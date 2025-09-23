<?php

namespace App\Jobs;

use App\Models\Question;
use App\Models\Option;
use App\Models\Category;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ImportQuestionsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;

    protected $batchData;
    protected $batchNumber;
    protected $totalBatches;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $batchData, int $batchNumber, int $totalBatches, int $userId)
    {
        $this->batchData = $batchData;
        $this->batchNumber = $batchNumber;
        $this->totalBatches = $totalBatches;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            Log::channel('import')->info("Batch {$this->batchNumber} was cancelled");
            return;
        }

        try {
            Log::channel('import')->info("Processing batch {$this->batchNumber}/{$this->totalBatches} with " . count($this->batchData) . " records");

            $importedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            DB::transaction(function () use (&$importedCount, &$skippedCount, &$errorCount) {
                foreach ($this->batchData as $index => $rowData) {
                    try {
                        $result = $this->importQuestion($rowData, $index + 1);

                        if ($result === 'imported') {
                            $importedCount++;
                        } elseif ($result === 'skipped') {
                            $skippedCount++;
                        }
                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::channel('import')->error('Error importing question row', [
                            'batch' => $this->batchNumber,
                            'row' => $index + 1,
                            'data' => $rowData,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            Log::channel('import')->info("Batch {$this->batchNumber} completed successfully: {$importedCount} imported, {$skippedCount} skipped, {$errorCount} errors");
        } catch (\Exception $e) {
            Log::channel('import')->error("Batch {$this->batchNumber} failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Import a single question from row data.
     */
    private function importQuestion(array $rowProperties, int $rowNumber): string
    {
        // Validate required fields as per Spatie documentation pattern
        $requiredFields = ['question_text', 'category_id', 'option_1', 'option_2', 'option_3', 'option_4', 'correct_option'];
        foreach ($requiredFields as $field) {
            if (empty($rowProperties[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        // Validate category_id exists
        $categoryId = (int) $rowProperties['category_id'];
        $category = Category::find($categoryId);
        if (!$category) {
            throw new \Exception("Category ID {$categoryId} does not exist. Please use a valid category ID.");
        }

        $questionText = trim($rowProperties['question_text']);

        // Check for duplicate question (same text and category)
        $existingQuestion = Question::where('question_text', $questionText)
            ->where('category_id', $categoryId)
            ->first();

        if ($existingQuestion) {
            Log::channel('import')->info('Skipping duplicate question', [
                'row' => $rowNumber,
                'question_text' => $questionText,
                'category_id' => $categoryId,
                'existing_question_id' => $existingQuestion->id
            ]);
            return 'skipped';
        }

        // Create question
        $question = Question::create([
            'category_id' => $categoryId,
            'question_text' => $questionText,
            'points' => (int) ($rowProperties['points'] ?? 1),
        ]);

        // Create options
        $options = [];
        $correctOptionIndex = null;

        for ($i = 1; $i <= 4; $i++) {
            $optionText = trim($rowProperties["option_{$i}"]);
            if (empty($optionText)) {
                throw new \Exception("Option {$i} cannot be empty");
            }

            $option = Option::create([
                'question_id' => $question->id,
                'option_text' => $optionText,
                'order_by' => $i
            ]);

            $options[] = $option;

            // Check if this is the correct option
            $correctOptionValue = strtolower(trim($rowProperties['correct_option']));
            if (in_array($correctOptionValue, [$i, "option{$i}", "option_{$i}"])) {
                $correctOptionIndex = $i - 1; // 0-based index
            }
        }

        // Set correct option
        if ($correctOptionIndex !== null && isset($options[$correctOptionIndex])) {
            $question->update([
                'correct_option_id' => $options[$correctOptionIndex]->id
            ]);
        } else {
            throw new \Exception("Invalid correct option value: {$rowProperties['correct_option']}. Must be 1, 2, 3, 4, option1, option2, option3, or option4");
        }

        return 'imported';
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('import')->error("ImportQuestionsJob batch {$this->batchNumber} failed permanently: " . $exception->getMessage());
    }
}
