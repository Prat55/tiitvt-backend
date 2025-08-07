<?php

use Mary\Traits\Toast;
use App\Models\Category;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Livewire\{WithFileUploads, WithPagination};

new class extends Component {
    use WithPagination, Toast, WithFileUploads;
    #[Title('All Categories')]
    public $headers;
    #[Url]
    public string $search = '';
    public $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public $categoryId;
    public $name,
        $slug,
        $image,
        $description,
        $is_active = true;
    public $editMode = false;
    public $showModal = false;
    public $cropConfig = [
        'aspectRatio' => 1,
    ];

    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'name', 'label' => 'Category Name', 'class' => 'w-48'], ['key' => 'slug', 'label' => 'Slug', 'class' => 'w-32'], ['key' => 'is_active', 'label' => 'Status', 'class' => 'w-20'], ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-32']];
    }

    public function rendering(View $view): void
    {
        $view->categories = Category::orderBy(...array_values($this->sortBy))
            ->where('name', 'like', "%$this->search%")
            ->paginate(20);
        $view->title('All Categories');
    }

    public function resetForm()
    {
        $this->name = $this->slug = $this->image = $this->description = '';
        $this->is_active = true;
        $this->editMode = false;
        $this->categoryId = null;
        $this->showModal = false;
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $category = Category::findOrFail($id);
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->image = $category->image;
        $this->description = $category->description;
        $this->is_active = $category->is_active;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function createCategory()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            if ($this->image) {
                $this->image = $this->image->store('category', 'public');
                $this->image = Storage::url($this->image);
            }

            Category::create([
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'image' => $this->image,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            $this->resetForm();
            $this->success('Category created successfully!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to create category. Please try again.', position: 'toast-bottom');
        }
    }

    public function updateCategory()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $this->categoryId,
        ]);

        try {
            $category = Category::findOrFail($this->categoryId);
            $category->update([
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'image' => $this->image,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
            $this->resetForm();
            $this->success('Category updated successfully!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to update category. Please try again.', position: 'toast-bottom');
        }
    }

    public function deleteCategory($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();
            $this->success('Category deleted successfully!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to delete category. Please try again.', position: 'toast-bottom');
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
            <h1 class="text-2xl font-bold">All Categories</h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>Dashboard</a>
                    </li>
                    <li>All Categories</li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-input placeholder="Search categories..." icon="o-magnifying-glass" wire:model.live.debounce="search" />
            <x-button label="Add Category" icon="o-plus" class="btn-primary" wire:click="openCreateModal" responsive />
        </div>
    </div>
    <hr class="mb-5">

    <x-table :headers="$headers" :rows="$categories" with-pagination :sort-by="$sortBy">
        @scope('cell_name', $category)
            <span class="font-medium">{{ $category->name }}</span>
        @endscope
        @scope('cell_slug', $category)
            <span class="text-xs">{{ $category->slug }}</span>
        @endscope
        @scope('cell_is_active', $category)
            <span class="badge badge-xs {{ $category->is_active ? 'badge-success' : 'badge-error' }}">
                {{ $category->is_active ? 'Active' : 'Inactive' }}
            </span>
        @endscope
        @scope('actions', $category)
            <div class="flex gap-1">
                <x-button icon="o-pencil" class="btn-ghost btn-xs" wire:click="openEditModal({{ $category->id }})"
                    title="Edit" />
                <x-button icon="o-trash" class="btn-ghost btn-xs" wire:click="deleteCategory({{ $category->id }})"
                    title="Delete" onclick="return confirm('Are you sure you want to delete this category?')" />
            </div>
        @endscope
        <x-slot:empty>
            <x-empty icon="o-no-symbol" message="No categories found" />
        </x-slot>
    </x-table>

    <!-- Category Modal -->
    <x-modal wire:model="showModal" title="{{ $editMode ? 'Edit Category' : 'Create Category' }}" class="backdrop-blur"
        separator>
        <form wire:submit.prevent="{{ $editMode ? 'updateCategory' : 'createCategory' }}">
            <div class="space-y-4">
                <x-input label="Category Name" wire:model.defer="name" placeholder="Enter category name" required />

                <x-file label="Image URL" wire:model.defer="image" placeholder="Enter image URL (optional)"
                    crop-after-change :crop-config="$cropConfig">
                    <img src="https://placehold.co/300" alt="Category Image" class="w-16 h-16 object-cover rounded-md">
                </x-file>

                <x-textarea label="Description" wire:model.defer="description"
                    placeholder="Enter description (optional)" />

                <x-checkbox label="Active" wire:model.defer="is_active" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showModal = false" />
                <x-button label="{{ $editMode ? 'Update' : 'Create' }}" class="btn-primary" type="submit" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
