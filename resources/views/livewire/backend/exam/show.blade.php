<?php

use App\Models\Exam;
use App\Enums\ExamStatusEnum;
use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\Volt\Component;

new class extends Component {
    use Toast;

    #[Title('Exam Details')]
    public Exam $exam;

    public $showEditModal = false;
    public $showCancelModal = false;
    public $showRescheduleModal = false;

    // Edit form fields
    public $editDate;
    public $editStartTime;
    public $editEndTime;
    public $editDuration;

    // Reschedule form fields
    public $rescheduleDate;
    public $rescheduleStartTime;
    public $rescheduleEndTime;
    public $rescheduleDuration;

    public function mount(Exam $exam): void
    {
        $this->exam = $exam->load(['course', 'examStudents.student', 'examCategories.category', 'examResults.student']);

        // Initialize edit form with current values
        $this->editDate = $this->exam->date->format('Y-m-d');
        $this->editStartTime = $this->exam->start_time->format('H:i');
        $this->editEndTime = $this->exam->end_time->format('H:i');
        $this->editDuration = $this->exam->duration;

        // Initialize reschedule form
        $this->rescheduleDate = $this->exam->date->format('Y-m-d');
        $this->rescheduleStartTime = $this->exam->start_time->format('H:i');
        $this->rescheduleEndTime = $this->exam->end_time->format('H:i');
        $this->rescheduleDuration = $this->exam->duration;
    }

    public function rendering(View $view): void
    {
        $view->exam = $this->exam;
    }

    public function openEditModal(): void
    {
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetEditForm();
    }

    public function openCancelModal(): void
    {
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    public function openRescheduleModal(): void
    {
        $this->showRescheduleModal = true;
    }

    public function closeRescheduleModal(): void
    {
        $this->showRescheduleModal = false;
        $this->resetRescheduleForm();
    }

    public function resetEditForm(): void
    {
        $this->editDate = $this->exam->date->format('Y-m-d');
        $this->editStartTime = $this->exam->start_time->format('H:i');
        $this->editEndTime = $this->exam->end_time->format('H:i');
        $this->editDuration = $this->exam->duration;
    }

    public function resetRescheduleForm(): void
    {
        $this->rescheduleDate = $this->exam->date->format('Y-m-d');
        $this->rescheduleStartTime = $this->exam->start_time->format('H:i');
        $this->rescheduleEndTime = $this->exam->end_time->format('H:i');
        $this->rescheduleDuration = $this->exam->duration;
    }

    public function updateExam(): void
    {
        $this->validate([
            'editDate' => 'required|date|after_or_equal:today',
            'editStartTime' => 'required|date_format:H:i',
            'editEndTime' => 'required|date_format:H:i|after:editStartTime',
            'editDuration' => 'required|integer|min:15|max:300',
        ]);

        try {
            $this->exam->update([
                'date' => $this->editDate,
                'start_time' => $this->editDate . ' ' . $this->editStartTime,
                'end_time' => $this->editDate . ' ' . $this->editEndTime,
                'duration' => $this->editDuration,
            ]);

            $this->success('Exam updated successfully!');
            $this->closeEditModal();
            $this->exam->refresh();
        } catch (\Exception $e) {
            $this->error('Failed to update exam: ' . $e->getMessage());
        }
    }

    public function cancelExam(): void
    {
        try {
            $this->exam->update([
                'status' => ExamStatusEnum::CANCELLED,
            ]);

            $this->success('Exam cancelled successfully!');
            $this->closeCancelModal();
            $this->exam->refresh();
        } catch (\Exception $e) {
            $this->error('Failed to cancel exam: ' . $e->getMessage());
        }
    }

    public function rescheduleExam(): void
    {
        $this->validate([
            'rescheduleDate' => 'required|date|after_or_equal:today',
            'rescheduleStartTime' => 'required|date_format:H:i',
            'rescheduleEndTime' => 'required|date_format:H:i|after:rescheduleStartTime',
            'rescheduleDuration' => 'required|integer|min:15|max:300',
        ]);

        try {
            $this->exam->update([
                'date' => $this->rescheduleDate,
                'start_time' => $this->rescheduleDate . ' ' . $this->rescheduleStartTime,
                'end_time' => $this->rescheduleDate . ' ' . $this->rescheduleEndTime,
                'duration' => $this->rescheduleDuration,
                'status' => ExamStatusEnum::SCHEDULED,
            ]);

            $this->success('Exam rescheduled successfully!');
            $this->closeRescheduleModal();
            $this->exam->refresh();
        } catch (\Exception $e) {
            $this->error('Failed to reschedule exam: ' . $e->getMessage());
        }
    }

    public function canCancelOrReschedule(): bool
    {
        return $this->exam->completed_students_count == 0;
    }

    public function copyStudentCredentials($examUserId, $examPassword): void
    {
        $this->success('Student credentials copied to clipboard!');
    }
};
?>

<div>
    {{-- Header Section --}}
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Exam Details
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
                    <li class="font-medium">Exam #{{ $exam->id }}</li>
                </ul>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            @if ($exam->status === ExamStatusEnum::SCHEDULED)
                <x-button label="Edit Exam" icon="o-pencil" class="btn-warning btn-sm" wire:click="openEditModal" />
            @endif

            @if ($this->canCancelOrReschedule())
                <x-button label="Cancel Exam" icon="o-x-mark" class="btn-error btn-sm" wire:click="openCancelModal" />
                <x-button label="Reschedule" icon="o-calendar" class="btn-info btn-sm"
                    wire:click="openRescheduleModal" />
            @endif

            <x-button label="View Results" icon="o-chart-bar" class="btn-primary btn-sm"
                link="{{ route('admin.exam.results', $exam->id) }}" />
        </div>
    </div>
    <hr class="mb-6">

    {{-- Exam Overview Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

        <x-card class="bg-gradient-to-r from-green-500 to-green-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Enrolled Students</p>
                    <p class="text-2xl font-bold">{{ $exam->enrolled_students_count ?? 0 }}</p>
                </div>
                <div class="text-green-100">
                    <x-icon name="o-users" class="w-8 h-8" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-purple-500 to-purple-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Completed</p>
                    <p class="text-2xl font-bold">{{ $exam->completed_students_count ?? 0 }}</p>
                </div>
                <div class="text-purple-100">
                    <x-icon name="o-check-circle" class="w-8 h-8" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-orange-500 to-orange-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Duration</p>
                    <p class="text-2xl font-bold">{{ $exam->duration }} min</p>
                </div>
                <div class="text-orange-100">
                    <x-icon name="o-clock" class="w-8 h-8" />
                </div>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Exam Information --}}
        <x-card>
            <x-slot:title>Exam Information</x-slot:title>
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <x-icon name="o-academic-cap" class="w-5 h-5 text-primary" />
                    <div>
                        <p class="text-sm text-gray-500">Course</p>
                        <p class="font-medium">{{ $exam->course->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-icon name="o-calendar" class="w-5 h-5 text-primary" />
                    <div>
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="font-medium">{{ $exam->date ? $exam->date->format('M d, Y') : 'N/A' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-icon name="o-clock" class="w-5 h-5 text-primary" />
                    <div>
                        <p class="text-sm text-gray-500">Time</p>
                        <p class="font-medium">
                            @if ($exam->start_time && $exam->end_time)
                                {{ $exam->start_time->format('g:i A') }} - {{ $exam->end_time->format('g:i A') }}
                            @else
                                N/A
                            @endif
                        </p>
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

        {{-- Categories --}}
        <x-card>
            <x-slot:title>Exam Categories</x-slot:title>
            @if ($exam->examCategories->count() > 0)
                <div class="space-y-2">
                    @foreach ($exam->examCategories as $examCategory)
                        <div class="flex items-center gap-2 p-2 bg-base-100 rounded-lg">
                            <x-icon name="o-tag" class="w-4 h-4 text-primary" />
                            <span class="text-sm font-medium">{{ $examCategory->category->name }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">No categories assigned</p>
            @endif
        </x-card>
    </div>

    {{-- Enrolled Students --}}
    <x-card class="mt-6">
        <x-slot:title>Enrolled Students ({{ $exam->examStudents->count() }})</x-slot:title>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Exam User ID</th>
                        <th>Password</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exam->examStudents as $examStudent)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                        <x-icon name="o-user" class="w-4 h-4 text-primary" />
                                    </div>
                                    <div>
                                        <p class="font-medium">
                                            {{ $examStudent->student->first_name }}{{ $examStudent->student->fathers_name ? ' ' . $examStudent->student->fathers_name : '' }}{{ $examStudent->student->surname ? ' ' . $examStudent->student->surname : '' }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $examStudent->student->email ?? 'No email' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span
                                    class="font-mono text-sm bg-base-200 px-2 py-1 rounded">{{ $examStudent->exam_user_id }}</span>
                            </td>
                            <td>
                                <span
                                    class="font-mono text-sm bg-base-200 px-2 py-1 rounded">{{ $examStudent->exam_password }}</span>
                            </td>
                            <td>
                                @if ($examStudent->examResult && $examStudent->examResult->result_status !== 'NotDeclared')
                                    <span class="badge badge-success badge-sm">Completed</span>
                                @else
                                    <span class="badge badge-warning badge-sm">Pending</span>
                                @endif
                            </td>
                            <td>
                                <x-button icon="o-clipboard" class="btn-xs btn-ghost"
                                    onclick="copyStudentCredentials('{{ $examStudent->exam_user_id }}', '{{ $examStudent->exam_password }}')"
                                    tooltip="Copy Credentials" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">No students enrolled</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Edit Modal --}}
    <x-modal wire:model="showEditModal" title="Edit Exam" class="backdrop-blur">
        <div class="space-y-4">
            <x-datepicker label="Date" wire:model="editDate" icon="o-calendar" />

            <div class="grid grid-cols-2 gap-3">
                <x-input label="Start Time" wire:model="editStartTime" type="time" icon="o-play" />
                <x-input label="End Time" wire:model="editEndTime" type="time" icon="o-stop" />
            </div>

            <x-input label="Duration (minutes)" wire:model="editDuration" type="number" icon="o-clock" />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" class="btn-ghost" wire:click="closeEditModal" />
            <x-button label="Update Exam" class="btn-primary" wire:click="updateExam" />
        </x-slot:actions>
    </x-modal>

    {{-- Cancel Modal --}}
    <x-modal wire:model="showCancelModal" title="Cancel Exam" class="backdrop-blur">
        <div class="space-y-4">
            <div class="alert alert-warning">
                <x-icon name="o-exclamation-triangle" class="w-6 h-6" />
                <div>
                    <h3 class="font-bold">Are you sure?</h3>
                    <div class="text-xs">This will cancel the exam for all enrolled students. This action cannot be
                        undone.</div>
                </div>
            </div>

            <p class="text-sm text-gray-600">
                <strong>Exam ID:</strong> #{{ $exam->id }}<br>
                <strong>Course:</strong> {{ $exam->course->name ?? 'N/A' }}<br>
                <strong>Enrolled Students:</strong> {{ $exam->enrolled_students_count ?? 0 }}
            </p>
        </div>

        <x-slot:actions>
            <x-button label="Keep Exam" class="btn-ghost" wire:click="closeCancelModal" />
            <x-button label="Cancel Exam" class="btn-error" wire:click="cancelExam" />
        </x-slot:actions>
    </x-modal>

    {{-- Reschedule Modal --}}
    <x-modal wire:model="showRescheduleModal" title="Reschedule Exam" class="backdrop-blur">
        <div class="space-y-4">
            <x-datepicker label="New Date" wire:model="rescheduleDate" icon="o-calendar" />

            <div class="grid grid-cols-2 gap-3">
                <x-input label="Start Time" wire:model="rescheduleStartTime" type="time" icon="o-play" />
                <x-input label="End Time" wire:model="rescheduleEndTime" type="time" icon="o-stop" />
            </div>

            <x-input label="Duration (minutes)" wire:model="rescheduleDuration" type="number" icon="o-clock" />

            <div class="alert alert-info">
                <x-icon name="o-information-circle" class="w-6 h-6" />
                <div>
                    <h3 class="font-bold">Reschedule Notice</h3>
                    <div class="text-xs">All enrolled students will be notified of the new exam schedule.</div>
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" class="btn-ghost" wire:click="closeRescheduleModal" />
            <x-button label="Reschedule Exam" class="btn-primary" wire:click="rescheduleExam" />
        </x-slot:actions>
    </x-modal>
</div>

<script>
    function copyStudentCredentials(examUserId, examPassword) {
        const credentials = `Exam User ID: ${examUserId}\nPassword: ${examPassword}`;

        // Try to use the modern clipboard API first
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(credentials).then(() => {
                showToast('Student credentials copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy: ', err);
                fallbackCopyTextToClipboard(credentials);
            });
        } else {
            // Fallback for older browsers or non-secure contexts
            fallbackCopyTextToClipboard(credentials);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;

        // Avoid scrolling to bottom
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showToast('Student credentials copied to clipboard!', 'success');
            } else {
                showToast('Failed to copy credentials', 'error');
            }
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
            showToast('Failed to copy credentials', 'error');
        }

        document.body.removeChild(textArea);
    }

    function showToast(message, type) {
        // Create a simple toast notification
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
        toast.textContent = message;

        document.body.appendChild(toast);

        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
</script>
