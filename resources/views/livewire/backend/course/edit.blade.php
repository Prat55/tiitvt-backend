<?php

use App\Models\Course;
use App\Models\Category;
use Mary\Traits\Toast;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Layout, Title};

new class extends Component {
    use WithFileUploads, Toast;

    // Form properties
    #[Title('Edit Course')]
    public Course $course;

    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $meta_description = '';
    public string $duration = '';
    public float $mrp = 0;
    public float $price = 0;
    public bool $is_active = true;
    public bool $auto_certificate = false;
    public int $passing_percentage = 80;
    public array $category_ids = [];
    public int $rating;

    // File uploads
    public $course_image;

    public $config = [
        'aspectRatio' => 16 / 9,
    ];

    public function mount(Course $course)
    {
        $this->course = $course;
        $this->loadCourseData();
    }

    private function loadCourseData()
    {
        $this->name = $this->course->name ?? '';
        $this->slug = $this->course->slug ?? '';
        $this->description = $this->course->description ?? '';
        $this->meta_description = $this->course->meta_description ?? '';
        $this->duration = $this->course->duration ?? '';
        $this->mrp = $this->course->mrp ?? 0;
        $this->price = $this->course->price ?? 0;
        $this->is_active = $this->course->is_active ?? true;
        $this->auto_certificate = $this->course->auto_certificate ?? false;
        $this->passing_percentage = $this->course->passing_percentage ?? 80;
        $this->category_ids = $this->course->categories->pluck('id')->toArray();
        $this->rating = $this->course->rating;
    }

    // Validation rules
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:courses,name,' . $this->course->id,
            'slug' => 'nullable|string|max:255|unique:courses,slug,' . $this->course->id,
            'description' => 'nullable|string|max:1000',
            'meta_description' => 'nullable|string|max:150',
            'duration' => 'nullable|string|max:100',
            'mrp' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:categories,id',
            'course_image' => 'nullable|image|max:2048',
            'rating' => 'nullable|integer|min:0|max:5',
            'auto_certificate' => 'boolean',
            'passing_percentage' => 'required_if:auto_certificate,true|nullable|integer|min:1|max:100',
        ];
    }

    // Validation messages
    protected function messages(): array
    {
        return [
            'name.required' => 'Course name is required.',
            'name.unique' => 'This course name already exists.',
            'slug.unique' => 'This slug already exists.',
            'mrp.min' => 'MRP must be greater than or equal to 0.',
            'price.min' => 'Price must be greater than or equal to 0.',
            'category_ids.array' => 'Please select valid categories.',
            'category_ids.*.exists' => 'One or more selected categories are invalid.',
            'rating.min' => 'Rating must be greater than or equal to 0.',
            'rating.max' => 'Rating must be less than or equal to 5.',
        ];
    }

    // Update course
    public function save(): void
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'slug' => $this->slug ?: Str::slug($this->name),
                'description' => $this->description,
                'meta_description' => $this->meta_description,
                'duration' => $this->duration,
                'mrp' => $this->mrp ?: null,
                'price' => $this->price ?: null,
                'is_active' => $this->is_active,
                'rating' => $this->rating ?? 4,
                'auto_certificate' => $this->auto_certificate,
                'passing_percentage' => $this->auto_certificate ? $this->passing_percentage ?? 80 : null,
            ];

            if ($this->course_image) {
                // Delete old image if exists
                if ($this->course->image) {
                    Storage::disk('public')->delete($this->course->image);
                }
                $data['image'] = $this->course_image->store('courses/images', 'public');
            }

            $this->course->update($data);

            // Sync categories
            $this->course->categories()->sync($this->category_ids);

            $this->success('Course updated successfully!', position: 'toast-bottom');
            $this->redirect(route('admin.course.show', $this->course->id));
        } catch (\Exception $e) {
            $this->error('Failed to update course. Please try again.', position: 'toast-bottom');
        }
    }

    // Remove uploaded file
    public function removeFile($property): void
    {
        $this->$property = null;
        $this->success('File removed successfully!', position: 'toast-bottom');
    }

    // Auto-generate slug when name changes
    public function updatedName(): void
    {
        if ($this->name && empty($this->slug)) {
            $this->slug = Str::slug($this->name);
        }
    }

    // Ensure price doesn't exceed MRP
    public function updatedPrice(): void
    {
        if ($this->price > $this->mrp && $this->mrp > 0) {
            $this->price = $this->mrp;
        }
    }

    public function rendering(\Illuminate\View\View $view)
    {
        $view->categories = Category::active()
            ->latest()
            ->get(['id', 'name']);
    }
}; ?>

