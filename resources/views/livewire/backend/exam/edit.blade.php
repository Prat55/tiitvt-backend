<?php

use App\Models\Exam;
use App\Enums\ExamStatusEnum;
use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\Volt\Component;

new class extends Component {
    use Toast;

    #[Title('Edit Exam')]
    public Exam $exam;

    public $date;
    public $startTime;
    public $endTime;
    public $duration;

    public function mount(Exam $exam): void
    {
        $this->exam = $exam->load(['course', 'examCategories.category']);

        // Initialize form with current values
        $this->date = $this->exam->date->format('Y-m-d');
        $this->startTime = $this->exam->start_time->format('H:i');
        $this->endTime = $this->exam->end_time->format('H:i');
        $this->duration = $this->exam->duration;
    }

    public function rendering(View $view): void
    {
        $view->exam = $this->exam;
    }

    public function updateExam(): void
    {
        $this->validate([
            'date' => 'required|date|after_or_equal:today',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i|after:startTime',
            'duration' => 'required|integer|min:15|max:300',
        ]);

        try {
            $this->exam->update([
                'date' => $this->date,
                'start_time' => $this->date . ' ' . $this->startTime,
                'end_time' => $this->date . ' ' . $this->endTime,
                'duration' => $this->duration,
            ]);

            $this->success('Exam updated successfully!');
            $this->redirect(route('admin.exam.show', $this->exam->id));
        } catch (\Exception $e) {
            $this->error('Failed to update exam: ' . $e->getMessage());
        }
    }

    public function calculateEndTime(): void
    {
        if ($this->startTime && $this->duration) {
            $start = \Carbon\Carbon::createFromFormat('H:i', $this->startTime);
            $end = $start->addMinutes($this->duration);
            $this->endTime = $end->format('H:i');
        }
    }
};
?>

<div>
    {{-- Header Section --}}
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Edit Exam
            </h1>
            <div class="breadcrumbs text-sm text-gray-600 dark:text-gray-400 mt-1">
                <ul class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate class="hover:text-primary transition-colors">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.exam.index') }}" wire:navigate
                            class="hover:text-primary transition-colors">
                            All Exams
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.exam.show', $exam->id) }}" wire:navigate
                            class="hover:text-primary transition-colors">
                            {{ $exam->exam_id }}
                        </a>
                    </li>
                    <li class="font-medium">Edit</li>
                </ul>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <x-button label="View Exam" icon="o-eye" class="btn-primary btn-sm"
                link="{{ route('admin.exam.show', $exam->id) }}" />
            <x-button label="Back to Exams" icon="o-arrow-left" class="btn-ghost btn-sm"
                link="{{ route('admin.exam.index') }}" />
        </div>
    </div>
    <hr class="mb-6">

    {{-- Exam Info Card --}}
    <x-card class="mb-6">
        <x-slot:title>Exam Information</x-slot:title>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="flex items-center gap-3">
                <x-icon name="o-identification" class="w-5 h-5 text-primary" />
                <div>
                    <p class="text-sm text-gray-500">Exam ID</p>
                    <p class="font-mono font-medium">{{ $exam->exam_id }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-icon name="o-academic-cap" class="w-5 h-5 text-primary" />
                <div>
                    <p class="text-sm text-gray-500">Course</p>
                    <p class="font-medium">{{ $exam->course->name ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-icon name="o-users" class="w-5 h-5 text-primary" />
                <div>
                    <p class="text-sm text-gray-500">Enrolled Students</p>
                    <p class="font-medium">{{ $exam->enrolled_students_count ?? 0 }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-icon name="o-flag" class="w-5 h-5 text-primary" />
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span class="{{ $exam->status->badge() }} badge-sm">
                        {{ $exam->status->label() }}
                    </span>
                </div>
            </div>
        </div>
    </x-card>

    {{-- Edit Form --}}
    <x-card>
        <x-slot:title>Update Exam Details</x-slot:title>

        <form wire:submit="updateExam" class="space-y-6">
            {{-- Exam Details --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary mb-4">Schedule Details</h3>
                </div>

                <x-datepicker label="Date" wire:model="date" icon="o-calendar" required />

                <div class="grid grid-cols-2 gap-3">
                    <x-input label="Start Time" wire:model.live="startTime" wire:change="calculateEndTime"
                        type="time" icon="o-play" required />

                    <x-input label="End Time" wire:model="endTime" type="time" icon="o-stop" required />
                </div>

                <x-input label="Duration (minutes)" wire:model.live="duration" wire:change="calculateEndTime"
                    type="number" icon="o-clock" min="15" max="300" required />
            </div>

            {{-- Categories Display --}}
            @if ($exam->examCategories->count() > 0)
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-primary">Exam Categories</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($exam->examCategories as $examCategory)
                            <span class="badge badge-primary badge-outline">
                                <x-icon name="o-tag" class="w-3 h-3 mr-1" />
                                {{ $examCategory->category->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Form Actions --}}
            <div class="flex justify-end gap-3 pt-6 border-t">
                <x-button label="Cancel" icon="o-x-mark" class="btn-error btn-soft btn-sm"
                    link="{{ route('admin.exam.show', $exam->id) }}" />
                <x-button label="Update Exam" icon="o-check" class="btn-primary btn-sm" type="submit"
                    spinner="updateExam" />
            </div>
        </form>
    </x-card>
</div>
