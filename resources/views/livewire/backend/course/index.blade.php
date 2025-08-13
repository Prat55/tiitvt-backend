<?php

use App\Models\Course;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Livewire\Attributes\{Layout};

new class extends Component {
    use WithPagination;
    #[Title('All Courses')]
    public $headers;
    #[Url]
    public string $search = '';

    public $sortBy = ['column' => 'name', 'direction' => 'asc'];

    // boot
    public function boot(): void
    {
        $this->headers = [['key' => 'name', 'label' => 'Course Name', 'class' => 'w-48'], ['key' => 'categories', 'label' => 'Categories', 'class' => 'w-40'], ['key' => 'duration', 'label' => 'Duration', 'class' => 'w-32'], ['key' => 'price', 'label' => 'Price', 'class' => 'w-32'], ['key' => 'status', 'label' => 'Status', 'class' => 'w-24']];
    }

    public function rendering(View $view): void
    {
        $view->courses = Course::with(['categories'])
            ->orderBy(...array_values($this->sortBy))
            ->whereAny(['name', 'description', 'duration'], 'like', "%$this->search%")
            ->orWhereHas('categories', function ($query) {
                $query->where('name', 'like', "%$this->search%");
            })
            ->paginate(20);
        $view->title('All Courses');
    }
};
?>

<div>
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                All Courses
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        All Courses
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-input placeholder="Search courses, categories, duration..." icon="o-magnifying-glass"
                wire:model.live.debounce="search" />
            <x-button label="Add Course" icon="o-plus" class="btn-primary inline-flex" responsive
                link="{{ route('admin.course.create') }}" />
        </div>
    </div>
    <hr class="mb-5">
    <x-table :headers="$headers" :rows="$courses" with-pagination :sort-by="$sortBy">
        @scope('cell_name', $course)
            <div class="flex items-center gap-3">
                @if ($course->image)
                    <img src="{{ Storage::url($course->image) }}" alt="{{ $course->name }}"
                        class="w-10 h-10 rounded-lg object-cover">
                @else
                    <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                        <x-icon name="o-book-open" class="w-5 h-5 text-primary" />
                    </div>
                @endif
                <div>
                    <div class="font-medium">{{ $course->name }}</div>
                    @if ($course->description)
                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ Str::limit($course->description, 60) }}
                        </div>
                    @endif
                </div>
            </div>
        @endscope
        @scope('cell_categories', $course)
            @if ($course->categories->count() > 0)
                <div class="flex flex-wrap gap-1">
                    @foreach ($course->categories->take(1) as $category)
                        <span class="badge badge-primary badge-sm h-fit">{{ $category->name }}</span>
                    @endforeach
                    @if ($course->categories->count() > 1)
                        <span class="badge badge-soft badge-sm">+{{ $course->categories->count() - 1 }}</span>
                    @endif
                </div>
            @else
                <span class="text-xs text-gray-400">No categories</span>
            @endif
        @endscope
        @scope('cell_duration', $course)
            @if ($course->duration)
                <span class="text-sm font-medium">{{ $course->duration }}</span>
            @else
                <span class="text-xs text-gray-400">-</span>
            @endif
        @endscope
        @scope('cell_price', $course)
            <div class="text-sm">
                @if ($course->price)
                    <div class="font-medium text-success">{{ $course->formatted_price }}</div>
                    @if ($course->mrp && $course->mrp > $course->price)
                        <div class="text-xs text-gray-500 line-through">{{ $course->formatted_mrp }}</div>
                        @if ($course->discount_percentage)
                            <div class="text-xs text-error">-{{ $course->discount_percentage }}%</div>
                        @endif
                    @endif
                @else
                    <span class="text-xs text-gray-400">-</span>
                @endif
            </div>
        @endscope
        @scope('cell_status', $course)
            <div class="flex items-center gap-2">
                @if ($course->is_active)
                    <span class="badge badge-success badge-sm">Active</span>
                @else
                    <span class="badge badge-error badge-sm">Inactive</span>
                @endif
            </div>
        @endscope
        @scope('actions', $course)
            <div class="flex gap-1">
                <x-button icon="o-eye" link="{{ route('admin.course.show', $course->id) }}" class="btn-xs btn-ghost"
                    title="View Details" responsive />
                <x-button icon="o-pencil" link="{{ route('admin.course.edit', $course->id) }}" class="btn-xs btn-ghost"
                    title="Edit Course" responsive />
            </div>
        @endscope
        <x-slot:empty>
            <x-empty icon="o-book-open" message="No courses found" />
        </x-slot>
    </x-table>
</div>
