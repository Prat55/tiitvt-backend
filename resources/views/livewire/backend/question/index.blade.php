<?php

use Livewire\Volt\Component;
use App\Models\Question;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Url};

new class extends Component {
    use WithPagination;

    #[Title('All Questions')]
    public $headers;

    #[Url]
    public string $search = '';

    public $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public string $category_filter = '';

    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'question_text', 'label' => 'Question', 'class' => 'w-80'], ['key' => 'category', 'label' => 'Category', 'class' => 'w-32'], ['key' => 'options', 'label' => 'Options', 'class' => 'w-24'], ['key' => 'correct_option', 'label' => 'Correct', 'class' => 'w-24'], ['key' => 'points', 'label' => 'Points', 'class' => 'w-20'], ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-32']];
    }

    public function deleteQuestion($questionId)
    {
        try {
            $question = Question::findOrFail($questionId);
            $question->delete();
            $this->success('Question deleted successfully!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to delete question. Please try again.', position: 'toast-bottom');
        }
    }

    public function rendering($view)
    {
        $view->questions = Question::with(['category', 'options', 'correctOption'])
            ->when($this->search, function ($query) {
                $query->where('question_text', 'like', '%' . $this->search . '%');
            })
            ->when($this->category_filter, function ($query) {
                $query->where('category_id', $this->category_filter);
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate(20);

        $view->categories = \App\Models\Category::active()->get(['id', 'name']);
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
            <x-button label="Add Question" icon="o-plus" class="btn-primary inline-flex" responsive
                link="{{ route('admin.question.create') }}" />
        </div>
    </div>
    <hr class="mb-5">

    <!-- Filters -->
    <x-card shadow class="mb-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-choices-offline label="Filter by Category" wire:model.live="category_filter" placeholder="All Categories"
                icon="o-tag" :options="$categories" clearable />
        </div>
    </x-card>

    <!-- Questions Table -->
    <x-table :headers="$headers" :rows="$questions" with-pagination :sort-by="$sortBy">
        @scope('cell_question_text', $question)
            <div class="max-w-xs">
                <div class="font-medium">{{ Str::limit($question->question_text, 80) }}</div>
            </div>
        @endscope

        @scope('cell_category', $question)
            <span class="badge badge-secondary badge-sm">{{ $question->category->name ?? 'N/A' }}</span>
        @endscope

        @scope('cell_options', $question)
            <span class="badge badge-outline badge-sm">{{ $question->options->count() ?? 0 }} options</span>
        @endscope

        @scope('cell_correct_option', $question)
            <span class="badge badge-success badge-sm">{{ $question->correctOption->option_text ?? 'N/A' }}</span>
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
</div>
