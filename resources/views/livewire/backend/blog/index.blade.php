<?php

use App\Models\Blog;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Livewire\Attributes\{Layout};
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination, Toast;
    #[Title('All Blogs')]
    public $headers;
    #[Url]
    public string $search = '';

    public $sortBy = ['column' => 'created_at', 'direction' => 'desc'];
    public $showCreateModal = false;
    public $newBlogTitle = '';

    // boot
    public function boot(): void
    {
        $this->headers = [['key' => 'title', 'label' => 'Title', 'class' => 'w-64'], ['key' => 'status', 'label' => 'Status', 'class' => 'w-24'], ['key' => 'created_at', 'label' => 'Created', 'class' => 'w-32'], ['key' => 'updated_at', 'label' => 'Last Updated', 'class' => 'w-32']];
    }

    public function rendering(View $view): void
    {
        $view->blogs = Blog::orderBy(...array_values($this->sortBy))
            ->where('title', 'like', "%$this->search%")
            ->paginate(20);
        $view->title('All Blogs');
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->newBlogTitle = '';
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->newBlogTitle = '';
        $this->resetValidation();
    }

    public function createBlog(): void
    {
        $this->validate(
            [
                'newBlogTitle' => 'required|string|max:255|unique:blogs,title',
            ],
            [
                'newBlogTitle.required' => 'Blog title is required.',
                'newBlogTitle.unique' => 'A blog with this title already exists.',
            ],
        );

        $blog = Blog::create([
            'title' => $this->newBlogTitle,
            'slug' => Str::slug($this->newBlogTitle),
            'content' => '',
            'meta_description' => '',
            'is_active' => false,
        ]);

        $this->closeCreateModal();
        $this->success('Blog created successfully!', redirectTo: route('admin.blog.edit', $blog->id));
    }
};
?>

<div>
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                All Blogs
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        All Blogs
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-input placeholder="Search blogs..." icon="o-magnifying-glass" wire:model.live.debounce="search" />
            <x-button label="Create Blog" icon="o-plus" class="btn-primary inline-flex" responsive
                wire:click="openCreateModal" />
        </div>
    </div>
    <hr class="mb-5">

    <x-table :headers="$headers" :rows="$blogs" with-pagination :sort-by="$sortBy">
        @scope('cell_title', $blog)
            <div class="flex items-center gap-3">
                @if ($blog->image)
                    <img src="{{ Storage::url($blog->image) }}" alt="{{ $blog->title }}"
                        class="w-10 h-10 rounded-lg object-cover">
                @else
                    <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                        <x-icon name="o-document-text" class="w-5 h-5 text-primary" />
                    </div>
                @endif
                <div>
                    <div class="font-medium">{{ $blog->title }}</div>
                    @if ($blog->meta_description)
                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ Str::limit($blog->meta_description, 60) }}
                        </div>
                    @endif
                </div>
            </div>
        @endscope

        @scope('cell_status', $blog)
            <div class="flex items-center gap-2">
                @if ($blog->is_active)
                    <span class="badge badge-success badge-sm">Active</span>
                @else
                    <span class="badge badge-error badge-sm">Inactive</span>
                @endif
            </div>
        @endscope

        @scope('cell_created_at', $blog)
            <div class="text-sm">
                <div class="font-medium">{{ $blog->created_at->format('M d, Y') }}</div>
                <div class="text-xs text-gray-500">{{ $blog->created_at->format('h:i A') }}</div>
            </div>
        @endscope

        @scope('cell_updated_at', $blog)
            <div class="text-sm">
                <div class="font-medium">{{ $blog->updated_at->format('M d, Y') }}</div>
                <div class="text-xs text-gray-500">{{ $blog->updated_at->format('h:i A') }}</div>
            </div>
        @endscope

        @scope('actions', $blog)
            <div class="flex gap-1">
                <x-button icon="o-pencil" link="{{ route('admin.blog.edit', $blog->id) }}" class="btn-xs btn-ghost"
                    title="Edit Blog" responsive />
            </div>
        @endscope

        <x-slot:empty>
            <x-empty icon="o-document-text" message="No blogs found" />
        </x-slot>
    </x-table>

    <!-- Create Blog Modal -->
    <x-modal wire:model="showCreateModal" title="Create New Blog" class="backdrop-blur">
        <x-form wire:submit="createBlog">
            <x-input label="Blog Title" wire:model="newBlogTitle" placeholder="Enter blog title"
                icon="o-document-text" />
            <x-slot:actions>
                <x-button label="Cancel" wire:click="closeCreateModal" class="btn-ghost" />
                <x-button label="Create Blog" type="submit" class="btn-primary" spinner="createBlog" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
