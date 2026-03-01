@extends('frontend.layouts.app')
@section('page_name', 'All Courses')
@section('content')

    {{-- Breadcrumb --}}
    <div class="breadcrumb-area text-center bg-gray-gradient-secondary">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <h1>All Courses</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li><a href="{{ route('frontend.index') }}"><i class="fas fa-home"></i> Home</a></li>
                            <li class="active">Courses</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div style="background:#f4f6fb; border-bottom: 1px solid #dce3ef; padding: 16px 0;">
        <div class="container">
            <form method="GET" action="{{ route('frontend.courses.index') }}" class="row g-2 align-items-center">
                <div class="col-lg-5 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text" style="background:#0b3d91; border:none;">
                            <i class="fas fa-search" style="color:#fff;"></i>
                        </span>
                        <input type="text" name="search" class="form-control" placeholder="Search courses..."
                            value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-2">
                    <button type="submit" class="btn w-100"
                        style="background:#FF9933; color:#fff; font-weight:700; border-radius:4px;">
                        Filter
                    </button>
                </div>
                @if (request()->hasAny(['search', 'category']))
                    <div class="col-lg-2 col-md-2">
                        <a href="{{ route('frontend.courses.index') }}" class="btn w-100"
                            style="background:transparent; color:#0b3d91; border:1px solid #0b3d91; font-weight:700; border-radius:4px;">
                            Clear
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Courses Grid --}}
    <div class="course-style-two-area default-padding">
        <div class="container">
            {{-- Results count --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p style="color:#5a6a7a; margin:0;">
                    Showing <strong>{{ $courses->total() }}</strong> course{{ $courses->total() != 1 ? 's' : '' }}
                    @if (request('search'))
                        for <strong>"{{ request('search') }}"</strong>
                    @endif
                </p>
            </div>

            @if ($courses->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-book-open fa-3x mb-3" style="color:#dce3ef;"></i>
                    <h5 style="color:#5a6a7a;">No courses found</h5>
                    <a href="{{ route('frontend.courses.index') }}" class="btn mt-2"
                        style="background:#0b3d91; color:#fff; border-radius:4px;">View All Courses</a>
                </div>
            @else
                <div class="row">
                    @foreach ($courses as $course)
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                            <div class="course-style-one-item"
                                style="background:#fff; border:1px solid #dce3ef; border-radius:6px;
                                       box-shadow:0 2px 12px rgba(11,61,145,0.09); overflow:hidden;
                                       border-top:3px solid transparent; transition:all 0.25s; height:100%;
                                       display:flex; flex-direction:column;">

                                {{-- Thumbnail --}}
                                <div style="position:relative; overflow:hidden;">
                                    @if ($course->image)
                                        <img src="{{ Storage::url($course->image) }}" alt="{{ $course->name }}"
                                            style="width:100%; height:180px; object-fit:cover; display:block;">
                                    @else
                                        <div
                                            style="width:100%; height:180px; background:linear-gradient(135deg,#0b3d91,#1a5fba);
                                                    display:flex; align-items:center; justify-content:center;">
                                            <i class="fas fa-graduation-cap"
                                                style="font-size:48px; color:rgba(255,255,255,0.4);"></i>
                                        </div>
                                    @endif
                                    @if ($course->discount_percentage)
                                        <span
                                            style="position:absolute; top:10px; right:10px; background:#FF9933;
                                                     color:#fff; font-size:11px; font-weight:700;
                                                     padding:3px 8px; border-radius:3px;">
                                            -{{ $course->discount_percentage }}% OFF
                                        </span>
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="info" style="padding:18px; flex:1; display:flex; flex-direction:column;">
                                    {{-- Categories --}}
                                    @if ($course->categories->count())
                                        <div class="mb-2">
                                            @foreach ($course->categories->take(2) as $cat)
                                                <span
                                                    style="background:rgba(11,61,145,0.08); color:#0b3d91;
                                                             font-size:11px; font-weight:700; padding:2px 8px;
                                                             border-radius:3px; margin-right:4px;">{{ $cat->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <h4 style="color:#0b3d91; font-size:15px; font-weight:700; margin-bottom:8px; flex:1;">
                                        {{ $course->name }}
                                    </h4>

                                    @if ($course->description)
                                        <p style="color:#5a6a7a; font-size:13px; line-height:1.6; margin-bottom:12px;">
                                            {{ Str::limit(strip_tags($course->description), 80) }}
                                        </p>
                                    @endif

                                    {{-- Meta row --}}
                                    <div class="d-flex gap-3 mb-2" style="font-size:12px; color:#5a6a7a;">
                                        @if ($course->duration)
                                            <span><i class="fas fa-clock me-1"
                                                    style="color:#FF9933;"></i>{{ $course->duration }}</span>
                                        @endif
                                        <span><i class="fas fa-users me-1"
                                                style="color:#FF9933;"></i>{{ $course->students_count }} enrolled</span>
                                    </div>

                                    {{-- Rating --}}
                                    @if ($course->rating)
                                        <div class="mb-2" style="font-size:12px;">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star"
                                                    style="color:{{ $i <= round($course->rating) ? '#FF9933' : '#dce3ef' }}; font-size:11px;"></i>
                                            @endfor
                                            <span
                                                style="color:#5a6a7a; margin-left:4px;">{{ number_format($course->rating, 1) }}</span>
                                        </div>
                                    @endif

                                    {{-- CTA --}}
                                    <div class="course-bottom-meta d-flex justify-content-end align-items-center mt-auto pt-3"
                                        style="border-top:1px solid #f0f0f0;">
                                        <button
                                            onclick="enquireFor({{ $course->id }}, '{{ addslashes($course->name) }}')"
                                            class="btn btn-sm"
                                            style="background:#FF9933; color:#fff; font-weight:700; font-size:12px;
                                                   border-radius:4px; padding:7px 14px; text-transform:uppercase; letter-spacing:0.4px;">
                                            Enroll Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div>
                    {{ $courses->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Inquiry Modal Shell (static, stays in DOM) --}}
    <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-top: 4px solid #FF9933; border-radius: 8px;">

                {{-- Header --}}
                <div class="modal-header" style="background: #f4f6fb; border-bottom: 1px solid #dce3ef;">
                    <div>
                        <h5 class="modal-title mb-0" id="inquiryModalLabel" style="color: #0b3d91; font-weight: 700;">
                            <i class="fas fa-paper-plane me-2" style="color: #FF9933;"></i> Enquire Now
                        </h5>
                        <small id="inquiryModalCourseName" style="color:#5a6a7a;"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- Body: Livewire form only --}}
                <div class="modal-body p-4">
                    @livewire('frontend.course-inquiry-modal')
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', function() {
            window.enquireFor = function(courseId, courseName) {
                // Update the course name in the modal header
                document.getElementById('inquiryModalCourseName').textContent = courseName;

                // Tell the Livewire component which course was selected
                Livewire.getByName('frontend.course-inquiry-modal')[0].setCourse(courseId, courseName);

                // Open the Bootstrap modal
                var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('inquiryModal'));
                modal.show();
            };
        });
    </script>

@endsection
