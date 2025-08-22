<?php

use Livewire\Volt\Component;
use App\Services\ExamService;

new class extends Component {
    public $examStats = [];

    public function mount()
    {
        $examService = new ExamService();
        $this->examStats = $examService->getExamStats();
    }
}; ?>

<x-card shadow>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Exam Overview</h3>
        <x-button label="View All" icon="o-arrow-right" class="btn-ghost btn-sm" link="{{ route('admin.exam.index') }}" />
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Exams -->
        <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $examStats['total'] ?? 0 }}</div>
            <div class="text-sm text-gray-600">Total Exams</div>
        </div>

        <!-- Scheduled Exams -->
        <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $examStats['scheduled'] ?? 0 }}</div>
            <div class="text-sm text-gray-600">Scheduled</div>
        </div>

        <!-- Completed Exams -->
        <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ $examStats['completed'] ?? 0 }}</div>
            <div class="text-sm text-gray-600">Completed</div>
        </div>

        <!-- Cancelled Exams -->
        <div class="text-center">
            <div class="text-2xl font-bold text-red-600">{{ $examStats['cancelled'] ?? 0 }}</div>
            <div class="text-sm text-gray-600">Cancelled</div>
        </div>

        <!-- Overdue Exams -->
        <div class="text-center">
            <div class="text-2xl font-bold text-orange-600">{{ $examStats['overdue'] ?? 0 }}</div>
            <div class="text-sm text-gray-600">Overdue</div>
        </div>
    </div>

    @if (($examStats['overdue'] ?? 0) > 0)
        <div class="mt-4 alert alert-warning">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"></path>
            </svg>
            <span class="text-sm">
                {{ $examStats['overdue'] }} exam(s) are overdue and will be automatically cancelled
            </span>
        </div>
    @endif

    <div class="mt-4 flex justify-center">
        <x-button label="Schedule New Exam" icon="o-calendar" class="btn-primary btn-sm"
            link="{{ route('admin.exam.schedule') }}" />
    </div>
</x-card>
