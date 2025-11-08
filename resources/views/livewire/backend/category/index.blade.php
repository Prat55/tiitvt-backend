<?php

use Mary\Traits\Toast;
use App\Models\Category;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Storage;
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
        $image,
        $oldImage,
        $description,
        $is_active = true;
    public $editMode = false;
    public $showCategoryModal = false;
    public $cropConfig = [
        'aspectRatio' => 1,
    ];
    public $perPage = 20;

    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'image', 'label' => 'Image', 'class' => 'w-1'], ['key' => 'name', 'label' => 'Category Name'], ['key' => 'is_active', 'label' => 'Status', 'class' => 'w-1'], ['key' => 'created_at', 'label' => 'Created At', 'class' => 'w-1'], ['key' => 'updated_at', 'label' => 'Last Updated', 'class' => 'w-1']];
    }

    public function rendering(View $view): void
    {
        $view->categories = Category::orderBy(...array_values($this->sortBy))
            ->where('name', 'like', "%$this->search%")
            ->paginate($this->perPage);
        $view->title('All Categories');
    }

    public function resetForm()
    {
        $this->name = $this->slug = $this->image = $this->description = $oldImage = '';
        $this->is_active = true;
        $this->editMode = false;
        $this->categoryId = null;
        $this->showCategoryModal = false;
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showCategoryModal = true;
    }

    public function openEditModal($id)
    {
        $category = Category::findOrFail($id);
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->oldImage = $category->image;
        $this->description = $category->description;
        $this->is_active = $category->is_active;
        $this->editMode = true;
        $this->showCategoryModal = true;
    }

    public function createCategory()
    {
        try {
            $this->validate([
                'name' => 'required|string|max:100|unique:categories,name',
            ]);

            $category = new Category();
            $category->name = $this->name;

            // Generate unique slug
            $baseSlug = Str::slug($this->name);
            $slug = $baseSlug;
            $counter = 1;

            while (Category::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $category->slug = $slug;
            $category->description = $this->description;
            $category->is_active = $this->is_active;

            if ($this->image) {
                $path = $this->image->store('category', 'public');
                $this->image = '/storage/' . $path;
                $category->image = $this->image;
            }
            $category->save();

            $this->resetForm();
            $this->success('Category created successfully!', position: 'toast-bottom');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show proper error messages
            throw $e;
        } catch (\Exception $e) {
            $this->error('Failed to create category: ' . $e->getMessage(), position: 'toast-bottom');
        }
    }

    public function updateCategory()
    {
        try {
            $this->validate([
                'name' => 'required|string|max:100|unique:categories,name,' . $this->categoryId,
            ]);

            $category = Category::findOrFail($this->categoryId);
            $category->name = $this->name;

            // Generate unique slug (excluding current category)
            $baseSlug = Str::slug($this->name);
            $slug = $baseSlug;
            $counter = 1;

            while (Category::where('slug', $slug)->where('id', '!=', $this->categoryId)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $category->slug = $slug;
            $category->description = $this->description;
            $category->is_active = $this->is_active;
            if ($this->image) {
                if ($category->image) {
                    $imagePath = str_replace('/storage/', '', $category->image);
                    Storage::disk('public')->delete($imagePath);
                }

                $path = $this->image->store('category', 'public');
                $this->image = '/storage/' . $path;
                $category->image = $this->image;
            }
            $category->save();

            $this->resetForm();
            $this->success('Category updated successfully!', position: 'toast-bottom');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show proper error messages
            throw $e;
        } catch (\Exception $e) {
            $this->error('Failed to update category: ' . $e->getMessage(), position: 'toast-bottom');
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

    <x-table :headers="$headers" :rows="$categories" with-pagination :sort-by="$sortBy" per-page="perPage" :per-page-values="[20, 50, 100]">
        @scope('cell_image', $category)
            @if ($category->image)
                <div class="avatar select-none">
                    <div class="w-12 rounded-md">
                        <img src="{{ asset($category->image) }}" alt="{{ $category->name }}" />
                    </div>
                </div>
            @else
                <div class="select-none avatar avatar-placeholder">
                    <div class="w-8 rounded-md bg-neutral text-neutral-content">
                        <span class="text-xs">{{ substr($category->name, 0, 1) }}</span>
                    </div>
                </div>
            @endif
        @endscope
        @scope('cell_name', $category)
            <span class="font-medium">{{ $category->name }}</span>
        @endscope
        @scope('cell_is_active', $category)
            <x-badge value="{{ $category->is_active ? 'Active' : 'Inactive' }}"
                class="badge {{ $category->is_active ? 'badge-success' : 'badge-error' }}" />
        @endscope
        @scope('cell_created_at', $category)
            {{ $category->created_at->format('d M Y') }}
        @endscope
        @scope('cell_updated_at', $category)
            {{ $category->updated_at->format('d M Y') }}
        @endscope
        @scope('actions', $category)
            <div class="flex gap-1">
                <x-button icon="o-pencil" class="btn-primary btn-outline" wire:click="openEditModal({{ $category->id }})"
                    title="Edit" />
                <x-button icon="o-trash" class="btn-error btn-outline" wire:click="deleteCategory({{ $category->id }})"
                    title="Delete" wire:confirm="Are you sure you want to delete this category?" />
            </div>
        @endscope
        <x-slot:empty>
            <x-empty icon="o-no-symbol" message="No categories found" />
        </x-slot>
    </x-table>

    <!-- Category Modal -->
    <x-modal wire:model="showCategoryModal" title="{{ $editMode ? 'Edit Category' : 'Create Category' }}"
        class="backdrop-blur" separator>
        <x-form wire:submit.prevent="{{ $editMode ? 'updateCategory' : 'createCategory' }}">
            <div class="space-y-4">
                <x-input label="Category Name" wire:model.defer="name" placeholder="Enter category name" />

                <x-file label="Image URL" wire:model.defer="image" placeholder="Enter image URL (optional)"
                    crop-after-change :crop-config="$cropConfig">
                    <img src="{{ $oldImage ? asset($oldImage) : 'https://placehold.co/300' }}" alt="Category Image"
                        class="w-16 h-16 object-cover rounded-md">
                </x-file>

                <x-textarea label="Description" wire:model.defer="description"
                    placeholder="Enter description (optional)" />

                <x-checkbox label="Active" wire:model.defer="is_active" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showCategoryModal = false" />
                <x-button label="{{ $editMode ? 'Update' : 'Create' }}" class="btn-primary" type="submit"
                    spinner="{{ $editMode ? 'updateCategory' : 'createCategory' }}" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
