<?php

use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new class extends Component {
    #[Title('Course Details')]
    public Course $course;
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
                            <p class="text-base font-semibold">{{ $course->name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Slug</label>
                            <p class="text-base font-mono text-gray-700">{{ $course->slug }}</p>
                        </div>

                        @if ($course->duration)
                            <div>
                                <label class="text-sm font-medium text-gray-600">Duration</label>
                                <p class="text-base">{{ $course->duration }}</p>
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
                            <label class="text-sm font-medium text-gray-600">Meta Description</label>
                            <p class="text-base text-gray-700 mt-1">{{ $course->meta_description }}</p>
                        </div>
                    @endif


                    @if ($course->description)
                        <div>
                            <label class="text-sm font-medium text-gray-600">Description</label>
                            <p class="text-base text-gray-700 mt-1">{!! $course->description !!}</p>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Pricing Information -->
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
                                <p class="text-lg text-gray-700">{{ $course->formatted_mrp }}</p>
                            </div>
                        @endif

                        @if ($course->discount_percentage)
                            <div>
                                <label class="text-sm font-medium text-gray-600">Discount</label>
                                <p class="text-lg font-semibold text-error">-{{ $course->discount_percentage }}%</p>
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
                    </div>
                </div>
            </x-card>

            <!-- Course Actions -->
            <x-card shadow>
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-primary">Actions</h3>

                    <x-button label="Edit Course" icon="o-pencil" class="btn-primary w-full"
                        link="{{ route('admin.course.edit', $course->id) }}" />

                    <x-button label="View Students" icon="o-users" class="btn-outline w-full"
                        link="{{ route('admin.student.index') }}?course={{ $course->id }}" />

                    <x-button label="Manage Exams" icon="o-academic-cap" class="btn-outline w-full"
                        link="{{ route('admin.exam.index') }}?course={{ $course->id }}" />
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
        </div>
    </div>
</div>
