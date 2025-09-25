<!DOCTYPE html>
<html lang="en">

<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="title" content="{{ getWebsiteSettings()->meta_title ?? 'Tiitvt' }}">
    <meta name="keywords" content="{{ getWebsiteSettings()->meta_keywords ?? 'Tiitvt' }}">
    <meta name="description" content="{{ getWebsiteSettings()->meta_description ?? 'Tiitvt' }}">
    <meta name="author" content="{{ getWebsiteSettings()->meta_author ?? 'Tiitvt' }}">

    <!-- ========== Page Title ========== -->
    <title>{{ getWebsiteName() }} | @yield('page_name')</title>

    <!-- ========== Favicon Icon ========== -->
    <link rel="shortcut icon" href="{{ asset('default/tiitvt_logo.svg') }}" type="image/x-icon">

    <!-- ========== Start Stylesheet ========== -->
    <link href="{{ asset('frontend/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/font-awesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/magnific-popup.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/swiper-bundle.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/animate.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/validnavs.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/helper.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/unit-test.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/style.css') }}" rel="stylesheet">
    <!-- ========== End Stylesheet ========== -->
    @stack('cdn')
</head>

<body>
    <!-- Start Preloader
    ============================================= -->
    <div id="preloader">
        <div id="tiitvt-preloader" class="tiitvt-preloader">
            <div class="animation-preloader">
                <div class="spinner"></div>
                <div class="txt-loading">
                    <span data-text-preloader="T" class="letters-loading">
                        T
                    </span>
                    <span data-text-preloader="I" class="letters-loading">
                        I
                    </span>
                    <span data-text-preloader="I" class="letters-loading">
                        I
                    </span>
                    <span data-text-preloader="T" class="letters-loading">
                        T
                    </span>
                    <span data-text-preloader="V" class="letters-loading">
                        V
                    </span>
                    <span data-text-preloader="T" class="letters-loading">
                        T
                    </span>
                </div>
            </div>
            <div class="loader">
                <div class="row">
                    <div class="col-3 loader-section section-left">
                        <div class="bg"></div>
                    </div>
                    <div class="col-3 loader-section section-left">
                        <div class="bg"></div>
                    </div>
                    <div class="col-3 loader-section section-right">
                        <div class="bg"></div>
                    </div>
                    <div class="col-3 loader-section section-right">
                        <div class="bg"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Preloader -->

    <!-- Start Header Top
    ============================================= -->
    <div class="top-bar-area top-bar-style-one bg-dark text-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <ul class="item-flex justify-content-end">
                        <li>
                            <a href="tel:{{ getWebsiteSettings()->primary_phone }}">
                                <img src="{{ asset('frontend/img/icon/2.png') }}" alt="Icon">
                                Phone: {{ getWebsiteSettings()->primary_phone }}
                            </a>
                        </li>
                        <li>
                            <a href="mailto:{{ getWebsiteSettings()->primary_email }}">
                                <img src="{{ asset('frontend/img/icon/3.png') }}" alt="Icon">
                                Email: {{ getWebsiteSettings()->primary_email }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('frontend.exam.login') }}">
                                Exam Login
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- End Header Top -->

    @include('frontend.layouts.header')

    <div>
        @yield('content')
    </div>

    <!-- Start Footer
    ============================================= -->
    <footer class="bg-dark footer-style-one text-light">
        <div class="footer-shape-style-one">
            <img src="{{ asset('frontend/img/shape/2-light.png') }}" alt="Image Not Found">
        </div>
        <div class="container">
            <div class="f-items default-padding">
                <div class="row">
                    <div class="col-lg-4 col-md-6 footer-item pr-30 pr-md-15 pr-xs-15">
                        <div class="f-item about">
                            <div class="footer-logo">
                                <img src="{{ asset('default/tiitvt_logo.svg') }}" alt="Image Not Found">
                            </div>
                            <p>
                                TIITVT – An ISO 9001:2015 certified skills training & certification body, registered
                                under MSME, Govt. of India, and a member of NBQP, QCI.
                            </p>
                            <ul class="footer-social">
                                <li>
                                    <a href="{{ getWebsiteSettings()->facebook_url }}">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ getWebsiteSettings()->twitter_url }}">
                                        <img src="{{ asset('frontend/img/icon/x.png') }}" alt="Icon">
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ getWebsiteSettings()->linkedin_url }}">
                                        <i class="fab fa-youtube"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ getWebsiteSettings()->instagram_url }}">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 footer-item">
                        <div class="f-item link">
                            <h4 class="widget-title">About</h4>
                            <ul>
                                <li>
                                    <a href="{{ route('frontend.about') }}">About Us</a>
                                </li>
                                <li>
                                    <a href="{{ route('frontend.contact') }}">Contact</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 footer-item">
                        <div class="f-item link">
                            <h4 class="widget-title">Quick Link</h4>
                            <ul>
                                <li>
                                    <a href="#0">Courses</a>
                                </li>
                                <li>
                                    <a href="#0">News & Blogs</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 footer-item">
                        <div class="f-item newsletter">
                            <h4 class="widget-title">Contact Info</h4>
                            <ul class="contact-list-two">
                                <li>
                                    <div class="icon">
                                        <i class="fas fa-phone-alt"></i>
                                    </div>
                                    <div class="info">
                                        <h5>
                                            <a href="tel:{{ getWebsiteSettings()->primary_phone }}">
                                                {{ getWebsiteSettings()->primary_phone }}
                                            </a>
                                        </h5>
                                    </div>
                                </li>
                                <li>
                                    <div class="icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="info">
                                        <h5>
                                            <a href="mailto:{{ getWebsiteSettings()->primary_email }}">
                                                {{ getWebsiteSettings()->primary_email }}
                                            </a>
                                        </h5>
                                    </div>
                                </li>
                            </ul>
                            {{-- <h4>Download TIITVT App</h4>
                            <ul class="app-store">
                                <li>
                                    <a href="#0">
                                        <img src="{{ asset('frontend/img/icon/4.png') }}" alt="Image Not Found">
                                    </a>
                                </li>
                                <li>
                                    <a href="#0">
                                        <img src="{{ asset('frontend/img/icon/5.png') }}" alt="Image Not Found">
                                    </a>
                                </li>
                                <li>
                                    <a href="#0">
                                        <img src="{{ asset('frontend/img/icon/6.png') }}" alt="Image Not Found">
                                    </a>
                                </li>
                            </ul> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Start Footer Bottom -->
        <div class="footer-bottom style-one">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        <p>
                            &copy; Copyright {{ date('Y') }}. All Rights Reserved by
                            <a href="{{ route('frontend.index') }}">
                                {{ getWebsiteSettings()->meta_author }}
                            </a>
                        </p>
                    </div>
                    <div class="col-lg-6 text-end">
                        <ul class="link-list">
                            <li>
                                <a href="#0">Terms</a>
                            </li>
                            <li>
                                <a href="#0">Privacy</a>
                            </li>
                            <li>
                                <a href="#0">Support</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Footer Bottom -->
    </footer>
    <!-- End Footer -->

    <!-- jQuery Frameworks
    ============================================= -->
    <script src="{{ asset('frontend/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('frontend/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('frontend/js/jquery.appear.js') }}"></script>
    <script src="{{ asset('frontend/js/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('frontend/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('frontend/js/progress-bar.min.js') }}"></script>
    <script src="{{ asset('frontend/js/isotope.pkgd.min.js') }}"></script>
    <script src="{{ asset('frontend/js/imagesloaded.pkgd.min.js') }}"></script>
    <script src="{{ asset('frontend/js/magnific-popup.min.js') }}"></script>
    <script src="{{ asset('frontend/js/count-to.js') }}"></script>
    <script src="{{ asset('frontend/js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('frontend/js/wow.min.js') }}"></script>
    <script src="{{ asset('frontend/js/YTPlayer.min.js') }}"></script>
    <script src="{{ asset('frontend/js/loopcounter.js') }}"></script>
    <script src="{{ asset('frontend/js/validnavs.js') }}"></script>
    <script src="{{ asset('frontend/js/gsap.js') }}"></script>
    <script src="{{ asset('frontend/js/ScrollTrigger.min.js') }}"></script>
    <script src="{{ asset('frontend/js/SplitText.min.js') }}"></script>
    <script src="{{ asset('frontend/js/main.js') }}"></script>
    @yield('scripts')
</body>

</html>
