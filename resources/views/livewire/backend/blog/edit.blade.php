<?php

use Mary\Traits\Toast;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Layout, Title};
use App\Models\{Blog, Tag};

new class extends Component {
    use WithFileUploads, Toast;

    #[Title('Edit Blog')]
    public Blog $blog;

    // Form properties
    public string $title = '';
    public string $slug = '';
    public string $content = '';
    public string $meta_description = '';
    public bool $is_active = false;
    public $blog_image;
    public array $tag_ids = [];
    public bool $addTagModal = false;
    public string $newTagName = '';
    public string $editTagName = '';
    public int $editTagId = 0;
    public bool $editTagModal = false;
    public bool $deleteTagModal = false;
    public int $deleteTagId = 0;

    public $config = [
        'aspectRatio' => 16 / 9,
    ];

    // Computed properties
    public function getTagsProperty()
    {
        return Tag::active()
            ->get(['id', 'name'])
            ->toArray();
    }

    // Validation rules
    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:blogs,title,' . $this->blog->id,
            'content' => 'nullable|string',
            'meta_description' => 'nullable|string|max:150',
            'is_active' => 'boolean',
            'blog_image' => 'nullable|image|max:2048',
            'tag_ids' => 'array',
            'tag_ids.*' => 'exists:tags,id',
        ];
    }

    // Validation messages
    protected function messages(): array
    {
        return [
            'title.required' => 'Blog title is required.',
            'title.unique' => 'A blog with this title already exists.',
            'meta_description.max' => 'Meta description must not exceed 150 characters.',
            'blog_image.image' => 'The file must be an image.',
            'blog_image.max' => 'The image size must not exceed 2MB.',
            'tag_ids.array' => 'The tags must be an array.',
            'tag_ids.*.exists' => 'The selected tag is invalid.',
        ];
    }

    public function mount(Blog $blog): void
    {
        $this->blog = $blog;
        $this->title = $blog->title;
        $this->content = $blog->content;
        $this->meta_description = $blog->meta_description;
        $this->is_active = $blog->is_active;
        $this->tag_ids = $blog->tags()->pluck('tags.id')->toArray();
        $this->newTagName = '';
        $this->editTagName = '';
        $this->editTagId = 0;
        $this->addTagModal = false;
        $this->editTagModal = false;
        $this->deleteTagModal = false;
        $this->deleteTagId = 0;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $validated['slug'] = $this->slug ?: Str::slug($this->title);

        // Handle image upload
        if ($this->blog_image) {
            // Delete old image if exists
            if ($this->blog->image && Storage::exists($this->blog->image)) {
                Storage::delete($this->blog->image);
            }

            $validated['image'] = $this->blog_image->store('blogs', 'public');
        }

        // Remove the blog_image from validated data as it's not a database field
        unset($validated['blog_image']);

        // Sync tags
        $this->blog->tags()->sync($this->tag_ids);

        $this->blog->update($validated);

        $this->success('Blog updated successfully!', position: 'toast-bottom');
    }

    // Auto-generate slug when title changes
    public function updatedTitle(): void
    {
        if ($this->title && empty($this->slug)) {
            $this->slug = Str::slug($this->title);
        }
    }

    // Remove uploaded file
    public function removeFile($property): void
    {
        $this->$property = null;
        $this->success('File removed successfully!', position: 'toast-bottom');
    }

    public function closeAddTagModal(): void
    {
        $this->addTagModal = false;
        $this->newTagName = '';
    }

    public function addTag(): void
    {
        $this->validate(['newTagName' => 'required|string|max:255|unique:tags,name']);

        $tag = Tag::create([
            'name' => $this->newTagName,
            'slug' => Str::slug($this->newTagName),
            'is_active' => true,
        ]);

        $this->tag_ids[] = $tag->id;
        $this->closeAddTagModal();
        $this->success('Tag added successfully!', position: 'toast-bottom');
    }
}; ?>

@section('cdn')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/tinymce.min.js" referrerpolicy="origin"></script>
@endsection

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Edit Blog
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.blog.index') }}" wire:navigate>
                            Blogs
                        </a>
                    </li>
                    <li>
                        Edit Blog
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-button label="Back to Blogs" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.blog.index') }}" responsive />
        </div>
    </div>
    <hr class="mb-5">

    <x-card shadow>
        <form wire:submit="save" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>
                </div>

                <x-input label="Blog Title" wire:model="title" placeholder="Enter blog title" icon="o-document-text" />

                <x-textarea label="Meta Description" wire:model="meta_description"
                    placeholder="Enter meta description for SEO" icon="o-tag" maxlength="150"
                    hint="Maximum 150 characters for SEO purposes" class="md:col-span-2" />
            </div>

            <!-- Tags -->
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-primary">Tags</h3>
                    <p class="text-sm text-gray-600 mt-1">Select one or more tags for this blog</p>
                </div>

                <x-choices-offline label="Blog Tags" wire:model="tag_ids" placeholder="Select tags" icon="o-tag"
                    :options="$this->tags" multiple searchable clearable>
                    <x-slot:append>
                        <x-button label="Add Tag" icon="o-plus" class="btn-primary btn-soft join-item"
                            @click="$wire.addTagModal = true" />
                    </x-slot:append>
                </x-choices-offline>
            </div>

            <!-- Blog Image -->
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-primary">Blog Image</h3>
                    <p class="text-sm text-gray-600 mt-1">Upload an image to represent this blog</p>
                </div>

                <x-file wire:model="blog_image" accept="image/*" placeholder="Upload blog image" icon="o-photo"
                    hint="Max 2MB, Recommended: 16:9 aspect ratio" crop-after-change :crop-config="$config">
                    @if ($blog->image)
                        <img src="{{ Storage::url($blog->image) }}" alt="Blog Image"
                            class="w-full h-48 object-cover rounded-lg">
                    @else
                        <img src="https://placehold.co/400x225?text=Blog+Image" alt="Blog Image"
                            class="w-full h-48 object-cover rounded-lg">
                    @endif
                </x-file>
            </div>

            <!-- Content -->
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-primary">Blog Content</h3>
                    <p class="text-sm text-gray-600 mt-1">Write the main content of your blog</p>
                </div>

                <x-editor wire:model="content" label="Content" hint="The full blog content" />
            </div>

            <!-- Status -->
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-primary">Status</h3>
                </div>

                <x-toggle label="Active Blog" wire:model="is_active"
                    hint="Inactive blogs won't be visible to visitors" />
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <x-button label="Cancel" icon="o-x-mark" class="btn-error btn-soft btn-sm"
                    link="{{ route('admin.blog.index') }}" responsive />
                <x-button label="Update Blog" icon="o-check" class="btn-primary btn-sm btn-soft" type="submit"
                    spinner="save" responsive />
            </div>
        </form>
    </x-card>

    <!-- Add Tag Modal -->
    <x-modal wire:model="addTagModal" title="Add Tag" class="backdrop-blur">
        <x-form wire:submit="addTag">
            <x-input label="Tag Name" wire:model="newTagName" placeholder="Enter tag name" icon="o-tag" />
            <x-slot:actions>
                <x-button label="Cancel" wire:click="closeAddTagModal" class="btn-ghost" />
                <x-button label="Add Tag" type="submit" class="btn-primary" spinner="addTag" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
