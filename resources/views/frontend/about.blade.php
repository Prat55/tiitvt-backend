@extends('frontend.layouts.app')
@section('page_name', 'About Us')
@section('content')
    <!-- Start Breadcrumb -->
    <div class="breadcrumb-area text-center bg-gray-gradient-secondary">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <h1>About Us</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                            <li class="active">About</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- End Breadcrumb -->

    <!-- Start About -->
    <div class="about-style-one-area default-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="thumb-style-two">
                        <img class="wow fadeInUp" src="{{ asset('frontend/img/thumb/4.jpg') }}" alt="Image Not Found">
                        <img class="wow fadeInDown" data-wow-delay="200ms" src="{{ asset('frontend/img/thumb/5.jpg') }}"
                            alt="Image Not Found">
                        <div class="shape">
                            <img src="{{ asset('frontend/img/shape/35.png') }}" alt="Image Not Found">
                            <img src="{{ asset('frontend/img/shape/37.png') }}" alt="Image Not Found">
                        </div>
                        <a href="https://www.youtube.com/watch?v=pv648_qOz94" class="popup-youtube video-play-button"><i
                                class="fas fa-play"></i></a>
                    </div>
                </div>
                <div class="col-lg-6 pl-80 pl-md-15 pl-xs-15">
                    <div class="about-style-one-info">
                        <h2 class="title split-text">TIITVT – Shaping Skills, Building Careers</h2>
                        <div class="wow fadeInUp" data-wow-delay="400ms">
                            <p>
                                TIITVT delivers internationally standardized IT and vocational training programs that
                                prepare students with practical, job-ready skills, enabling them to pursue rewarding careers
                                in reputed organizations. Our training is conducted through authorized training centers
                                across India, and successful candidates receive globally recognized certifications.
                            </p>
                            <ul class="list-style-two">
                                <li>
                                    Industry Training - Global standard IT & vocational courses.
                                </li>
                                <li>
                                    Certified Skills - Recognized certificates for career growth.
                                </li>
                                <li>
                                    Nationwide Reach - Authorized centers across India.
                                </li>
                            </ul>
                            <div class="button-double mt-30">
                                <a class="btn btn-sm btn-dark animation" href="{{ route('frontend.contact') }}"
                                    target="_blank">
                                    Contact Us
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End About -->

    <!-- Start Fun Fact -->
    <div class="fun-fact-style-two-area default-padding dark-optional text-light overflow-hidden">
        <div class="container">
            <div class="heading-left">
                <div class="row">
                    <div class="col-lg-6">
                        <h4 class="sub-title">Education for everyone</h4>
                        <h2 class="title split-text">Advantages of our program</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-9">
                    <div class="fun-fact-style-three-items">
                        <!-- Single item -->
                        <div class="funfact-style-one-item wow fadeInUp">
                            <div class="icon">
                                <img src="{{ asset('frontend/img/icon/31.png') }}" alt="Image Not Found">
                            </div>
                            <div class="fun-fact">
                                <span class="medium">Students Enrolled</span>
                                <div class="counter">
                                    <div class="timer" data-to="56" data-speed="2000">56</div>
                                    <div class="operator">K</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Single item -->

                        <!-- Single item -->
                        <div class="funfact-style-one-item wow fadeInUp" data-wow-delay="200ms">
                            <div class="icon">
                                <img src="{{ asset('frontend/img/icon/32.png') }}" alt="Image Not Found">
                            </div>
                            <div class="fun-fact">
                                <span class="medium">Certified Teachers</span>
                                <div class="counter">
                                    <div class="timer" data-to="30" data-speed="2000">30</div>
                                    <div class="operator">+</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Single item -->

                        <!-- Single item -->
                        <div class="funfact-style-one-item wow fadeInUp" data-wow-delay="400ms">
                            <div class="icon">
                                <img src="{{ asset('frontend/img/icon/33.png') }}" alt="Image Not Found">
                            </div>
                            <div class="fun-fact">
                                <span class="medium">Premium Courses</span>
                                <div class="counter">
                                    <div class="timer" data-to="450" data-speed="2000">450</div>
                                    <div class="operator">+</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Single item -->
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="advantages-info">
                        <h2>The worlds best online education institude</h2>
                        <a class="btn btn-sm btn-light animation" href="about-us.html">Explore More</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Fun Fact -->

    <!-- Start Advisor -->
    <div class="advisor-style-one-area default-padding mb--50">
        <div class="container">
            <div class="row">
                <div class="col-xl-6 offset-xl-3 col-lg-8 offset-lg-2">
                    <div class="site-heading text-center">
                        <h4 class="sub-title">Professional Instructor</h4>
                        <h2 class="title split-text">Meet the talented advisor form our institute</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="advisor-style-one-items">
                <div class="row">

                    <!-- Single item -->
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-50 wow fadeInUp" data-wow-delay="200ms">
                        <div class="advisor-style-one-item">
                            <div class="thumb">
                                <img src="{{ asset('frontend/img/advisor/1.jpg') }}" alt="Image Not Found">
                                <div class="social-overlay">
                                    <ul>
                                        <li>
                                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                        </li>
                                        <li>
                                            <a href="#"><i class="fab fa-dribbble"></i></a>
                                        </li>
                                        <li>
                                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                                        </li>
                                    </ul>
                                    <div class="icon">
                                        <div class="inner">
                                            <i class="fas fa-plus"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="info">
                                <h4><a href="profile.html">Kennsual Martin</a></h4>
                                <span>Laravel Tutor</span>
                            </div>
                        </div>
                    </div>
                    <!-- End Single item -->
                    <!-- Single item -->
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-50 wow fadeInUp" data-wow-delay="400ms">
                        <div class="advisor-style-one-item wow">
                            <div class="thumb">
                                <img src="{{ asset('frontend/img/advisor/2.jpg') }}" alt="Image Not Found">
                                <div class="social-overlay">
                                    <ul>
                                        <li>
                                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                        </li>
                                        <li>
                                            <a href="#"><i class="fab fa-dribbble"></i></a>
                                        </li>
                                        <li>
                                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                                        </li>
                                    </ul>
                                    <div class="icon">
                                        <div class="inner">
                                            <i class="fas fa-plus"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="info">
                                <h4><a href="profile.html">Sarahub Albert</a></h4>
                                <span>Spoken Tutor</span>
                            </div>
                        </div>
                    </div>
                    <!-- End Single item -->
                    <!-- Single item -->
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-50 wow fadeInUp pl-50 pl-md-15 pl-xs-15">
                        <div class="join-as-advisor">
                            <img src="{{ asset('frontend/img/shape/38.png') }}" alt="Image Not Found">
                            <ul>
                                <li>
                                    <h4>Teach your way</h4>
                                    <p>
                                        Education rather her you not esteem men settle genius excuse. Deal say over you
                                        age from. Comparison.
                                    </p>
                                </li>
                                <li>
                                    <h4>Inspire learners</h4>
                                    <p>
                                        Teaching do existence northward as per preserved daughters. Continued at up to
                                        zealously necessary term breakfast surrounded.
                                    </p>
                                </li>
                            </ul>
                            <a href="contact-us.html" class="btn-style-two"><span>Become a instructor</span> <i
                                    class="fas fa-long-arrow-right"></i></a>
                        </div>
                    </div>
                    <!-- End Single item -->
                </div>
                <div class="row">
                    <div class="col-xl-12">
                        <div class="team-grid">
                            <div class="row">
                                <!-- Single item -->
                                <div class="col-xl-4 col-lg-6 col-md-6 mb-50 wow fadeInUp">
                                    <div class="advisor-style-one-item">
                                        <div class="thumb">
                                            <img src="{{ asset('frontend/img/advisor/3.jpg') }}" alt="Image Not Found">
                                            <div class="social-overlay">
                                                <ul>
                                                    <li>
                                                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                                    </li>
                                                    <li>
                                                        <a href="#"><i class="fab fa-dribbble"></i></a>
                                                    </li>
                                                    <li>
                                                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                                                    </li>
                                                </ul>
                                                <div class="icon">
                                                    <div class="inner">
                                                        <i class="fas fa-plus"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="info">
                                            <h4><a href="profile.html">Amanulla Joey</a></h4>
                                            <span>WordPress Tutor</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Single item -->
                                <!-- Single item -->
                                <div class="col-xl-4 col-lg-6 col-md-6 mb-50 wow fadeInUp" data-wow-delay="200ms">
                                    <div class="advisor-style-one-item">
                                        <div class="thumb">
                                            <img src="{{ asset('frontend/img/advisor/4.jpg') }}" alt="Image Not Found">
                                            <div class="social-overlay">
                                                <ul>
                                                    <li>
                                                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                                    </li>
                                                    <li>
                                                        <a href="#"><i class="fab fa-dribbble"></i></a>
                                                    </li>
                                                    <li>
                                                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                                                    </li>
                                                </ul>
                                                <div class="icon">
                                                    <div class="inner">
                                                        <i class="fas fa-plus"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="info">
                                            <h4><a href="profile.html">Kamal Abraham</a></h4>
                                            <span>React Tutor</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Single item -->
                                <!-- Single item -->
                                <div class="col-xl-4 col-lg-6 col-md-6 mb-50 wow fadeInUp" data-wow-delay="400ms">
                                    <div class="advisor-style-one-item">
                                        <div class="thumb">
                                            <img src="{{ asset('frontend/img/advisor/5.jpg') }}" alt="Image Not Found">
                                            <div class="social-overlay">
                                                <ul>
                                                    <li>
                                                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                                    </li>
                                                    <li>
                                                        <a href="#"><i class="fab fa-dribbble"></i></a>
                                                    </li>
                                                    <li>
                                                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                                                    </li>
                                                </ul>
                                                <div class="icon">
                                                    <div class="inner">
                                                        <i class="fas fa-plus"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="info">
                                            <h4><a href="profile.html">Daniyel Joe</a></h4>
                                            <span>Java Tutor</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Single item -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Advisor -->

    <!-- Start Join Us -->
    <div class="join-us-style-one-area default-padding bg-gray-gradient-secondary">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="join-us-style-one-item wow fadeInUp">
                        <div class="shape">
                            <img src="{{ asset('frontend/img/shape/52.png') }}" alt="Image Not Found">
                        </div>
                        <div class="info">
                            <span>Join us today</span>
                            <h2>Become an expert Instructor</h2>
                            <p>
                                Learn at your own pace, move the between multiple courses.
                            </p>
                            <a class="btn circle btn-sm btn-gradient animation" href="contact-us.html">Apply Now</a>
                        </div>
                        <div class="thumb">
                            <img src="{{ asset('frontend/img/illustration/7.png') }}" alt="Image Not Found">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="join-us-style-one-item active wow fadeInUp" data-wow-delay="300ms">
                        <div class="shape">
                            <img src="{{ asset('frontend/img/shape/52.png') }}" alt="Image Not Found">
                        </div>
                        <div class="info">
                            <span>Start learning</span>
                            <h2>Become a skilled pro learner</h2>
                            <p>
                                Learn at your own pace, move the between multiple courses.
                            </p>
                            <a class="btn btn-sm btn-light circle animation" href="contact-us.html">Apply Now</a>
                        </div>
                        <div class="thumb">
                            <img src="{{ asset('frontend/img/illustration/8.png') }}" alt="Image Not Found">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Join Us -->

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
                            <div class="swiper-slide">
                                <div class="testimonial-style-two">
                                    <div class="top-info">
                                        <div class="icon">
                                            <img src="{{ asset('frontend/img/shape/quote.png') }}" alt="Image Not Found">
                                        </div>
                                        <h5>Great Quality</h5>
                                    </div>
                                    <div class="content">
                                        <p>
                                            Education is the most powerful tool to change the world One thing that can’t
                                            be taken from you. A mind is a terrible thing to waste of perfection.
                                        </p>
                                        <div class="bottom-info">
                                            <div class="provider">
                                                <div class="thumb">
                                                    <img src="{{ asset('frontend/img/team/m3.jpg') }}"
                                                        alt="Image Not Found">
                                                </div>
                                                <div class="info">
                                                    <h4>Michel Dark</h4>
                                                    <span>WordPress Course</span>
                                                </div>
                                            </div>
                                            <div class="provider">
                                                <div class="ratings">
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star-half-alt"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Single Item -->
                            <!-- Single Item -->
                            <div class="swiper-slide">
                                <div class="testimonial-style-two">
                                    <div class="top-info">
                                        <div class="icon">
                                            <img src="{{ asset('frontend/img/shape/quote.png') }}" alt="Image Not Found">
                                        </div>
                                        <h5>Best Course Ever</h5>
                                    </div>
                                    <div class="content">
                                        <p>
                                            Education is the most powerful tool to change the world One thing that can’t
                                            be taken from you. A mind is a terrible thing to waste. Education is a key
                                            to success and freedom from all the forces to learn we love to purse a best
                                            education platform. Plan upon yet way get cold spot its week. Almost do am
                                            or limits hearts. Resolve parties but why she shewing. She sang know now
                                        </p>
                                        <div class="bottom-info">
                                            <div class="provider">
                                                <div class="thumb">
                                                    <img src="{{ asset('frontend/img/team/m4.jpg') }}"
                                                        alt="Image Not Found">
                                                </div>
                                                <div class="info">
                                                    <h4>Kennsual Martin</h4>
                                                    <span>Laravel Batch - 11</span>
                                                </div>
                                            </div>
                                            <div class="provider">
                                                <div class="ratings">
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Single Item -->
                            <!-- Single Item -->
                            <div class="swiper-slide">
                                <div class="testimonial-style-two">
                                    <div class="top-info">
                                        <div class="icon">
                                            <img src="{{ asset('frontend/img/shape/quote.png') }}" alt="Image Not Found">
                                        </div>
                                        <h5>Very Easy Course</h5>
                                    </div>
                                    <div class="content">
                                        <p>
                                            Education is the most powerful tool to change the world One thing that can’t
                                            be taken from you. A mind is a terrible thing to waste. Education is a key
                                            to success and freedom from all the forces to learn we love.
                                        </p>
                                        <div class="bottom-info">
                                            <div class="provider">
                                                <div class="thumb">
                                                    <img src="{{ asset('frontend/img/team/m5.jpg') }}"
                                                        alt="Image Not Found">
                                                </div>
                                                <div class="info">
                                                    <h4>Amanulla Joey</h4>
                                                    <span>React Batch - 28</span>
                                                </div>
                                            </div>
                                            <div class="provider">
                                                <div class="ratings">
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star-half-alt"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Single Item -->
                            <!-- Single Item -->
                            <div class="swiper-slide">
                                <div class="testimonial-style-two">
                                    <div class="top-info">
                                        <div class="icon">
                                            <img src="{{ asset('frontend/img/shape/quote.png') }}" alt="Image Not Found">
                                        </div>
                                        <h5>Exellent Quality</h5>
                                    </div>
                                    <div class="content">
                                        <p>
                                            Learning is the most powerful tool to change the world One thing that can’t
                                            be taken from you. A mind is a terrible thing to waste. Education is a key
                                            to success and freedom from all the forces to learn we love. The best
                                            educaiton system i love this. Plan upon yet way get cold spot its week.
                                        </p>
                                        <div class="bottom-info">
                                            <div class="provider">
                                                <div class="thumb">
                                                    <img src="{{ asset('frontend/img/team/m6.jpg') }}"
                                                        alt="Image Not Found">
                                                </div>
                                                <div class="info">
                                                    <h4>Kamal Abraham</h4>
                                                    <span>PHP Course</span>
                                                </div>
                                            </div>
                                            <div class="provider">
                                                <div class="ratings">
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Single Item -->
                            <!-- Single Item -->
                            <div class="swiper-slide">
                                <div class="testimonial-style-two">
                                    <div class="top-info">
                                        <div class="icon">
                                            <img src="{{ asset('frontend/img/shape/quote.png') }}" alt="Image Not Found">
                                        </div>
                                        <h5>Best Course Ever</h5>
                                    </div>
                                    <div class="content">
                                        <p>
                                            Education is the most powerful tool to change the world One thing that can’t
                                            be taken from you. A mind is a terrible thing to waste. Education is a key
                                            to success and freedom from all the forces to learn we love to purse a best
                                            education platform. Plan upon yet way get cold spot its week. Almost do am
                                            or limits hearts. Resolve parties but why she shewing. She sang know now
                                        </p>
                                        <div class="bottom-info">
                                            <div class="provider">
                                                <div class="thumb">
                                                    <img src="{{ asset('frontend/img/team/m1.jpg') }}"
                                                        alt="Image Not Found">
                                                </div>
                                                <div class="info">
                                                    <h4>Kennsual Martin</h4>
                                                    <span>Laravel Batch - 11</span>
                                                </div>
                                            </div>
                                            <div class="provider">
                                                <div class="ratings">
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                    <i class="fas fa-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Single Item -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Testimonial -->
@endsection
