<?php

use Mary\Traits\Toast;
use App\Models\Testimonial;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Storage;
use Livewire\{WithFileUploads, WithPagination};

new class extends Component {
    use WithPagination, Toast, WithFileUploads;

    #[Title('All Testimonials')]
    public $headers;
    #[Url]
    public string $search = '';
    public $sortBy = ['column' => 'created_at', 'direction' => 'desc'];
    public $testimonialId;
    public $subject,
        $description,
        $rating = 5,
        $student_name,
        $student_image,
        $oldStudentImage,
        $is_active = true;
    public $editMode = false;
    public $showTestimonialModal = false;
    public $cropConfig = [
        'aspectRatio' => 1,
    ];

    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'student_image', 'label' => 'Student', 'class' => 'w-1'], ['key' => 'student_name', 'label' => 'Student Name'], ['key' => 'subject', 'label' => 'Subject'], ['key' => 'rating', 'label' => 'Rating', 'class' => 'w-1'], ['key' => 'created_at', 'label' => 'Created At', 'class' => 'w-1'], ['key' => 'updated_at', 'label' => 'Last Updated', 'class' => 'w-1']];
    }

    public function rendering(View $view): void
    {
        $view->testimonials = Testimonial::orderBy(...array_values($this->sortBy))
            ->where(function ($query) {
                $query
                    ->where('subject', 'like', "%$this->search%")
                    ->orWhere('student_name', 'like', "%$this->search%")
                    ->orWhere('description', 'like', "%$this->search%");
            })
            ->paginate(20);
        $view->title('All Testimonials');
    }

    public function resetForm()
    {
        $this->subject = $this->description = $this->student_name = $this->student_image = $this->oldStudentImage = $this->is_active = true;
        $this->rating = 5;
        $this->editMode = false;
        $this->testimonialId = null;
        $this->showTestimonialModal = false;
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showTestimonialModal = true;
    }

    public function openEditModal($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $this->testimonialId = $testimonial->id;
        $this->subject = $testimonial->subject;
        $this->description = $testimonial->description;
        $this->rating = $testimonial->rating;
        $this->student_name = $testimonial->student_name;
        $this->oldStudentImage = $testimonial->student_image;
        $this->is_active = $testimonial->is_active;
        $this->editMode = true;
        $this->showTestimonialModal = true;
    }

    public function createTestimonial()
    {
        $this->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'student_name' => 'required|string|max:255',
        ]);

        $testimonial = new Testimonial();
        $testimonial->subject = $this->subject;
        $testimonial->description = $this->description;
        $testimonial->rating = $this->rating;
        $testimonial->student_name = $this->student_name;
        $testimonial->is_active = $this->is_active;

        if ($this->student_image) {
            $path = $this->student_image->store('testimonials', 'public');
            $this->student_image = '/storage/' . $path;
            $testimonial->student_image = $this->student_image;
        }

        $testimonial->save();

        $this->resetForm();
        $this->success('Testimonial created successfully!', position: 'toast-bottom');
    }

    public function updateTestimonial()
    {
        $this->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'student_name' => 'required|string|max:255',
        ]);

        $testimonial = Testimonial::findOrFail($this->testimonialId);
        $testimonial->subject = $this->subject;
        $testimonial->description = $this->description;
        $testimonial->rating = $this->rating;
        $testimonial->student_name = $this->student_name;
        $testimonial->is_active = $this->is_active;
        
        if ($this->student_image) {
            if ($testimonial->student_image) {
                $imagePath = str_replace('/storage/', '', $testimonial->student_image);
                Storage::disk('public')->delete($imagePath);
            }

            $path = $this->student_image->store('testimonials', 'public');
            $this->student_image = '/storage/' . $path;
            $testimonial->student_image = $this->student_image;
        }

        $testimonial->save();

        $this->resetForm();
        $this->success('Testimonial updated successfully!', position: 'toast-bottom');
    }

    public function deleteTestimonial($id)
    {
        try {
            $testimonial = Testimonial::findOrFail($id);
            if ($testimonial->student_image) {
                $imagePath = str_replace('/storage/', '', $testimonial->student_image);
                Storage::disk('public')->delete($imagePath);
            }
            $testimonial->delete();
            $this->success('Testimonial deleted successfully!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to delete testimonial. Please try again.', position: 'toast-bottom');
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
            <h1 class="text-2xl font-bold">All Testimonials</h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>Dashboard</a>
                    </li>
                    <li>All Testimonials</li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-input placeholder="Search testimonials..." icon="o-magnifying-glass" wire:model.live.debounce="search" />
            <x-button label="Add Testimonial" icon="o-plus" class="btn-primary" wire:click="openCreateModal"
                responsive />
        </div>
    </div>
    <hr class="mb-5">

    <x-table :headers="$headers" :rows="$testimonials" with-pagination :sort-by="$sortBy">
        @scope('cell_student_image', $testimonial)
            @if ($testimonial->student_image)
                <div class="avatar select-none">
                    <div class="w-12 rounded-md">
                        <img src="{{ asset($testimonial->student_image) }}" alt="{{ $testimonial->student_name }}" />
                    </div>
                </div>
            @else
                <div class="select-none avatar avatar-placeholder">
                    <div class="w-8 rounded-md bg-neutral text-neutral-content">
                        <span class="text-xs">{{ substr($testimonial->student_name, 0, 1) }}</span>
                    </div>
                </div>
            @endif
        @endscope
        @scope('cell_student_name', $testimonial)
            <span class="font-medium">{{ $testimonial->student_name }}</span>
        @endscope
        @scope('cell_subject', $testimonial)
            <span class="font-medium">{{ $testimonial->subject }}</span>
        @endscope
        @scope('cell_rating', $testimonial)
            <div class="flex items-center gap-1">
                @for ($i = 1; $i <= 5; $i++)
                    @if ($i <= $testimonial->rating)
                        <x-icon name="o-star" class="w-4 h-4 text-yellow-400" />
                    @else
                        <x-icon name="o-star" class="w-4 h-4 text-gray-300" />
                    @endif
                @endfor
                <span class="text-sm text-gray-600 ml-1">({{ $testimonial->rating }})</span>
            </div>
        @endscope
        @scope('cell_created_at', $testimonial)
            {{ $testimonial->created_at->format('d M Y') }}
        @endscope
        @scope('cell_updated_at', $testimonial)
            {{ $testimonial->updated_at->format('d M Y') }}
        @endscope
        @scope('actions', $testimonial)
            <div class="flex gap-1">
                <x-button icon="o-pencil" class="btn-primary btn-outline" wire:click="openEditModal({{ $testimonial->id }})"
                    title="Edit" />
                <x-button icon="o-trash" class="btn-error btn-outline"
                    wire:click="deleteTestimonial({{ $testimonial->id }})" title="Delete"
                    wire:confirm="Are you sure you want to delete this testimonial?" />
            </div>
        @endscope
        <x-slot:empty>
            <x-empty icon="o-no-symbol" message="No testimonials found" />
        </x-slot>
    </x-table>

    <!-- Testimonial Modal -->
    <x-modal wire:model="showTestimonialModal" title="{{ $editMode ? 'Edit Testimonial' : 'Create Testimonial' }}"
        class="backdrop-blur" separator>
        <x-form wire:submit.prevent="{{ $editMode ? 'updateTestimonial' : 'createTestimonial' }}">
            <div class="space-y-4">
                <x-input label="Student Name" wire:model.defer="student_name" placeholder="Enter student name" />

                <x-input label="Subject" wire:model.defer="subject" placeholder="Enter testimonial subject" />

                <x-textarea label="Description" wire:model.defer="description"
                    placeholder="Enter testimonial description" rows="4" />

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                    <x-rating wire:model.defer="rating" />
                </div>

                <x-file label="Student Image" wire:model.defer="student_image"
                    placeholder="Upload student image (optional)" crop-after-change :crop-config="$cropConfig">
                    <img src="{{ $oldStudentImage ? asset($oldStudentImage) : 'https://placehold.co/300' }}"
                        alt="Student Image" class="w-16 h-16 object-cover rounded-md">
                </x-file>

                <x-checkbox label="Active" wire:model.defer="is_active" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showTestimonialModal = false" />
                <x-button label="{{ $editMode ? 'Update' : 'Create' }}" class="btn-primary" type="submit"
                    spinner="{{ $editMode ? 'updateTestimonial' : 'createTestimonial' }}" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