@section('cdn')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/tinymce.min.js" referrerpolicy="origin"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/gh/robsontenorio/mary@0.44.2/libs/currency/currency.js">
    </script>
@endsection

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Edit Course
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
                        <a href="{{ route('admin.course.show', $course->id) }}" wire:navigate>
                            {{ $course->name }}
                        </a>
                    </li>
                    <li>
                        Edit
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-button label="View Course" icon="o-eye" class="btn-primary btn-outline"
                link="{{ route('admin.course.show', $course->id) }}" responsive />
            <x-button label="Back to Courses" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.course.index') }}" responsive />
        </div>
    </div>

    <hr class="mb-5">

    <!-- Form -->
    <x-card shadow>
        <form wire:submit="save" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>
                </div>

                <x-input label="Course Name" wire:model="name" placeholder="Enter course name" icon="o-book-open" />

                <x-input label="Duration" wire:model="duration" placeholder="e.g., 3 months, 6 weeks" icon="o-clock"
                    hint="Course duration in human-readable format" />

                <x-textarea label="Meta Description" wire:model="meta_description"
                    placeholder="Enter meta description for SEO" icon="o-tag" maxlength="150"
                    hint="Maximum 150 characters for SEO purposes" class="md:col-span-2" />

                <div>
                    <x-file label="Course Image" wire:model="course_image" accept="image/*"
                        placeholder="Upload new course image" icon="o-photo"
                        hint="Max 2MB, Recommended: 16:9 aspect ratio" crop-after-change :crop-config="$config">
                        <img src="{{ $course->image ? asset('storage/' . $course->image) : 'https://placehold.co/400x225?text=Course+Image' }}"
                            alt="Course Image" class="w-full h-32 object-cover rounded-lg">
                    </x-file>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-primary">Course Rating</h3>
                    <p class="text-sm text-gray-600 mt-1">Set the rating for this course</p>
                    <x-rating wire:model="rating" />
                </div>

            </div>

            <!-- Pricing Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Pricing Information</h3>
                </div>

                <x-input label="MRP (Maximum Retail Price)" wire:model.live="mrp" placeholder="Enter MRP"
                    icon="o-currency-rupee" money step="0.01" hint="Original price before discount" />

                <x-input label="Selling Price" wire:model.live="price" placeholder="Enter selling price"
                    icon="o-currency-rupee" money step="0.01" hint="Final price for learners" />

                @if ($mrp > 0 && $price > 0 && $mrp > $price)
                    <div class="md:col-span-2 mt-2">
                        <x-alert icon="o-tag" class="alert-success">
                            <h3 class="text-sm font-semibold">Discount Applied!</h3>
                            Learners will save up to <strong>₹{{ number_format($mrp - $price, 2) }}
                                ({{ round((($mrp - $price) / $mrp) * 100) }}% off)</strong>
                        </x-alert>
                    </div>
                @endif
            </div>

            <!-- Categories -->
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-primary">Categories</h3>
                    <p class="text-sm text-gray-600 mt-1">Select one or more categories for this course</p>
                </div>

                <x-choices-offline label="Course Categories" wire:model="category_ids" placeholder="Select categories"
                    icon="o-tag" :options="$categories" multiple searchable clearable />
            </div>

            <!-- Description -->
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-primary">Course Description</h3>
                    <p class="text-sm text-gray-600 mt-1">Provide a detailed description of the course</p>
                </div>

                <x-editor wire:model="description" label="Description" hint="The full course description" />
            </div>

            <!-- Status & Certificates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-primary">Status</h3>
                    <x-toggle label="Active Course" wire:model="is_active"
                        hint="Inactive courses won't be visible to students" />
                </div>

                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-primary">Certificate Settings</h3>
                    <div class="flex items-center gap-4">
                        <x-toggle label="Auto Certificate Creation" wire:model.live="auto_certificate"
                            hint="Automatically create certificates based on exam results" />

                        @if ($auto_certificate)
                            <div class="flex-1">
                                <x-input label="Passing Percentage (%)" type="number" wire:model="passing_percentage"
                                    min="1" max="100" icon="o-academic-cap"
                                    hint="Predefined passing marks for auto-certificate creation" />
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <x-button label="Cancel" icon="o-x-mark" class="btn-error btn-soft btn-sm"
                    link="{{ route('admin.course.show', $course->id) }}" responsive />
                <x-button label="Update Course" icon="o-check" class="btn-primary btn-sm btn-soft" type="submit"
                    spinner="save" responsive />
            </div>
        </form>
    </x-card>
</div>
