<?php

use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use App\Models\{Question, Category};

new class extends Component {
    use Toast;

    #[Title('Edit Question')]
    public Question $question;

    // Form properties
    public string $question_text = '';
    public string $category_id = '';
    public array $options = ['', '', '', ''];
    public string $correct_option_id = '';
    public int $points = 1;

    public function mount(Question $question): void
    {
        $this->question = $question;
        $this->question_text = $question->question_text;
        $this->category_id = $question->category_id;
        $this->points = $question->points;

        // Load existing options ordered by order_by column
        $existingOptions = $question->options()->orderBy('order_by', 'asc')->pluck('option_text')->toArray();
        $this->options = array_pad($existingOptions, 4, '');

        // Find the index of the correct option
        if ($question->correct_option_id) {
            $correctOption = $question->options()->where('id', $question->correct_option_id)->first();
            if ($correctOption) {
                $index = array_search($correctOption->option_text, $this->options);
                if ($index !== false) {
                    $this->correct_option_id = (string) $index;
                }
            }
        }
    }

    // Move option up
    public function moveOptionUp(int $index): void
    {
        if ($index > 0) {
            // Swap options
            $temp = $this->options[$index];
            $this->options[$index] = $this->options[$index - 1];
            $this->options[$index - 1] = $temp;

            // Update correct option index if needed
            if ($this->correct_option_id == (string) $index) {
                $this->correct_option_id = (string) ($index - 1);
            } elseif ($this->correct_option_id == (string) ($index - 1)) {
                $this->correct_option_id = (string) $index;
            }

            $this->success('Option moved up successfully!', position: 'toast-bottom');
        }
    }

    // Move option down
    public function moveOptionDown(int $index): void
    {
        if ($index < count($this->options) - 1) {
            // Swap options
            $temp = $this->options[$index];
            $this->options[$index] = $this->options[$index + 1];
            $this->options[$index + 1] = $temp;

            // Update correct option index if needed
            if ($this->correct_option_id == (string) $index) {
                $this->correct_option_id = (string) ($index + 1);
            } elseif ($this->correct_option_id == (string) ($index + 1)) {
                $this->correct_option_id = (string) $index;
            }

            $this->success('Option moved down successfully!', position: 'toast-bottom');
        }
    }

    // Validation rules
    protected function rules(): array
    {
        return [
            'question_text' => 'required|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'options' => 'required|array|min:4|max:4',
            'options.*' => 'required|string|max:255',
            'correct_option_id' => 'required|string|max:255',
            'points' => 'required|integer|min:1|max:100',
        ];
    }

    // Validation messages
    protected function messages(): array
    {
        return [
            'question_text.required' => 'Question text is required.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category is invalid.',
            'options.required' => 'All four options are required.',
            'options.min' => 'Exactly four options are required.',
            'options.max' => 'Exactly four options are required.',
            'options.*.required' => 'All options must have text.',
            'correct_option_id.required' => 'Please select the correct option.',
            'points.required' => 'Points are required.',
            'points.min' => 'Points must be at least 1.',
            'points.max' => 'Points cannot exceed 100.',
        ];
    }

    // Update question
    public function update(): void
    {
        $this->validate();

        try {
            // Check for duplicate options
            if (count(array_unique($this->options)) !== count($this->options)) {
                $this->error('All options must be unique.', position: 'toast-bottom');
                return;
            }

            // Check if correct option ID is valid (0, 1, 2, or 3)
            if (!in_array($this->correct_option_id, ['0', '1', '2', '3'])) {
                $this->error('Please select a valid correct option.', position: 'toast-bottom');
                return;
            }

            // Update question basic info
            $this->question->update([
                'category_id' => $this->category_id,
                'question_text' => $this->question_text,
                'points' => $this->points,
            ]);

            // Delete existing options and create new ones with order_by
            $this->question->options()->delete();
            $correctOptionId = null;

            foreach ($this->options as $index => $optionText) {
                $option = $this->question->options()->create([
                    'option_text' => $optionText,
                    'order_by' => $index + 1, // Set order_by based on current position
                ]);

                // If this is the correct option, store the option ID
                if ($index == (int) $this->correct_option_id) {
                    $correctOptionId = $option->id;
                }
            }

            // Update the question with the correct option ID
            if ($correctOptionId) {
                $this->question->update(['correct_option_id' => $correctOptionId]);
            }

            $this->success('Question updated successfully!', position: 'toast-bottom');
            $this->redirect(route('admin.question.index'));
        } catch (\Exception $e) {
            $this->error('Failed to update question. Please try again.', position: 'toast-bottom');
        }
    }

    // Reset form to original values
    public function resetForm(): void
    {
        $this->mount($this->question);
        $this->resetValidation();
        $this->success('Form reset to original values!', position: 'toast-bottom');
    }

    public function rendering(View $view)
    {
        $view->categories = Category::active()
            ->latest()
            ->get(['id', 'name']);
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Edit Question
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.question.index') }}" wire:navigate>
                            Questions
                        </a>
                    </li>
                    <li>
                        Edit Question
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-button label="Reset Form" icon="o-arrow-path" class="btn-outline" wire:click="resetForm" responsive />
            <x-button label="View Question" icon="o-eye" class="btn-primary btn-outline"
                link="{{ route('admin.question.show', $question) }}" responsive />
            <x-button label="Back to Questions" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.question.index') }}" responsive />
        </div>
    </div>
    <hr class="mb-5">

    <x-card shadow>
        <form wire:submit="update" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>
                </div>

                <x-choices-offline label="Category" wire:model="category_id" placeholder="Select a category"
                    icon="o-tag" :options="$categories" required />

                <x-textarea label="Question Text" wire:model="question_text" placeholder="Enter your question here..."
                    icon="o-question-mark-circle" maxlength="1000" required class="md:col-span-2" />

                <x-input label="Points" wire:model="points" type="number" min="1" max="100"
                    placeholder="Enter points for this question" icon="o-star" required />
            </div>

            <!-- Options -->
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-primary">Question Options</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Edit the four options for this question. Use the arrows to reorder options.
                    </p>
                </div>

                @if (count(array_unique($options)) !== count($options))
                    <x-alert title="Warning!" icon="o-exclamation-triangle" class="alert-warning"
                        description="All options must be unique. Please ensure no duplicate options exist." />
                @endif

                @foreach ($options as $index => $option)
                    <div
                        class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <!-- Reorder Controls -->
                        <div class="flex flex-col gap-1">
                            <button type="button" wire:click="moveOptionUp({{ $index }})"
                                @if ($index === 0) disabled @endif
                                class="btn btn-sm btn-ghost {{ $index === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary hover:text-white' }}"
                                title="Move option up">
                                <x-icon name="o-chevron-up" class="w-4 h-4" />
                            </button>
                            <button type="button" wire:click="moveOptionDown({{ $index }})"
                                @if ($index === count($options) - 1) disabled @endif
                                class="btn btn-sm btn-ghost {{ $index === count($options) - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary hover:text-white' }}"
                                title="Move option down">
                                <x-icon name="o-chevron-down" class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Option Number Badge -->
                        <div class="flex-shrink-0">
                            <div
                                class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-semibold">
                                {{ $index + 1 }}
                            </div>
                        </div>

                        <!-- Option Input -->
                        <div class="flex-1">
                            <x-input label="Option {{ $index + 1 }}" wire:model="options.{{ $index }}"
                                placeholder="Enter option {{ $index + 1 }}" icon="o-list-bullet" required
                                class="mb-0" />
                        </div>

                        <!-- Correct Option Radio -->
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <input type="radio" name="correct_option_id" value="{{ (string) $index }}"
                                class="radio radio-lg radio-primary" wire:model.live="correct_option_id" />
                            <span
                                class="text-sm font-medium {{ $correct_option_id == (string) $index ? 'text-primary' : 'text-gray-500' }}">
                                {{ $correct_option_id == (string) $index ? 'Correct' : 'Mark as Correct' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <x-button label="Cancel" icon="o-x-mark" class="btn-error btn-soft btn-sm"
                    link="{{ route('admin.question.index') }}" responsive />
                <x-button label="Update Question" icon="o-check" class="btn-primary btn-sm btn-soft" type="submit"
                    spinner="update" responsive />
            </div>
        </form>
    </x-card>
</div>
