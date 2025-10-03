<?php

use Mary\Traits\Toast;
use App\Models\Question;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Jobs\ImportQuestionsJob;
use Illuminate\Support\Facades\Bus;
use Livewire\Attributes\{Title, Url};

new class extends Component {
    use WithPagination, WithFileUploads, Toast;

    #[Title('All Questions')]
    public $headers;

    #[Url]
    public string $search = '';

    public $sortBy = ['column' => 'id', 'direction' => 'desc'];

    // Filter properties
    public $filterDrawer = false;
    #[Url]
    public $selectedCategory = null;

    // Cached data to avoid repeated queries
    public $categories = [];

    // Import functionality
    public $showImportModal = false;
    public $importFile;
    public $importInProgress = false;
    public $importProgress = 0;
    public $importProcessed = 0;
    public $importTotal = 0;
    public $importImported = 0;
    public $importSkipped = 0;
    public $importErrors = 0;
    public $importStatus = 'idle'; // idle, importing, completed, failed
    public $failureReportPath = null;
    public $importBatchId = null;

    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'question_text', 'label' => 'Question', 'class' => 'w-80'], ['key' => 'category', 'label' => 'Category', 'class' => 'w-32'], ['key' => 'options', 'label' => 'Options', 'class' => 'w-24'], ['key' => 'correct_option', 'label' => 'Correct', 'class' => 'w-24'], ['key' => 'points', 'label' => 'Points', 'class' => 'w-20']];
    }

    // Mount method to cache filter options
    public function mount(): void
    {
        // Cache categories data to avoid repeated queries
        $this->categories = \App\Models\Category::active()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return ['name' => $category->name, 'id' => $category->id];
            })
            ->toArray();
    }

    public function deleteQuestion($questionId)
    {
        $question = Question::findOrFail($questionId);
        $question->options()->delete();
        $question->delete();
        $this->success('Question deleted successfully!', position: 'toast-bottom');
    }

    // Import methods
    public function openImportModal()
    {
        $this->showImportModal = true;
        $this->importFile = null;
        $this->importInProgress = false;
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importInProgress = false;
    }

    public function importQuestions()
    {
        $this->validate(
            [
                'importFile' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            ],
            [
                'importFile.required' => 'Please select a file to import.',
                'importFile.file' => 'The selected file is not valid.',
                'importFile.mimes' => 'The file must be an Excel file (xlsx, xls) or CSV.',
                'importFile.max' => 'The file size must not exceed 10MB.',
            ],
        );

        try {
            $this->importInProgress = true;
            $this->importStatus = 'processing';
            $this->importProgress = 0;
            $this->importProcessed = 0;
            $this->importTotal = 0;
            $this->importImported = 0;
            $this->importSkipped = 0;
            $this->importErrors = 0;

            // Process file directly in memory instead of storing temporarily
            $this->processImportFile($this->importFile);

            $this->success('Questions import has been queued successfully! Processing will start shortly.', position: 'toast-bottom');
            $this->closeImportModal();

            // Start checking import status periodically
            $this->js('
                const checkInterval = setInterval(() => {
                    $wire.checkImportStatus();

                    // Stop checking when import is completed or failed
                    if (!$wire.importInProgress || $wire.importStatus === "completed" || $wire.importStatus === "failed" || $wire.importStatus === "cancelled") {
                        clearInterval(checkInterval);
                    }
                }, 2000); // Check every 2 seconds
            ');
        } catch (\Exception $e) {
            $this->error('Failed to start import: ' . $e->getMessage(), position: 'toast-bottom');
            $this->importInProgress = false;
            $this->importStatus = 'failed';
        }
    }

    public function processImportFile($file)
    {
        try {
            Log::channel('import')->info('Starting file processing for: ' . $file->getClientOriginalName());

            $fileExtension = $file->getClientOriginalExtension();
            $rows = [];

            if (in_array($fileExtension, ['xlsx', 'xls'])) {
                // For Excel files, we'll use a simple approach with PhpSpreadsheet
                $rows = $this->readExcelFile($file->getRealPath());
            } else {
                // Use Laravel's built-in CSV reading
                $rows = $this->readCsvFile($file->getRealPath());
            }

            // Filter out empty rows
            $filteredRows = array_filter($rows, function ($row) {
                $rowData = is_array($row) ? $row : (array) $row;
                return !empty(
                    array_filter($rowData, function ($value) {
                        return $value !== null && $value !== '';
                    })
                );
            });

            $this->importTotal = count($filteredRows);
            $batchSize = 50; // Process 50 questions per batch

            Log::channel('import')->info("Starting batch import with batch size: {$batchSize}, total rows: {$this->importTotal}");

            // Prepare batches for queue jobs
            $batches = [];
            $batchNumber = 1;

            for ($i = 0; $i < $this->importTotal; $i += $batchSize) {
                $batch = array_slice($filteredRows, $i, $batchSize);

                if (!empty($batch)) {
                    $batches[] = new ImportQuestionsJob($batch, $batchNumber, ceil($this->importTotal / $batchSize), auth()->id());
                    $batchNumber++;
                }
            }

            // Dispatch batch jobs
            $batch = Bus::batch($batches)
                ->name('Questions Import - ' . now()->format('Y-m-d H:i:s'))
                ->allowFailures()
                ->dispatch();

            $this->importBatchId = $batch->id;
            $this->importStatus = 'importing';
            $this->importInProgress = true;

            Log::channel('import')->info("Questions import batch dispatched with ID: {$this->importBatchId}");
        } catch (\Exception $e) {
            $this->importInProgress = false;
            $this->importStatus = 'failed';
            Log::channel('import')->error('Import preparation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function downloadSampleExcel()
    {
        return response()->download(public_path('default/sample_questions.xlsx'));
    }

    // Progress update methods
    public function updateImportProgress($percentage, $processedRows, $totalRows, $importedCount, $skippedCount, $errorCount)
    {
        $this->importProgress = $percentage;
        $this->importProcessed = $processedRows;
        $this->importTotal = $totalRows;
        $this->importImported = $importedCount;
        $this->importSkipped = $skippedCount;
        $this->importErrors = $errorCount;
        $this->importStatus = 'importing';
    }

    public function importCompleted($importedCount, $skippedCount, $errorCount, $failureReportPath = null)
    {
        $this->importImported = $importedCount;
        $this->importSkipped = $skippedCount;
        $this->importErrors = $errorCount;
        $this->importProgress = 100;
        $this->importStatus = 'completed';
        $this->failureReportPath = $failureReportPath;
        $this->importInProgress = false;

        $message = "Import completed! Imported: {$importedCount}, Skipped: {$skippedCount}, Errors: {$errorCount}";
        if ($failureReportPath) {
            $message .= ' - Failure report generated.';
        }

        $this->success($message, position: 'toast-bottom');
    }

    public function importFailed($error)
    {
        $this->importStatus = 'failed';
        $this->importInProgress = false;
        $this->error('Import failed: ' . $error, position: 'toast-bottom');
    }

    public function downloadFailureReport()
    {
        if ($this->failureReportPath && file_exists($this->failureReportPath)) {
            return response()->download($this->failureReportPath);
        }

        $this->error('Failure report not found.', position: 'toast-bottom');
    }

    public function checkImportStatus()
    {
        if ($this->importBatchId) {
            $batch = \Illuminate\Support\Facades\Bus::findBatch($this->importBatchId);

            if ($batch) {
                $this->importProgress = $batch->progress();

                // Update processed rows based on batch progress
                if ($this->importTotal > 0) {
                    $this->importProcessed = round(($this->importProgress / 100) * $this->importTotal);
                }

                if ($batch->finished()) {
                    $this->importStatus = 'completed';
                    $this->importInProgress = false;
                    $this->importProgress = 100;
                    $this->importProcessed = $this->importTotal;

                    // Get final statistics from batch
                    $this->importImported = $batch->totalJobs - $batch->failedJobs;
                    $this->importErrors = $batch->failedJobs;

                    if ($batch->hasFailures()) {
                        $this->warning('Import completed with some failures. Check logs for details.');
                    } else {
                        $this->success("Import completed successfully! {$batch->totalJobs} batches processed.");
                    }
                } elseif ($batch->cancelled()) {
                    $this->importStatus = 'cancelled';
                    $this->importInProgress = false;
                    $this->error('Import was cancelled.');
                } else {
                    $this->importStatus = 'importing';

                    // Update statistics based on completed jobs
                    $completedJobs = $batch->totalJobs - $batch->pendingJobs;
                    $this->importImported = $completedJobs;
                    $this->importErrors = $batch->failedJobs;
                }

                Log::channel('import')->info("Import status check - Progress: {$this->importProgress}%, Status: {$this->importStatus}, Processed: {$this->importProcessed}/{$this->importTotal}");
            }
        }
    }

    /**
     * Read CSV file using Laravel's built-in functionality
     */
    private function readCsvFile($filePath)
    {
        $rows = [];
        $handle = fopen($filePath, 'r');

        if ($handle !== false) {
            $headers = fgetcsv($handle, 0, ',', '"', '\\'); // Read header row with explicit parameters

            while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                if (count($data) === count($headers)) {
                    $rows[] = array_combine($headers, $data);
                }
            }

            fclose($handle);
        }

        return $rows;
    }

    /**
     * Read Excel file using PhpSpreadsheet (Laravel's built-in package)
     */
    private function readExcelFile($filePath)
    {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row and convert to associative array
            $headers = array_shift($rows);
            $data = [];

            foreach ($rows as $row) {
                if (!empty(array_filter($row))) {
                    // Skip empty rows
                    $data[] = array_combine($headers, $row);
                }
            }

            return $data;
        } catch (\Exception $e) {
            Log::channel('import')->error('Error reading Excel file: ' . $e->getMessage());
            throw new \Exception('Error reading Excel file: ' . $e->getMessage());
        }
    }

    public function rendering($view)
    {
        $query = Question::with(['category', 'options', 'correctOption']);

        // Apply category filter
        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }

        // Apply search filter
        if ($this->search) {
            $query->where('question_text', 'like', '%' . $this->search . '%');
        }

        $view->questions = $query->orderBy(...array_values($this->sortBy))->paginate(20);
    }

    // Clear all filters
    public function clearFilters(): void
    {
        $this->selectedCategory = null;
        $this->search = '';
        $this->resetPage();
    }

    // Check if any filters are active
    public function hasActiveFilters(): bool
    {
        return !empty($this->selectedCategory) || !empty($this->search);
    }

    // Reset pagination when filters change
    public function updatedSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                All Questions
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        All Questions
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-input placeholder="Search questions..." icon="o-magnifying-glass" wire:model.live.debounce="search" />

            <x-button icon="o-plus" class="btn-primary inline-flex" responsive
                link="{{ route('admin.question.create') }}" tooltip-left="Add Question" />

            <x-button tooltip="Import Questions" icon="o-arrow-up-tray" class="btn-secondary inline-flex" responsive
                wire:click="openImportModal" />

            <x-button tooltip-left="Filter Questions" class="btn-secondary" icon="o-funnel"
                wire:click="$toggle('filterDrawer')" />

            @if ($this->hasActiveFilters())
                <x-button icon="o-x-mark" class="btn-outline btn-primary" wire:click="clearFilters"
                    tooltip-left="Clear all filters" />
            @endif
        </div>
    </div>
    <hr class="mb-5">

    <!-- Import Progress -->
    @if ($importInProgress || $importStatus === 'importing')
        <div class="mb-6 p-4 bg-base-200 rounded-xl">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-blue-800">Importing Questions</h3>
                <span class="text-sm text-blue-600">{{ $importProcessed }}/{{ $importTotal }} processed</span>
            </div>
            <x-progress :value="$importProgress" max="100" class="mb-2" />
            <div class="flex justify-between text-xs text-blue-600">
                <span>Imported: {{ $importImported }}</span>
                <span>Skipped: {{ $importSkipped }}</span>
                <span>Errors: {{ $importErrors }}</span>
            </div>
        </div>
    @endif

    <!-- Questions Table -->
    <x-table :headers="$headers" :rows="$questions" with-pagination :sort-by="$sortBy">
        @scope('cell_question_text', $question)
            <div class="max-w-xs">
                <div class="font-medium">{{ Str::limit($question->question_text, 80) }}</div>
            </div>
        @endscope

        @scope('cell_category', $question)
            <span class="badge badge-secondary badge-sm h-fit">{{ $question->category->name ?? 'N/A' }}</span>
        @endscope

        @scope('cell_options', $question)
            <span class="badge badge-outline badge-sm">{{ $question->options->count() ?? 0 }} options</span>
        @endscope

        @scope('cell_correct_option', $question)
            <span
                class="badge badge-success badge-sm h-fit">{{ Str::limit($question->correctOption->option_text, 20) ?? 'N/A' }}</span>
        @endscope

        @scope('cell_points', $question)
            <span class="badge badge-info badge-sm">{{ $question->points }} pts</span>
        @endscope

        @scope('actions', $question)
            <div class="flex gap-1">
                <x-button icon="o-eye" link="{{ route('admin.question.show', $question) }}" class="btn-xs btn-ghost"
                    title="View Details" />
                <x-button icon="o-pencil" link="{{ route('admin.question.edit', $question) }}" class="btn-xs btn-ghost"
                    title="Edit Question" />
                <x-button icon="o-trash" class="btn-xs btn-ghost text-error"
                    wire:click="deleteQuestion({{ $question->id }})"
                    wire:confirm="Are you sure you want to delete this question?" title="Delete Question" />
            </div>
        @endscope

        <x-slot:empty>
            <x-empty icon="o-question-mark-circle" message="No questions found" />
        </x-slot>
    </x-table>

    <!-- Import Modal -->
    <x-modal wire:model="showImportModal" title="Import Questions from Excel" class="backdrop-blur">
        <div class="space-y-4">
            <div class="alert alert-info">
                <x-icon name="o-information-circle" class="w-5 h-5" />
                <div>
                    <h3 class="font-bold">Import Instructions</h3>
                    <div class="text-sm mt-2">
                        <p>• Download the sample Excel file to see the required format</p>
                        <p>• Ensure all required fields are filled</p>
                        <p>• Correct option should be 1, 2, 3, or 4 (or option1, option2, etc.)</p>
                        <p>• Categories will be created automatically if they don't exist</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-2 mb-4">
                <x-button label="Download Sample Excel" icon="o-arrow-down-tray" class="btn-outline btn-sm"
                    wire:click="downloadSampleExcel" />
            </div>

            <x-file label="Select Excel File" wire:model="importFile" accept=".xlsx,.xls,.csv" />

            @if ($importFile)
                <div class="alert alert-success">
                    <x-icon name="o-check-circle" class="w-5 h-5" />
                    <span>File selected: {{ $importFile->getClientOriginalName() }}</span>
                </div>
            @endif

            <!-- Progress Section -->
            @if ($importStatus === 'importing')
                <div class="space-y-4">
                    <div class="divider">Import Progress</div>

                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Progress: {{ $importProcessed }} / {{ $importTotal }}</span>
                            <span>{{ $importProgress }}%</span>
                        </div>
                        <progress class="progress progress-primary w-full" value="{{ $importProgress }}"
                            max="100"></progress>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="stat">
                            <div class="stat-title">Imported</div>
                            <div class="stat-value text-success">{{ $importImported }}</div>
                        </div>
                        <div class="stat">
                            <div class="stat-title">Skipped</div>
                            <div class="stat-value text-warning">{{ $importSkipped }}</div>
                        </div>
                        <div class="stat">
                            <div class="stat-title">Errors</div>
                            <div class="stat-value text-error">{{ $importErrors }}</div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Completion Section -->
            @if ($importStatus === 'completed')
                <div class="space-y-4">
                    <div class="divider">Import Complete</div>

                    <div class="alert alert-success">
                        <x-icon name="o-check-circle" class="w-5 h-5" />
                        <div>
                            <h3 class="font-bold">Import Summary</h3>
                            <div class="text-sm mt-2">
                                <p>✅ Imported: {{ $importImported }} questions</p>
                                <p>⚠️ Skipped: {{ $importSkipped }} duplicates</p>
                                <p>❌ Errors: {{ $importErrors }} failed rows</p>
                            </div>
                        </div>
                    </div>

                    @if ($failureReportPath)
                        <div class="alert alert-warning">
                            <x-icon name="o-exclamation-triangle" class="w-5 h-5" />
                            <div>
                                <h3 class="font-bold">Failure Report Available</h3>
                                <p class="text-sm mt-1">Some rows failed to import. Download the report to see details.
                                </p>
                                <x-button label="Download Failure Report" icon="o-arrow-down-tray"
                                    class="btn-outline btn-sm mt-2" wire:click="downloadFailureReport" />
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Failed Section -->
            @if ($importStatus === 'failed')
                <div class="alert alert-error">
                    <x-icon name="o-x-circle" class="w-5 h-5" />
                    <div>
                        <h3 class="font-bold">Import Failed</h3>
                        <p class="text-sm mt-1">The import process encountered an error and could not complete.</p>
                    </div>
                </div>
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Cancel" wire:click="closeImportModal" class="btn-ghost" />
            @if ($importStatus === 'idle')
                <x-button label="Import Questions" icon="o-arrow-up-tray" class="btn-primary"
                    wire:click="importQuestions" :disabled="$importInProgress || !$importFile" />
            @elseif($importStatus === 'completed')
                <x-button label="Close" wire:click="closeImportModal" class="btn-primary" />
            @endif
        </x-slot:actions>
    </x-modal>

    <!-- Filter Drawer -->
    <x-drawer wire:model="filterDrawer" class="w-11/12 lg:w-1/3" right title="Filter Questions">
        <div class="space-y-4">
            <div class="space-y-4">
                <x-choices-offline label="Filter by Category" wire:model.live="selectedCategory" :options="$categories"
                    placeholder="All Categories" single clearable searchable />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Clear All" icon="o-x-mark" class="btn-outline" wire:click="clearFilters" />
            <x-button label="Close" @click="$wire.filterDrawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
