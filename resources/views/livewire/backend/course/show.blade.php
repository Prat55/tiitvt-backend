<?php

use App\Models\Course;
use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new class extends Component {
    use Toast;

    #[Title('Course Details')]
    public Course $course;

    public array $lectures = [];
    public bool $showLectureModal = false;
    public bool $editLectureMode = false;
    public ?int $editingLectureIndex = null;
    public string $lectureTitle = '';
    public string $lectureUrl = '';

    public function mount(Course $course): void
    {
        $this->course = $course;
        $this->lectures = $this->normalizeLectures($this->course->lectures);
    }

    public function openCreateLectureModal(): void
    {
        $this->resetLectureForm();
        $this->editLectureMode = false;
        $this->showLectureModal = true;
    }

    public function openEditLectureModal(int $index): void
    {
        if (!isset($this->lectures[$index])) {
            $this->error('Lecture not found.', position: 'toast-bottom');
            return;
        }

        $lecture = $this->lectures[$index];

        $this->editLectureMode = true;
        $this->editingLectureIndex = $index;
        $this->lectureTitle = (string) ($lecture['title'] ?? '');
        $this->lectureUrl = (string) ($lecture['url'] ?? '');
        $this->showLectureModal = true;
    }

    public function saveLecture(): void
    {
        $this->validate([
            'lectureTitle' => 'required|string|max:255',
            'lectureUrl' => 'required|url|max:1000',
        ]);

        $lectureData = [
            'title' => trim($this->lectureTitle),
            'url' => trim($this->lectureUrl),
        ];

        $lectures = $this->lectures;

        if ($this->editLectureMode && $this->editingLectureIndex !== null && isset($lectures[$this->editingLectureIndex])) {
            $lectures[$this->editingLectureIndex] = $lectureData;
        } else {
            $lectures[] = $lectureData;
        }

        $this->persistLectures($lectures);
        $this->resetLectureForm();
        $this->showLectureModal = false;
        $this->success('Lecture saved successfully!', position: 'toast-bottom');
    }

    public function deleteLecture(int $index): void
    {
        if (!isset($this->lectures[$index])) {
            $this->error('Lecture not found.', position: 'toast-bottom');
            return;
        }

        $lectures = $this->lectures;
        array_splice($lectures, $index, 1);

        $this->persistLectures($lectures);
        $this->success('Lecture deleted successfully!', position: 'toast-bottom');
    }

    public function moveLectureUp(int $index): void
    {
        if ($index <= 0 || !isset($this->lectures[$index])) {
            return;
        }

        $lectures = $this->lectures;
        [$lectures[$index - 1], $lectures[$index]] = [$lectures[$index], $lectures[$index - 1]];

        $this->persistLectures($lectures);
    }

    public function moveLectureDown(int $index): void
    {
        if (!isset($this->lectures[$index]) || !isset($this->lectures[$index + 1])) {
            return;
        }

        $lectures = $this->lectures;
        [$lectures[$index], $lectures[$index + 1]] = [$lectures[$index + 1], $lectures[$index]];

        $this->persistLectures($lectures);
    }

    public function moveLectureToTop(int $index): void
    {
        if ($index <= 0 || !isset($this->lectures[$index])) {
            return;
        }

        $lectures = $this->lectures;
        $lecture = $lectures[$index];
        array_splice($lectures, $index, 1);
        array_unshift($lectures, $lecture);

        $this->persistLectures($lectures);
    }

    private function persistLectures(array $lectures): void
    {
        $lectures = $this->normalizeLectures($lectures);

        $this->course->update([
            'lectures' => $lectures,
        ]);

        $this->course->refresh();
        $this->lectures = $lectures;
    }

    private function normalizeLectures(mixed $lectures): array
    {
        if (!is_array($lectures)) {
            return [];
        }

        return collect($lectures)
            ->map(function ($lecture) {
                if (!is_array($lecture)) {
                    return null;
                }

                return [
                    'title' => trim((string) ($lecture['title'] ?? '')),
                    'url' => trim((string) ($lecture['url'] ?? '')),
                ];
            })
            ->filter(fn($lecture) => is_array($lecture) && $lecture['title'] !== '' && $lecture['url'] !== '')
            ->values()
            ->all();
    }

    private function resetLectureForm(): void
    {
        $this->editLectureMode = false;
        $this->editingLectureIndex = null;
        $this->lectureTitle = '';
        $this->lectureUrl = '';
        $this->resetValidation(['lectureTitle', 'lectureUrl']);
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Course Details
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.course.index') }}" wire:navigate>
                            Courses
                        </a>
                    </li>
                    <li>
                        {{ $course->name }}
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-button label="Edit Course" icon="o-pencil" class="btn-primary btn-outline"
                link="{{ route('admin.course.edit', $course->id) }}" responsive />
            <x-button label="Back to Courses" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.course.index') }}" responsive />
        </div>
    </div>
    <hr class="mb-5">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Course Image -->
            @if ($course->image)
                <x-card shadow>
                    <img src="{{ Storage::url($course->image) }}" alt="{{ $course->name }}"
                        class="w-full h-64 object-cover rounded-lg">
                </x-card>
            @endif

            <!-- Basic Information -->
            <x-card shadow>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Course Name</label>
                            <p class="text-base font-semibold text-gray-700 dark:text-gray-300">{{ $course->name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Slug</label>
                            <p class="text-base font-mono text-gray-700 dark:text-gray-300">{{ $course->slug }}</p>
                        </div>

                        @if ($course->duration)
                            <div>
                                <label class="text-sm font-medium text-gray-600">Duration</label>
                                <p class="text-base text-gray-700 dark:text-gray-300">{{ $course->duration }}</p>
                            </div>
                        @endif

                        <div>
                            <label class="text-sm font-medium text-gray-600">Status</label>
                            <div class="flex items-center gap-2">
                                @if ($course->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-error">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if ($course->meta_description)
                        <div>
                            <label class="text-sm font-bold">Meta Description</label>
                            <p class="text-base text-gray-700 dark:text-gray-300 mt-1">{{ $course->meta_description }}
                            </p>
                        </div>
                    @endif


                    @if ($course->description)
                        <div>
                            <label class="text-sm font-bold">Description</label>
                            <div class="mt-1">{!! $course->description !!}</div>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Pricing Information -->
            @if ($course->price || $course->mrp || $course->discount_percentage)
                <x-card shadow>
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-primary">Pricing Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if ($course->price)
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Selling Price</label>
                                    <p class="text-2xl font-bold text-success">{{ $course->formatted_price }}</p>
                                </div>
                            @endif

                            @if ($course->mrp)
                                <div>
                                    <label class="text-sm font-medium text-gray-600">MRP</label>
                                    <p class="text-lg text-gray-700 dark:text-gray-300">{{ $course->formatted_mrp }}
                                    </p>
                                </div>
                            @endif

                            @if ($course->discount_percentage)
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Discount</label>
                                    <p class="text-lg font-semibold text-error">-{{ $course->discount_percentage }}%
                                    </p>
                                </div>
                            @endif

                            @if ($course->mrp && $course->price && $course->mrp > $course->price)
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Savings</label>
                                    <p class="text-lg font-semibold text-success">
                                        ₹{{ number_format($course->mrp - $course->price, 2) }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-card>
            @endif

            <x-card shadow>
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-primary">Course Lectures</h3>
                        <x-button label="Add Lecture" icon="o-plus" class="btn-primary btn-sm"
                            wire:click="openCreateLectureModal" />
                    </div>

                    @if (count($lectures) > 0)
                        <div class="space-y-3">
                            @foreach ($lectures as $index => $lecture)
                                <x-card shadow class="bg-base-200">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-xs text-gray-500">Lecture {{ $index + 1 }}</div>
                                            <h4 class="font-semibold text-gray-800 dark:text-gray-200">
                                                {{ $lecture['title'] }}
                                            </h4>
                                            <a href="{{ $lecture['url'] }}" target="_blank" rel="noopener noreferrer"
                                                class="text-sm text-primary break-all hover:underline">
                                                {{ $lecture['url'] }}
                                            </a>
                                        </div>

                                        <div class="flex gap-1 shrink-0">
                                            @if ($index > 0)
                                                <x-button label="Top" class="btn-xs btn-outline btn-primary"
                                                    wire:click="moveLectureToTop({{ $index }})"
                                                    tooltip="Move lecture to top" />
                                                <x-button icon="o-arrow-up" class="btn-xs btn-ghost"
                                                    wire:click="moveLectureUp({{ $index }})"
                                                    tooltip="Move up" />
                                            @endif

                                            @if ($index < count($lectures) - 1)
                                                <x-button icon="o-arrow-down" class="btn-xs btn-ghost"
                                                    wire:click="moveLectureDown({{ $index }})"
                                                    tooltip="Move down" />
                                            @endif

                                            <x-dropdown right>
                                                <x-slot:trigger>
                                                    <x-button icon="o-ellipsis-horizontal-circle"
                                                        class="btn-xs btn-outline btn-primary" />
                                                </x-slot:trigger>

                                                <x-menu-item icon="o-pencil"
                                                    wire:click="openEditLectureModal({{ $index }})"
                                                    label="Edit Lecture"
                                                    spinner="openEditLectureModal({{ $index }})" />

                                                <x-menu-item icon="o-trash" class="text-error"
                                                    wire:click="deleteLecture({{ $index }})"
                                                    label="Delete Lecture" spinner="deleteLecture({{ $index }})"
                                                    wire:confirm="Are you sure you want to delete this lecture?" />
                                            </x-dropdown>
                                        </div>
                                    </div>
                                </x-card>
                            @endforeach
                        </div>
                    @else
                        <x-empty icon="o-video-camera" message="No lectures added yet" />
                    @endif
                </div>
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <x-card shadow>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-primary">Quick Stats</h3>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Students Enrolled</span>
                            <span class="font-semibold">{{ $course->students->count() }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Exams</span>
                            <span class="font-semibold">{{ $course->exams->count() }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Certificates</span>
                            <span class="font-semibold">{{ $course->certificates->count() }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Lectures</span>
                            <span class="font-semibold">{{ count($lectures) }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Course Actions -->
            <x-card shadow>
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-primary">Actions</h3>

                    <x-button label="Edit Course" icon="o-pencil" class="btn-primary w-full"
                        link="{{ route('admin.course.edit', $course->id) }}" responsive />

                    <x-button label="View Students" icon="o-users" class="btn-outline w-full"
                        link="{{ route('admin.student.index') }}?course={{ $course->id }}" responsive />

                    <x-button label="Manage Exams" icon="o-academic-cap" class="btn-outline w-full"
                        link="{{ route('admin.exam.index') }}?course={{ $course->id }}" responsive />
                </div>
            </x-card>

            <!-- Course Information -->
            <x-card shadow>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-primary">Course Information</h3>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Created:</span>
                            <span>{{ $course->created_at->format('M d, Y') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Updated:</span>
                            <span>{{ $course->updated_at->format('M d, Y') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Course ID:</span>
                            <span class="font-mono">{{ $course->id }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Categories -->
            <x-card shadow>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-primary">Categories</h3>

                    @if ($course->categories->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($course->categories as $category)
                                <span class="badge badge-primary badge-lg">{{ $category->name }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No categories assigned</p>
                    @endif
                </div>
            </x-card>
        </div>
    </div>

    <x-modal wire:model="showLectureModal" title="{{ $editLectureMode ? 'Edit Lecture' : 'Add Lecture' }}"
        class="backdrop-blur" separator>
        <x-form wire:submit.prevent="saveLecture">
            <div class="space-y-4">
                <x-input label="Lecture Title" wire:model.defer="lectureTitle"
                    placeholder="Enter lecture title (e.g. Introduction)" />

                <x-input label="Lecture URL" wire:model.defer="lectureUrl"
                    placeholder="https://www.youtube.com/watch?v=..." hint="YouTube, Vimeo, or any valid URL" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showLectureModal = false" />
                <x-button label="{{ $editLectureMode ? 'Update' : 'Add' }}" class="btn-primary" type="submit"
                    spinner="saveLecture" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
