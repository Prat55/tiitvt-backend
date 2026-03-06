<?php

use App\Models\Category;
use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use Toast, WithFileUploads;

    #[Title('Category Details')]
    public Category $category;

    public array $lectures = [];
    public bool $showLectureModal = false;
    public bool $editLectureMode = false;
    public ?int $editingLectureIndex = null;
    public string $lectureTitle = '';
    public $lectureVideo = null;
    public string $lectureDescription = '';

    public array $materials = [];
    public bool $showMaterialModal = false;
    public bool $editMaterialMode = false;
    public ?int $editingMaterialIndex = null;
    public string $materialName = '';
    public $materialFile = null;
    public string $materialDescription = '';

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->lectures = $this->normalizeLectures($this->category->lectures);
        $this->materials = $this->normalizeMaterials($this->category->materials);
    }

    // Lecture Methods
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
        $this->lectureDescription = (string) ($lecture['description'] ?? '');
        $this->showLectureModal = true;
    }
    public function saveLecture(): void
    {
        $this->validate([
            'lectureTitle' => 'required|string|max:255',
            'lectureDescription' => 'nullable|string|max:5000',
        ]);

        if (!$this->editLectureMode) {
            $this->validate([
                'lectureVideo' => 'required|file|max:512000|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/x-flv,video/webm',
            ]);
        } elseif ($this->lectureVideo) {
            $this->validate([
                'lectureVideo' => 'file|max:512000|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/x-flv,video/webm',
            ]);
        }

        $lecturePath = null;
        if ($this->lectureVideo && is_object($this->lectureVideo)) {
            // Delete old video if editing
            if ($this->editLectureMode && !empty($this->lectures[$this->editingLectureIndex]['path'])) {
                Storage::disk('public')->delete($this->lectures[$this->editingLectureIndex]['path']);
            }

            $lecturePath = $this->lectureVideo->store('category-lectures', 'public');
        } elseif ($this->editLectureMode && isset($this->lectures[$this->editingLectureIndex]['path'])) {
            $lecturePath = $this->lectures[$this->editingLectureIndex]['path'];
        }

        $lectureData = [
            'title' => trim($this->lectureTitle),
            'path' => $lecturePath,
            'description' => trim($this->lectureDescription),
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
        $lecture = $lectures[$index];

        // Delete video file if exists
        if (!empty($lecture['path'])) {
            Storage::disk('public')->delete($lecture['path']);
        }

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

        $this->category->update([
            'lectures' => $lectures,
        ]);

        $this->category->refresh();
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
                    'path' => trim((string) ($lecture['path'] ?? '')),
                    'description' => trim((string) ($lecture['description'] ?? '')),
                ];
            })
            ->filter(fn($lecture) => is_array($lecture) && $lecture['title'] !== '' && $lecture['path'] !== '')
            ->values()
            ->all();
    }

    private function resetLectureForm(): void
    {
        $this->editLectureMode = false;
        $this->editingLectureIndex = null;
        $this->lectureTitle = '';
        $this->lectureVideo = null;
        $this->lectureDescription = '';
        $this->resetValidation(['lectureTitle', 'lectureVideo', 'lectureDescription']);
    }

    // Material Methods
    public function openCreateMaterialModal(): void
    {
        $this->resetMaterialForm();
        $this->editMaterialMode = false;
        $this->showMaterialModal = true;
    }

    public function openEditMaterialModal(int $index): void
    {
        if (!isset($this->materials[$index])) {
            $this->error('Material not found.', position: 'toast-bottom');
            return;
        }

        $material = $this->materials[$index];

        $this->editMaterialMode = true;
        $this->editingMaterialIndex = $index;
        $this->materialName = (string) ($material['name'] ?? '');
        $this->materialDescription = (string) ($material['description'] ?? '');
        $this->materialFile = null;
        $this->showMaterialModal = true;
    }

    public function saveMaterial(): void
    {
        $this->validate([
            'materialName' => 'required|string|max:255',
            'materialDescription' => 'nullable|string|max:1000',
        ]);

        if (!$this->editMaterialMode) {
            $this->validate([
                'materialFile' => 'required|file|max:102400', // 100MB
            ]);
        } elseif ($this->materialFile) {
            $this->validate([
                'materialFile' => 'file|max:102400', // 100MB
            ]);
        }

        $materials = $this->materials;

        $materialPath = null;
        if ($this->materialFile && is_object($this->materialFile)) {
            // Delete old file if editing
            if ($this->editMaterialMode && isset($this->materials[$this->editingMaterialIndex]['path'])) {
                Storage::disk('public')->delete($this->materials[$this->editingMaterialIndex]['path']);
            }

            $materialPath = $this->materialFile->store('category-materials', 'public');
        } elseif ($this->editMaterialMode && isset($this->materials[$this->editingMaterialIndex]['path'])) {
            $materialPath = $this->materials[$this->editingMaterialIndex]['path'];
        }

        $materialData = [
            'name' => trim($this->materialName),
            'description' => trim($this->materialDescription),
            'path' => $materialPath,
            'file_name' => $this->materialFile?->getClientOriginalName() ?? ($materials[$this->editingMaterialIndex]['file_name'] ?? ''),
            'file_size' => $this->materialFile?->getSize() ?? ($materials[$this->editingMaterialIndex]['file_size'] ?? 0),
            'mime_type' => $this->materialFile?->getMimeType() ?? ($materials[$this->editingMaterialIndex]['mime_type'] ?? ''),
        ];

        if ($this->editMaterialMode && $this->editingMaterialIndex !== null && isset($materials[$this->editingMaterialIndex])) {
            $materials[$this->editingMaterialIndex] = $materialData;
        } else {
            $materials[] = $materialData;
        }

        $this->persistMaterials($materials);
        $this->resetMaterialForm();
        $this->showMaterialModal = false;
        $this->success('Material saved successfully!', position: 'toast-bottom');
    }

    public function deleteMaterial(int $index): void
    {
        if (!isset($this->materials[$index])) {
            $this->error('Material not found.', position: 'toast-bottom');
            return;
        }

        // Delete file from storage
        if (!empty($this->materials[$index]['path'])) {
            Storage::disk('public')->delete($this->materials[$index]['path']);
        }

        $materials = $this->materials;
        array_splice($materials, $index, 1);

        $this->persistMaterials($materials);
        $this->success('Material deleted successfully!', position: 'toast-bottom');
    }

    private function persistMaterials(array $materials): void
    {
        $materials = $this->normalizeMaterials($materials);

        $this->category->update([
            'materials' => $materials,
        ]);

        $this->category->refresh();
        $this->materials = $materials;
    }

    private function normalizeMaterials(mixed $materials): array
    {
        if (!is_array($materials)) {
            return [];
        }

        return collect($materials)
            ->map(function ($material) {
                if (!is_array($material)) {
                    return null;
                }

                return [
                    'name' => trim((string) ($material['name'] ?? '')),
                    'description' => trim((string) ($material['description'] ?? '')),
                    'path' => trim((string) ($material['path'] ?? '')),
                    'file_name' => trim((string) ($material['file_name'] ?? '')),
                    'file_size' => (int) ($material['file_size'] ?? 0),
                    'mime_type' => trim((string) ($material['mime_type'] ?? '')),
                ];
            })
            ->filter(fn($material) => is_array($material) && $material['name'] !== '' && $material['path'] !== '')
            ->values()
            ->all();
    }

    private function resetMaterialForm(): void
    {
        $this->editMaterialMode = false;
        $this->editingMaterialIndex = null;
        $this->materialName = '';
        $this->materialDescription = '';
        $this->materialFile = null;
        $this->resetValidation(['materialName', 'materialDescription', 'materialFile']);
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Category Details
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.category.index') }}" wire:navigate>
                            Categories
                        </a>
                    </li>
                    <li>
                        {{ $category->name }}
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-button label="Go Back" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.category.index') }}" responsive />
        </div>
    </div>
    <hr class="mb-5">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Category Image -->
            @if ($category->image)
                <x-card shadow>
                    <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}"
                        class="w-full h-64 object-cover rounded-lg">
                </x-card>
            @endif

            <!-- Basic Information -->
            <x-card shadow>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Category Name</label>
                            <p class="text-base font-semibold text-gray-700 dark:text-gray-300">{{ $category->name }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Slug</label>
                            <p class="text-base font-mono text-gray-700 dark:text-gray-300">{{ $category->slug }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Status</label>
                            <div class="flex items-center gap-2">
                                @if ($category->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-error">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($category->description)
                        <div>
                            <label class="text-sm font-bold">Description</label>
                            <div class="mt-1">{!! $category->description !!}</div>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Category Lectures -->
            <x-card shadow>
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-primary">Category Lectures</h3>
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

                                            <x-card class="my-3">
                                                @if (!empty($lecture['path']))
                                                    <div
                                                        class="mt-2 w-full aspect-video bg-black rounded-lg overflow-hidden relative">
                                                        <video class="w-full h-full" controls preload="metadata">
                                                            <source
                                                                src="{{ route('api.videos.stream', ['path' => base64_encode($lecture['path'])]) }}"
                                                                type="video/mp4">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    </div>
                                                @else
                                                    <div class="p-4 text-center text-gray-400">
                                                        No video file attached
                                                    </div>
                                                @endif
                                            </x-card>

                                            <x-card class="mt-2">
                                                @if (!empty($lecture['description']))
                                                    @php $desc = $lecture['description']; @endphp
                                                    @if (strlen($desc) > 500)
                                                        <div x-data="{ expanded: false }">
                                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                                <span
                                                                    x-show="!expanded">{{ Str::limit($desc, 500) }}</span>
                                                                <span x-show="expanded"
                                                                    x-cloak>{{ $desc }}</span>
                                                            </p>
                                                            <button @click="expanded = !expanded"
                                                                class="text-xs text-primary hover:underline mt-1 focus:outline-none"
                                                                x-text="expanded ? 'Show Less' : 'Read More'"></button>
                                                        </div>
                                                    @else
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                            {{ $desc }}
                                                        </p>
                                                    @endif
                                                @endif
                                            </x-card>
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

            <!-- Associated Courses -->
            <x-card shadow>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-primary">Associated Courses</h3>

                    @if ($category->courses->count() > 0)
                        <div class="space-y-2">
                            @foreach ($category->courses as $course)
                                <div
                                    class="flex items-center justify-between p-3 bg-base-100 rounded-lg border border-base-300">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">{{ $course->name }}
                                        </h4>
                                        <p class="text-xs text-gray-500">{{ $course->slug }}</p>
                                    </div>
                                    <x-button label="View" icon="o-arrow-right" class="btn-outline btn-xs"
                                        link="{{ route('admin.course.show', $course->id) }}" />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No courses assigned to this category</p>
                    @endif
                </div>
            </x-card>

            <!-- Category Materials -->
            <x-card shadow>
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-primary">Study Materials</h3>
                        <x-button label="Add Material" icon="o-plus" class="btn-primary btn-sm"
                            wire:click="openCreateMaterialModal" />
                    </div>

                    @if (count($materials) > 0)
                        <div class="space-y-3">
                            @foreach ($materials as $index => $material)
                                <x-card shadow class="bg-base-200">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <div class="text-2xl">
                                                    @php
                                                        $mimeType = $material['mime_type'] ?? '';
                                                        if (str_contains($mimeType, 'pdf')) {
                                                            echo '📄';
                                                        } elseif (
                                                            str_contains($mimeType, 'spreadsheet') ||
                                                            str_contains($mimeType, 'sheet')
                                                        ) {
                                                            echo '📊';
                                                        } elseif (
                                                            str_contains($mimeType, 'zip') ||
                                                            str_contains($mimeType, 'compress')
                                                        ) {
                                                            echo '📦';
                                                        } elseif (str_contains($mimeType, 'word')) {
                                                            echo '📝';
                                                        } elseif (
                                                            str_contains($mimeType, 'presentation') ||
                                                            str_contains($mimeType, 'powerpoint')
                                                        ) {
                                                            echo '🎨';
                                                        } else {
                                                            echo '📁';
                                                        }
                                                    @endphp
                                                </div>
                                                <div>
                                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200">
                                                        {{ $material['name'] }}
                                                    </h4>
                                                    <p class="text-xs text-gray-500">
                                                        {{ $material['file_name'] }} •
                                                        {{ round($material['file_size'] / 1024 / 1024, 2) }} MB
                                                    </p>
                                                </div>
                                            </div>

                                            @if (!empty($material['description']))
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                    {{ $material['description'] }}
                                                </p>
                                            @endif
                                        </div>

                                        <div class="flex gap-1 shrink-0">
                                            <x-button icon="o-arrow-down-tray" class="btn-xs btn-ghost"
                                                wire:click="$dispatch('download-material', { path: '{{ $material['path'] }}', name: '{{ $material['file_name'] }}' })"
                                                tooltip="Download" />

                                            <x-dropdown right>
                                                <x-slot:trigger>
                                                    <x-button icon="o-ellipsis-horizontal-circle"
                                                        class="btn-xs btn-outline btn-primary" />
                                                </x-slot:trigger>

                                                <x-menu-item icon="o-pencil"
                                                    wire:click="openEditMaterialModal({{ $index }})"
                                                    label="Edit Material"
                                                    spinner="openEditMaterialModal({{ $index }})" />

                                                <x-menu-item icon="o-trash" class="text-error"
                                                    wire:click="deleteMaterial({{ $index }})"
                                                    label="Delete Material"
                                                    spinner="deleteMaterial({{ $index }})"
                                                    wire:confirm="Are you sure you want to delete this material?" />
                                            </x-dropdown>
                                        </div>
                                    </div>
                                </x-card>
                            @endforeach
                        </div>
                    @else
                        <x-empty icon="o-document" message="No materials added yet" />
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
                            <span class="text-sm text-gray-600">Total Courses</span>
                            <span class="font-semibold">{{ $category->courses->count() }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Lectures</span>
                            <span class="font-semibold">{{ count($lectures) }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Materials</span>
                            <span class="font-semibold">{{ count($materials) }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Questions</span>
                            <span class="font-semibold">{{ $category->questions->count() }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Category Actions -->
            <x-card shadow>
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-primary">Actions</h3>

                    <x-button label="View Courses" icon="o-book-open" class="btn-outline w-full"
                        link="{{ route('admin.course.index') }}?category={{ $category->id }}" responsive />

                    <x-button label="Manage Questions" icon="o-question-mark-circle" class="btn-outline w-full"
                        link="{{ route('admin.question.index') }}?category={{ $category->id }}" responsive />
                </div>
            </x-card>

            <!-- Category Information -->
            <x-card shadow>
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-primary">Category Information</h3>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Created:</span>
                            <span>{{ $category->created_at->format('M d, Y') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Updated:</span>
                            <span>{{ $category->updated_at->format('M d, Y') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Category ID:</span>
                            <span class="font-mono">{{ $category->id }}</span>
                        </div>
                    </div>
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

                @if (!$editLectureMode)
                    <div>
                        <x-file label="Upload Video File" wire:model="lectureVideo"
                            hint="MP4, WebM and other formats (Max: 500MB)" />
                    </div>
                @else
                    @if (!empty($lectures[$editingLectureIndex]['path']))
                        <div class="p-4 bg-base-200 rounded-lg mb-4">
                            <p class="text-sm text-gray-600">Current Video: <span
                                    class="font-semibold">{{ basename($lectures[$editingLectureIndex]['path']) }}</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Leave empty to keep current video</p>
                        </div>
                    @endif
                    <div>
                        <x-file label="Upload New Video (Optional)" wire:model="lectureVideo"
                            hint="Leave empty to keep current video" />
                    </div>
                @endif

                <x-textarea label="Description (Optional)" wire:model.defer="lectureDescription"
                    placeholder="Add a description for this lecture..." rows="4" hint="Max 5000 characters" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showLectureModal = false" />
                <x-button label="{{ $editLectureMode ? 'Update' : 'Add' }}" class="btn-primary" type="submit"
                    spinner="saveLecture" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <x-modal wire:model="showMaterialModal" title="{{ $editMaterialMode ? 'Edit Material' : 'Add Material' }}"
        class="backdrop-blur" separator>
        <x-form wire:submit.prevent="saveMaterial">
            <div class="space-y-4">
                <x-input label="Material Name" wire:model.defer="materialName"
                    placeholder="e.g. Python Basics Guide" />

                <x-textarea label="Description (Optional)" wire:model.defer="materialDescription"
                    placeholder="Add a description for this material..." rows="3" />

                @if (!$editMaterialMode)
                    <div>
                        <x-file label="Upload File" wire:model="materialFile"
                            hint="PDF, XLSX, ZIP, DOC, DOCX, PPT, PPTX, and other formats (Max: 100MB)" />
                    </div>
                @else
                    <div class="p-4 bg-base-200 rounded-lg">
                        <p class="text-sm text-gray-600">Current File: <span
                                class="font-semibold">{{ $materials[$editingMaterialIndex]['file_name'] ?? 'N/A' }}</span>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Leave file empty to keep current file</p>
                    </div>
                    <div>
                        <x-file label="Upload New File (Optional)" wire:model="materialFile"
                            hint="Leave empty to keep current file" />
                    </div>
                @endif
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showMaterialModal = false" />
                <x-button label="{{ $editMaterialMode ? 'Update' : 'Add' }}" class="btn-primary" type="submit"
                    spinner="saveMaterial" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
