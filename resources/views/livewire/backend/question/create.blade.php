<?php

use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use App\Models\{Category, Question};

new class extends Component {
    use Toast;
    // Form properties
    #[Title('Create Question')]
    public string $question_text = '';
    public string $category_id = '';
    public array $options = ['', '', '', ''];
    public string $correct_option_id = '';
    public int $points = 1;

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

    // Save question
    public function save(): void
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

            $question = Question::create([
                'category_id' => $this->category_id,
                'question_text' => $this->question_text,
                'correct_option' => $this->options[(int) $this->correct_option_id],
                'points' => $this->points,
            ]);

            // Create options
            foreach ($this->options as $index => $optionText) {
                $option = $question->options()->create([
                    'option_text' => $optionText,
                    'order_by' => $index + 1, // Set order_by based on position
                ]);

                // If this is the correct option, update the question with the option ID
                if ($index == (int) $this->correct_option_id) {
                    $question->update(['correct_option' => $option->id]);
                }
            }

            $this->success('Question created successfully!', position: 'toast-bottom', redirect: route('admin.question.index'));
        } catch (\Exception $e) {
            $this->error('Failed to create question. Please try again.', position: 'toast-bottom');
        }
    }

    // Reset form
    public function resetForm(): void
    {
        $this->reset();
        $this->resetValidation();
        $this->options = ['', '', '', ''];
        $this->points = 1;
        $this->success('Form reset successfully!', position: 'toast-bottom');
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
                Create Question
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
                        Create Question
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-button label="Reset Form" icon="o-arrow-path" class="btn-outline" wire:click="resetForm" responsive />
            <x-button label="Back to Questions" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.question.index') }}" responsive />
        </div>
    </div>
    <hr class="mb-5">

    <x-card shadow>
        <form wire:submit="save" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>
                </div>

                <x-choices-offline label="Category" wire:model.live="category_id" placeholder="Select a category"
                    icon="o-tag" :options="$categories" single required clearable />

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
                        Create exactly four unique options for this question
                    </p>
                </div>

                @if (count(array_unique($options)) !== count($options))
                    <x-alert title="Warning!" icon="o-exclamation-triangle" class="alert-warning"
                        description="All options must be unique. Please ensure no duplicate options exist." />
                @endif

                @foreach ($options as $index => $option)
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <x-input label="Option {{ $index + 1 }}" wire:model="options.{{ $index }}"
                                placeholder="Enter option {{ $index + 1 }}" icon="o-list-bullet" required>
                                <x-slot:append>
                                    <div class="join-item flex items-center justify-center border border-primary ">
                                        <input type="radio" name="correct_option_id" value="{{ $index }}"
                                            class="radio radio-lg mx-2 mt-1" wire:model.live="correct_option_id" />
                                    </div>
                                </x-slot:append>
                            </x-input>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <x-button label="Cancel" icon="o-x-mark" class="btn-error btn-soft btn-sm"
                    link="{{ route('admin.question.index') }}" responsive />
                <x-button label="Create Question" icon="o-plus" class="btn-primary btn-sm btn-soft" type="submit"
                    spinner="save" responsive />
            </div>
        </form>
    </x-card>
</div>
