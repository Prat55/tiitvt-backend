@extends('frontend.layouts.app')
@section('title', 'Home')
@section('content')
    <!-- Start Banner Area -->
    <div class="banner-style-nine-area bg-cover"
        style="background-image: url({{ asset('frontend/img/shape/banner-7.jpg') }});">
        <div class="container">
            <div class="banner-style-nine-items">
                <div class="row align-items-center">
                    <div class="col-lg-7 pr-60 pr-md-15 pr-xs-15">
                        <div class="info">
                            <h4>
                                <img src="{{ asset('frontend/img/shape/91.png') }}" alt="Image Not Found">
                                Discover 50+ World-Class Courses
                            </h4>
                            <h2>Learn <strong>Smarter</strong> Achieve knowledge</h2>
                            <p>
                                Expand your knowledge and open doors to exciting careers with our online education
                                platform.
                            </p>
                            <a class="btn btn-md btn-gradient animation" href="###">
                                Explore Course
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="thumb">
                            <img src="{{ asset('frontend/img/illustration/1.png') }}" alt="Image Not Found">
                            <img src="{{ asset('frontend/img/shape/92.png') }}" alt="Image Not Found">
                            <div class="card-style-seven">
                                <h2>4.9</h2>
                                <div class="content">
                                    <div class="icon">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                    <h4>Instructor Rating</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Banner -->

    @if ($categories->count() > 0)
        <!-- Start Category -->
        <div class="category-style-two-area default-padding bg-cover bg-gray-secondary"
            style="background-image: url({{ asset('frontend/img/shape/wooden.png') }});">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 offset-lg-2">
                        <div class="site-heading text-center">
                            <h4 class="sub-title">Top categories</h4>
                            <h2 class="title split-text">Most demanding category to learn from today</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="category-style-two-carousel swiper">
                            <div class="swiper-wrapper">
                                @foreach ($categories as $category)
                                    <div class="swiper-slide">
                                        <div class="category-style-two-item wow fadeInUp">
                                            <a href="?category={{ $category->slug }}">
                                                <div class="info">
                                                    <h4>{{ $category->name }} <strong>interface </strong></h4>
                                                    <span>{{ $category->courses->count() }} Course(s)</span>
                                                </div>
                                                <i class="fas fa-long-arrow-right"></i>
                                                <div class="thumb">
                                                    <img src="{{ $category->image ? asset('storage/' . $category->image) : 'https://placehold.co/600x400?text=' . $category->name }}"
                                                        alt="{{ $category->name }}">
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="about-style-three-area overflow-hidden default-padding">
        <div class="container">
            <div class="about-style-three-items wow fadeInUp" data-wow-delay="200ms">
                <div class="row align-center">
                    <div class="col-lg-6">
                        <div class="about-style-three-thumb">
                            <img src="{{ asset('frontend/img/thumb/14.jpg') }}" alt="Image Not Found">
                            <img src="{{ asset('frontend/img/shape/45.png') }}" alt="Image Not Found">
                        </div>
                    </div>
                    <div class="col-lg-6 pl-60 pl-md-15 pl-xs-15">
                        <div class="about-style-three-info">
                            <h2 class="title split-text">Our commitment to diversity leadership.</h2>

                            <p>
                                Education has come a long way from its traditional roots and will continue to evolve.
                                Learn but the majority have suffered alteration in some form, by injected humour, or
                                randomised words which don't look even slightly. njected humour, or randomised words
                                which don't look even slightly believable. If you are going to use a passage of Lorem
                                Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle.
                            </p>
                            <div class="fact-style-two">
                                <div class="fun-fact">
                                    <div class="counter">
                                        <div class="timer" data-to="28" data-speed="2000">28</div>
                                        <div class="operator">K</div>
                                    </div>
                                    <h4>Graduate Students</h4>
                                </div>
                                <div class="fun-fact">
                                    <div class="counter">
                                        <div class="timer" data-to="98" data-speed="2000">98</div>
                                        <div class="operator">%</div>
                                    </div>
                                    <h4>Happy Students</h4>
                                </div>
                            </div>
                            <a class="btn btn-md btn-gradient animation" href="contact-us.html">Get Started</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($courses->count() > 0)
        <div class="course-style-two-area default-padding bottom-less bg-gray-gradient-secondary overflow-hidden"
            id="courses">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 offset-lg-2">
                        <div class="site-heading text-center">
                            <h4 class="sub-title">Latest Courses</h4>
                            <h2 class="title">Most Popular Courses</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container">
                <div class="course-style-one-carousel swiper">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="row">
                                <div class="course-inner-carousel swiper">
                                    <div class="swiper-wrapper">
                                        @foreach ($courses as $course)
                                            <div class="swiper-slide">
                                                <div class="course-style-one-item hover-less style-two">
                                                    <div class="thumb">
                                                        <img src="{{ $course->image ? asset('storage/' . $course->image) : 'https://placehold.co/600x350' }}"
                                                            alt="{{ $course->name }}">
                                                    </div>

                                                    <div class="top-meta">
                                                        <ul>
                                                            <li>
                                                                <div class="course-rating">
                                                                    @for ($i = 1; $i <= $course->rating; $i++)
                                                                        <i class="fas fa-star"></i>
                                                                    @endfor
                                                                    <span>({{ $course->rating }})</span>
                                                                </div>
                                                            </li>
                                                        </ul>
                                                        {{-- <div class="bookmark">
                                                            <a href="#"><i class="fas fa-bookmark"></i></a>
                                                        </div> --}}
                                                    </div>

                                                    <div class="info">
                                                        <div class="author">
                                                            <img src="{{ asset('default/tiitvt_logo.svg') }}"
                                                                alt="Image Not Found">
                                                            <a href="{{ route('frontend.index') }}">
                                                                {{ config('app.name') }}
                                                            </a>
                                                        </div>
                                                        <h4><a href="course-single.html">{{ $course->name }}</a></h4>
                                                        <div class="course-meta">
                                                            <ul>
                                                                <li>
                                                                    <i class="fas fa-user"></i>
                                                                    {{ $course->students->count() }} Students
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>

                                                    <div class="course-bottom-meta">
                                                        <h2 class="price">
                                                            <del>{{ $course->mrp }}</del>
                                                            {{ $course->price }}
                                                        </h2>

                                                        <a href="">
                                                            <i class="fas fa-shopping-cart"></i> Enroll Now
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Single Item -->
                    </div>
                </div>
            </div>
        </div>
        <!-- End Course -->
    @endif

    <!-- Start Certificate -->
    <div class="certificate-area">
        <div class="container">
            <div class="row">
                <div class="col-xl-6 col-lg-7">
                    <div class="certificate-info default-padding">
                        <h4 class="sub-title">Earn a certificate</h4>
                        <h2 class="title split-text">Enjoy new skills to go ahead for your career.</h2>
                        <p class="wow fadeInUp" data-wow-delay="200ms">
                            There are many variations of passages of Lorem Ipsum available, but the majority have
                            suffered alteration in some form, by injected humour, or randomised words.
                        </p>
                        <div class="cartifita-style-one-items wow fadeInUp mt-40" data-wow-delay="400ms">
                            <div class="certificate-carousel swiper">
                                <!-- Additional required wrapper -->
                                <div class="swiper-wrapper">
                                    <!-- Single Item -->
                                    <div class="swiper-slide">
                                        <div class="certificate-items">
                                            <div class="thumb">
                                                <img src="{{ asset('frontend/img/thumb/15.jpg') }}"
                                                    alt="Image Not Found">
                                            </div>
                                            <div class="info">
                                                <h4>Coding Certificate</h4>
                                                <span>Total Awarded 450</span>
                                                <a class="btn circle btn-theme animation" href="course-single.html">View
                                                    Programs</a>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Single Item -->
                                    <!-- Single Item -->
                                    <div class="swiper-slide">
                                        <div class="certificate-items">
                                            <div class="thumb">
                                                <img src="{{ asset('frontend/img/thumb/15.jpg') }}"
                                                    alt="Image Not Found">
                                            </div>
                                            <div class="info">
                                                <h4>Design Certificate</h4>
                                                <span>Total Awarded 350</span>
                                                <a class="btn circle btn-theme animation" href="course-single.html">View
                                                    Programs</a>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Single Item -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5 offset-xl-1 col-lg-5">
                    <div class="certificate-thumb wow fadeInUp">
                        <img src="{{ asset('frontend/img/illustration/6.png') }}" alt="Image Not Found">
                        <img src="{{ asset('frontend/img/shape/37.png') }}" alt="Image Not Found">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Certificate -->

    @if ($testimonials->count() > 0)
        <!-- Start Testimonial -->
        <div class="testimonial-style-two-area default-padding">
            <div class="container">
                <div class="row">
                    <div class="col-xl-6 offset-xl-3 col-lg-8 offset-lg-2">
                        <div class="site-heading text-center">
                            <h4 class="sub-title">Student Feedback</h4>
                            <h2 class="title split-text">Best review from our successful student</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="testimonial-style-two-carousel swiper">
                            <!-- Additional required wrapper -->
                            <div class="swiper-wrapper">
                                <!-- Single Item -->
                                @foreach ($testimonials as $testimonial)
                                    <div class="swiper-slide">
                                        <div class="testimonial-style-two">
                                            <div class="top-info">
                                                <div class="icon">
                                                    <img src="{{ asset('frontend/img/shape/quote.png') }}"
                                                        alt="Image Not Found">
                                                </div>
                                                <h5>{{ $testimonial->subject }}</h5>
                                            </div>
                                            <div class="content">
                                                <p>
                                                    {{ $testimonial->description }}
                                                </p>
                                                <div class="bottom-info">
                                                    <div class="provider">
                                                        <div class="thumb">
                                                            <img src="{{ $testimonial->student_image ? asset('storage/' . $testimonial->student_image) : 'https://placehold.co/600x400?text=' . $testimonial->student_name }}"
                                                                alt="Image Not Found">
                                                        </div>
                                                        <div class="info">
                                                            <h4>{{ $testimonial->student_name }}</h4>
                                                            <div class="ratings">
                                                                @for ($i = 1; $i <= $testimonial->rating; $i++)
                                                                    <i class="fas fa-star"></i>
                                                                @endfor
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                <!-- Single Item -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Testimonial -->
    @endif

    @if ($blogs->count() > 0)
        <!-- Start Blog -->
        <div class="blog-area home-blog-style-two bg-gray-gradient-secondary default-padding bottom-less">
            <div class="container">
                <div class="row">
                    <div class="col-xl-6 offset-xl-3 col-lg-8 offset-lg-2">
                        <div class="site-heading text-center">
                            <h4 class="sub-title">Blog Insight</h4>
                            <h2 class="title split-text">Valuable insights to change your startup idea</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    @foreach ($blogs as $blog)
                        <div class="col-xl-4 col-md-6 col-lg-6 mb-30">
                            <div class="home-blog-style-two-item wow fadeInUp">
                                <div class="thumb">
                                    <img src="{{ $blog->image ? asset('storage/' . $blog->image) : 'https://placehold.co/600x350' }}"
                                        alt="{{ $blog->title }}">
                                    <ul class="blog-meta">
                                        {{-- <li><a href="#">Courses</a></li> --}}
                                        <li>
                                            <i class="fas fa-calendar-alt"></i> {{ $blog->created_at->format('F d, Y') }}
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
                    @endforeach
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <a href="{{ route('frontend.blog.index') }}">
                                <button>
                                    View All Blog <i class="fas fa-long-arrow-right"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Blog -->
    @endif

    <!-- Start Newsletter -->
    <div class="newsletter-area default-padding-bottom bg-gray-gradient-secondary">
        <div class="container">
            <div class="newsletter-style-one-items bg-theme text-center bg-cover"
                style="background-image: url({{ asset('frontend/img/shape/banner-8.jpg') }});">
                <div class="shape">
                    <img src="{{ asset('frontend/img/illustration/9.png') }}" alt="Image Not Found">
                    <img src="{{ asset('frontend/img/shape/48.png') }}" alt="Image Not Found">
                </div>
                <div class="row">
                    <div class="col-xl-8 offset-xl-2 col-lg-10 offset-lg-1">
                        <h2 class="title split-text">Subscribe to our newsletter, We don't make any spam.</h2>
                        <ul class="list-check">
                            <li>Easy to Access</li>
                            <li>No Credit card</li>
                            <li>10M student onboard with us</li>
                        </ul>
                        <form action="#">
                            <input type="email" placeholder="Your Email" class="form-control" name="email">
                            <button type="submit">Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Newsletter -->
@endsection
