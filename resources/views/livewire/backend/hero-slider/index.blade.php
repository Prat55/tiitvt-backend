<?php

use App\Models\HeroSlider;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast, WithFileUploads;

    #[Title('Hero Sliders')]
    public $headers;

    #[Url]
    public string $search = '';

    public $sortBy = ['column' => 'sort_order', 'direction' => 'asc'];
    public $perPage = 20;

    public $sliderId;
    public $title = '';
    public $subtitle = '';
    public $link = '';
    public $image;
    public $oldImage;
    public $sort_order = 0;
    public $is_active = true;

    public $editMode = false;
    public $showSliderModal = false;
    public $cropConfig = [
        'aspectRatio' => 2,
    ];

    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'image', 'label' => 'Image', 'class' => 'w-1'], ['key' => 'title', 'label' => 'Title'], ['key' => 'subtitle', 'label' => 'Subtitle'], ['key' => 'sort_order', 'label' => 'Order', 'class' => 'w-1'], ['key' => 'is_active', 'label' => 'Status', 'class' => 'w-1'], ['key' => 'updated_at', 'label' => 'Updated', 'class' => 'w-1']];
    }

    public function rendering(View $view): void
    {
        $view->sliders = HeroSlider::orderBy(...array_values($this->sortBy))
            ->where(function ($query) {
                $query
                    ->where('title', 'like', "%$this->search%")
                    ->orWhere('subtitle', 'like', "%$this->search%")
                    ->orWhere('link', 'like', "%$this->search%");
            })
            ->paginate($this->perPage);

        $view->title('Hero Sliders');
    }

    public function resetForm(): void
    {
        $this->sliderId = null;
        $this->title = '';
        $this->subtitle = '';
        $this->link = '';
        $this->image = null;
        $this->oldImage = null;
        $this->sort_order = 0;
        $this->is_active = true;
        $this->editMode = false;
        $this->showSliderModal = false;
        $this->resetValidation();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showSliderModal = true;
    }

    public function openEditModal(int $id): void
    {
        $slider = HeroSlider::findOrFail($id);

        $this->sliderId = $slider->id;
        $this->title = $slider->title;
        $this->subtitle = $slider->subtitle ?? '';
        $this->link = $slider->link ?? '';
        $this->oldImage = $slider->image;
        $this->sort_order = $slider->sort_order;
        $this->is_active = $slider->is_active;
        $this->image = null;
        $this->editMode = true;
        $this->showSliderModal = true;
    }

    public function createSlider(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:255',
            'image' => 'required|image|max:4096',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $slider = new HeroSlider();
        $slider->title = $this->title;
        $slider->subtitle = $this->subtitle;
        $slider->link = $this->link;
        $slider->sort_order = $this->sort_order ?: 0;
        $slider->is_active = $this->is_active;

        $path = $this->image->store('hero-sliders', 'public');
        $slider->image = '/storage/' . $path;

        $slider->save();

        $this->resetForm();
        $this->success('Hero slider created successfully!', position: 'toast-bottom');
    }

    public function updateSlider(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:4096',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $slider = HeroSlider::findOrFail($this->sliderId);
        $slider->title = $this->title;
        $slider->subtitle = $this->subtitle;
        $slider->link = $this->link;
        $slider->sort_order = $this->sort_order ?: 0;
        $slider->is_active = $this->is_active;

        if ($this->image) {
            if ($slider->image) {
                $imagePath = str_replace('/storage/', '', $slider->image);
                Storage::disk('public')->delete($imagePath);
            }

            $path = $this->image->store('hero-sliders', 'public');
            $slider->image = '/storage/' . $path;
        }

        $slider->save();

        $this->resetForm();
        $this->success('Hero slider updated successfully!', position: 'toast-bottom');
    }

    public function deleteSlider(int $id): void
    {
        try {
            $slider = HeroSlider::findOrFail($id);

            if ($slider->image) {
                $imagePath = str_replace('/storage/', '', $slider->image);
                Storage::disk('public')->delete($imagePath);
            }

            $slider->delete();
            $this->success('Hero slider deleted successfully!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to delete hero slider. Please try again.', position: 'toast-bottom');
        }
    }
}; ?>

@section('cdn')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
@endsection

<div>
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">Hero Sliders</h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>Dashboard</a>
                    </li>
                    <li>Hero Sliders</li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-input placeholder="Search sliders..." icon="o-magnifying-glass" wire:model.live.debounce="search" />
            <x-button label="Add Slider" icon="o-plus" class="btn-primary" wire:click="openCreateModal" responsive />
        </div>
    </div>
    <hr class="mb-5">

    <x-table :headers="$headers" :rows="$sliders" with-pagination :sort-by="$sortBy" per-page="perPage" :per-page-values="[20, 50, 100]">
        @scope('cell_image', $slider)
            <div class="avatar select-none">
                <div class="w-14 h-10 rounded-md">
                    <img src="{{ asset($slider->image) }}" alt="{{ $slider->title }}" class="object-cover" />
                </div>
            </div>
        @endscope

        @scope('cell_title', $slider)
            <span class="font-medium">{{ $slider->title }}</span>
        @endscope

        @scope('cell_subtitle', $slider)
            <span class="text-sm text-gray-600">{{ $slider->subtitle ?: '-' }}</span>
        @endscope

        @scope('cell_sort_order', $slider)
            <x-badge :value="$slider->sort_order" class="badge-ghost" />
        @endscope

        @scope('cell_is_active', $slider)
            @if ($slider->is_active)
                <x-badge value="Active" class="badge-success" />
            @else
                <x-badge value="Inactive" class="badge-error" />
            @endif
        @endscope

        @scope('cell_updated_at', $slider)
            {{ $slider->updated_at->format('d M Y') }}
        @endscope

        @scope('actions', $slider)
            <div class="flex gap-1">
                <x-button icon="o-pencil" class="btn-primary btn-outline" wire:click="openEditModal({{ $slider->id }})"
                    title="Edit" />
                <x-button icon="o-trash" class="btn-error btn-outline" wire:click="deleteSlider({{ $slider->id }})"
                    title="Delete" wire:confirm="Are you sure you want to delete this slider?" />
            </div>
        @endscope

        <x-slot:empty>
            <x-empty icon="o-photo" message="No hero sliders found" />
        </x-slot>
    </x-table>

    <x-modal wire:model="showSliderModal" title="{{ $editMode ? 'Edit Hero Slider' : 'Create Hero Slider' }}"
        class="backdrop-blur" separator>
        <x-form wire:submit.prevent="{{ $editMode ? 'updateSlider' : 'createSlider' }}">
            <div class="space-y-4">
                <x-input label="Title" wire:model.defer="title" placeholder="Enter title" />
                <x-input label="Subtitle" wire:model.defer="subtitle" placeholder="Enter subtitle" />
                <x-input label="Link" wire:model.defer="link" placeholder="Enter link (optional)" />
                <x-input label="Sort Order" type="number" min="0" wire:model.defer="sort_order"
                    placeholder="0" />
                <x-file label="Slider Image" wire:model.defer="image" placeholder="Upload slider image"
                    crop-after-change :crop-config="$cropConfig">
                    <img src="{{ $oldImage ? asset($oldImage) : 'https://placehold.co/1200x600' }}" alt="Slider Image"
                        class="w-24 h-16 object-cover rounded-md">
                </x-file>
                <x-checkbox label="Active" wire:model.defer="is_active" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showSliderModal = false" />
                <x-button label="{{ $editMode ? 'Update' : 'Create' }}" class="btn-primary" type="submit"
                    spinner="{{ $editMode ? 'updateSlider' : 'createSlider' }}" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
