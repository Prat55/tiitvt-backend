@extends('frontend.layouts.app')
@section('page_name', 'Blog')
@section('content')
    <!-- Start Breadcrumb -->
    <div class="breadcrumb-area text-center bg-gray-gradient-secondary">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <h1>{{ $blog->title }}</h1>
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
    <div class="blog-area single full-blog full-blog default-padding">
        <div class="container">
            <div class="blog-items">
                <div class="row">
                    <div class="blog-content wow fadeInUp col-lg-10 offset-lg-1 col-md-12">
                        <div class="blog-style-two item">
                            <div class="blog-item-box">
                                <div class="thumb">
                                    <a href="#">
                                        <img src="{{ $blog->image ? asset('storage/' . $blog->image) : 'https://placehold.co/600x350' }}"
                                            alt="Thumb">
                                    </a>
                                </div>

                                <div class="info">
                                    <div class="meta">
                                        <ul>
                                            <li>
                                                <a href="#">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    {{ $blog->created_at->format('F d, Y') }}
                                                </a>
                                            </li>

                                            {{-- <li>
                                                <a href="#">
                                                    <i class="fas fa-user-circle"></i>
                                                    {{ getWebsiteSettings()->meta_author }}
                                                </a>
                                            </li> --}}
                                        </ul>
                                    </div>
                                    <div>
                                        {!! $blog->content !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Post Tags Share -->
                        <div class="post-tags share">
                            <div class="tags">
                                <h4>Tags: </h4>
                                @foreach ($blog->tags as $tag)
                                    <a href="{{ route('frontend.blog.index') }}?tag={{ $tag->slug }}">
                                        {{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>

                            <div class="social">
                                <h4>Share:</h4>
                                <ul>
                                    <li>
                                        <a class="facebook"
                                            href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('frontend.blog.show', $blog->slug)) }}"
                                            target="_blank">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="twitter"
                                            href="https://twitter.com/intent/tweet?url={{ urlencode(route('frontend.blog.show', $blog->slug)) }}"
                                            target="_blank">
                                            <i class="fab fa-twitter"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="pinterest"
                                            href="https://api.whatsapp.com/send?text={{ urlencode(route('frontend.blog.show', $blog->slug)) }}"
                                            target="_blank">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="linkedin"
                                            href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(route('frontend.blog.show', $blog->slug)) }}"
                                            target="_blank">
                                            <i class="fab fa-linkedin-in"></i>
                                        </a>
                                    </li>
                                </ul><!-- End Social Share -->
                            </div>
                        </div>
                        <!-- Post Tags Share -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Blog -->
@endsection
