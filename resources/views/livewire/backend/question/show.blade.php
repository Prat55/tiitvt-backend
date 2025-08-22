<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Title};

new class extends Component {
    #[Title('Question Details')]
    public \App\Models\Question $question;

    public function mount(\App\Models\Question $question): void
    {
        $this->question = $question->load([
            'category',
            'options' => function ($query) {
                $query->orderBy('order_by');
            },
            'correctOption',
        ]);
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Question Details
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
                        Question Details
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-button label="Edit Question" icon="o-pencil" class="btn-primary btn-outline"
                link="{{ route('admin.question.edit', $question) }}" responsive />
            <x-button label="Back to Questions" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.question.index') }}" responsive />
        </div>
    </div>
    <hr class="mb-5">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Question Information -->
        <div class="lg:col-span-2">
            <x-card shadow>
                <div class="space-y-6">
                    <!-- Question Text -->
                    <div>
                        <h3 class="text-lg font-semibold text-primary mb-3">Question</h3>
                        <div class="bg-base-200 p-4 rounded-lg">
                            <p class="text-lg">{{ $question->question_text }}</p>
                        </div>
                    </div>

                    <!-- Options -->
                    <div>
                        <h3 class="text-lg font-semibold text-primary mb-3">Options</h3>
                        <div class="space-y-3">
                            @foreach ($question->options as $index => $option)
                                <div
                                    class="flex items-center gap-3 p-3 rounded-lg border {{ $option->id === $question->correct_option_id ? 'border-success bg-success/10' : 'border-base-300' }}">
                                    <div
                                        class="w-8 h-8 rounded-full bg-primary text-primary-content flex items-center justify-center text-sm font-medium">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium">{{ $option->option_text }}</p>
                                    </div>
                                    @if ($option->id === $question->correct_option_id)
                                        <div class="flex items-center gap-2 text-success">
                                            <x-icon name="o-check-circle" class="w-5 h-5" />
                                            <span class="text-sm font-medium">Correct Answer</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Sidebar Information -->
        <div class="space-y-6">
            <!-- Basic Details -->
            <x-card shadow>
                <h3 class="text-lg font-semibold text-primary mb-4">Question Details</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600">Question ID:</span>
                        <span class="badge badge-outline">#{{ $question->id }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600">Points:</span>
                        <span class="badge badge-info">{{ $question->points }} pts</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600">Created:</span>
                        <span class="text-sm">{{ $question->created_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600">Updated:</span>
                        <span class="text-sm">{{ $question->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </x-card>

            <!-- Category Information -->
            <x-card shadow>
                <h3 class="text-lg font-semibold text-primary mb-4">Category Information</h3>
                <div class="space-y-4">
                    <div>
                        <span class="text-sm font-medium text-gray-600">Category:</span>
                        <div class="mt-1">
                            <span class="badge badge-secondary">{{ $question->category->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Quick Actions -->
            <x-card shadow>
                <h3 class="text-lg font-semibold text-primary mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <x-button label="Edit Question" icon="o-pencil" class="btn-primary btn-sm w-full"
                        link="{{ route('admin.question.edit', $question) }}" />
                    <x-button label="Back to Questions" icon="o-arrow-left" class="btn-outline btn-sm w-full"
                        link="{{ route('admin.question.index') }}" />
                </div>
            </x-card>
        </div>
    </div>

    <!-- Bottom Actions -->
    <div class="mt-8 flex justify-center gap-4">
        <x-button label="Edit Question" icon="o-pencil" class="btn-primary"
            link="{{ route('admin.question.edit', $question) }}" />
        <x-button label="Back to Questions" icon="o-arrow-left" class="btn-outline"
            link="{{ route('admin.question.index') }}" />
    </div>
</div>
