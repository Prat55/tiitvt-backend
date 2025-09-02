@extends('frontend.layouts.app')
@section('page_name', 'Blog')
@section('content')
    <!-- Start Breadcrumb -->
    <div class="breadcrumb-area text-center bg-gray-gradient-secondary">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <h1>Latest Blog</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li><a href="{{ route('frontend.index') }}"><i class="fas fa-home"></i> Home</a></li>
                            <li class="active">Blog</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- End Breadcrumb -->

    <!-- Start Blog -->
    <div class="blog-area home-blog-style-two blog-grid default-padding">
        <div class="container">
            <div class="blog-item-box">
                <div class="row">
                    <!-- Single Item -->
                    @foreach ($blogs as $blog)
                        <div class="col-xl-4 col-md-6 col-lg-6 mb-50">
                            <div class="home-blog-style-two-item wow fadeInUp">
                                <div class="thumb">
                                    <img src="{{ $blog->image ? asset('storage/' . $blog->image) : 'https://placehold.co/600x350' }}"
                                        alt="Image not Found">
                                    <ul class="blog-meta">
                                        {{-- <li><a href="#">Courses</a></li> --}}
                                        <li>
                                            <i class="fas fa-calendar-alt"></i>
                                            {{ $blog->created_at->format('F d, Y') }}
                                        </li>
                                    </ul>
                                </div>
                                <div class="info">
                                    <h3 class="blog-title">
                                        <a href="{{ route('frontend.blog.show', $blog->slug) }}">{{ $blog->title }}</a>
                                    </h3>
                                    <p>
                                        {{ Str::limit($blog->meta_description, 100) }}
                                    </p>
                                    <a href="{{ route('frontend.blog.show', $blog->slug) }}" class="btn-read-more">
                                        Read More <i class="fas fa-long-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- End Single Item -->
                    @endforeach
                </div>
            </div>
            <!-- Pagination -->
            <div class="row">
                <div class="col-md-12 pagi-area text-center">
                    <nav aria-label="navigation">
                        {{ $blogs->links() }}
                    </nav>
                </div>
            </div>
            <!-- End Pagination -->
        </div>
    </div>
    <!-- End Blog -->
@endsection
