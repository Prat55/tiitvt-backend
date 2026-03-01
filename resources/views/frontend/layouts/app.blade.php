<!DOCTYPE html>
<html lang="en">

<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="title" content="{{ $websiteSettings->getMetaTitle() }}">
    <meta name="keywords" content="{{ $websiteSettings->getMetaKeywords() }}">
    <meta name="description" content="{{ $websiteSettings->getMetaDescription() }}">
    <meta name="author" content="{{ $websiteSettings->getSettings()?->meta_author ?? 'Tiitvt' }}">

    <!-- ========== Page Title ========== -->
    <title>{{ $websiteSettings->getWebsiteName() }} | @yield('page_name')</title>

    <!-- ========== Favicon Icon ========== -->
    @if ($websiteSettings->getFaviconUrl())
        <link rel="shortcut icon" href="{{ $websiteSettings->getFaviconUrl() }}" type="image/x-icon">
    @else
        <link rel="shortcut icon" href="{{ asset('default/favicon.ico') }}" type="image/x-icon">
    @endif

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

    <style>
        :root {
            --gov-india-saffron: #ff9933;
            --gov-india-green: #138808;
            --gov-india-navy: #0b3d91;
            --gov-surface: #ffffff;
            --gov-page-bg: #f4f7fb;
            --gov-text: #1f2a37;
        }

        body.gov-ui-theme {
            background: var(--gov-page-bg);
            color: var(--gov-text);
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        }

        .gov-ui-theme .top-bar-area.top-bar-style-one {
            background: var(--gov-india-navy) !important;
            border-top: 3px solid var(--gov-india-saffron);
            border-bottom: 3px solid var(--gov-india-green);
        }

        .gov-ui-theme .top-bar-area ul li a {
            color: #ffffff !important;
            font-size: 13px;
            letter-spacing: 0.2px;
        }

        .gov-ui-theme nav.navbar {
            background: var(--gov-surface) !important;
            border-bottom: 1px solid #d7e0ee;
            box-shadow: none;
        }

        .gov-ui-theme .navbar.navbar-sticky::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gov-india-saffron);
        }

        .gov-ui-theme .navbar-brand img.logo,
        .gov-ui-theme .navbar .navbar-brand img {
            max-height: 62px;
            width: auto;
        }

        .gov-ui-theme nav.navbar .container {
            min-height: 86px;
            gap: 18px;
            align-items: center;
        }

        .gov-ui-theme nav.navbar .item-flex {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1 1 auto;
            min-width: 0;
        }

        .gov-ui-theme nav.navbar .navbar-header {
            margin-right: 0;
            flex-shrink: 0;
        }

        .gov-ui-theme nav.navbar .navbar-brand {
            padding: 8px 0;
            display: flex;
            align-items: center;
        }

        .gov-ui-theme nav.navbar .search-form {
            flex: 1 1 340px;
            max-width: 460px;
            margin: 0;
        }

        .gov-ui-theme nav.navbar .search-form .form-control {
            height: 42px;
            font-size: 14px;
            padding-left: 14px;
        }

        .gov-ui-theme nav.navbar .search-form button {
            min-width: 44px;
            height: 42px;
        }

        .gov-ui-theme nav.navbar .nav-item-box {
            gap: 18px;
            margin-left: auto;
            flex: 0 0 auto;
        }

        .gov-ui-theme nav.navbar .navbar-collapse {
            padding-left: 0;
            padding-right: 0;
        }

        .gov-ui-theme .navbar .nav>li>a {
            line-height: 1.2;
        }

        .gov-ui-theme nav.navbar .attr-nav>ul>li>a {
            padding: 0;
        }

        .gov-ui-theme .navbar .nav>li>a {
            color: var(--gov-india-navy) !important;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            font-size: 13px;
        }

        .gov-ui-theme .navbar .nav>li>a:hover,
        .gov-ui-theme .navbar .nav>li.active>a {
            color: var(--gov-india-saffron) !important;
        }

        .gov-ui-theme .search-form .form-control {
            border: 1px solid #c8d3e3;
            border-radius: 2px;
            background: #ffffff;
        }

        .gov-ui-theme .search-form button {
            background: var(--gov-india-navy);
            color: #ffffff;
        }

        .gov-ui-theme .attr-nav .menu-icon span {
            background-color: var(--gov-india-navy);
        }

        .gov-ui-theme .gov-main-wrapper {
            background: var(--gov-surface);
        }

        .gov-ui-theme footer.footer-style-one {
            background: #0e2d61 !important;
            border-top: 4px solid var(--gov-india-saffron);
            position: relative;
        }

        .gov-ui-theme footer.footer-style-one::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 4px;
            background: var(--gov-india-green);
        }

        .gov-ui-theme .footer-style-one .f-item p,
        .gov-ui-theme .footer-style-one .f-item a,
        .gov-ui-theme .footer-style-one .widget-title,
        .gov-ui-theme .footer-style-one h5 a {
            color: #f5f8ff !important;
        }

        .gov-ui-theme .footer-style-one .f-item a:hover,
        .gov-ui-theme .footer-style-one .link-list li a:hover {
            color: var(--gov-india-saffron) !important;
        }

        .gov-ui-theme .footer-bottom.style-one {
            background: #0a2148;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .gov-ui-theme .footer-social li a,
        .gov-ui-theme .widget.social .link li a {
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: transparent;
        }

        .gov-ui-theme .footer-social li a:hover,
        .gov-ui-theme .widget.social .link li a:hover {
            background: var(--gov-india-saffron);
            border-color: var(--gov-india-saffron);
            color: #102a43;
        }

        @media (max-width: 1199px) {
            .gov-ui-theme nav.navbar .container {
                min-height: 78px;
            }

            .gov-ui-theme nav.navbar .search-form {
                max-width: 340px;
            }

            .gov-ui-theme .navbar .nav>li>a {
                padding: 24px 10px;
                font-size: 12px;
            }
        }

        @media (max-width: 991px) {
            .gov-ui-theme nav.navbar .container {
                min-height: 72px;
                gap: 10px;
            }

            .gov-ui-theme nav.navbar .item-flex {
                gap: 10px;
            }

            .gov-ui-theme nav.navbar .search-form {
                flex: 1 1 auto;
                max-width: 250px;
            }

            .gov-ui-theme .navbar-brand img.logo,
            .gov-ui-theme .navbar .navbar-brand img {
                max-height: 56px;
            }

            .gov-ui-theme nav.navbar .nav-item-box {
                gap: 10px;
            }

            .gov-ui-theme nav.navbar .nav.navbar-nav {
                display: block;
            }

            .gov-ui-theme nav.navbar .nav.navbar-nav>li {
                display: block;
                width: 100%;
            }

            .gov-ui-theme nav.navbar .nav.navbar-nav>li>a {
                padding: 12px 18px;
                font-size: 13px;
            }
        }

        @media (max-width: 767px) {
            .gov-ui-theme nav.navbar .search-form {
                display: none;
            }

            .gov-ui-theme nav.navbar .container {
                min-height: 68px;
            }

            .gov-ui-theme .navbar-brand img.logo,
            .gov-ui-theme .navbar .navbar-brand img {
                max-height: 50px;
            }
        }

        @media (min-width: 992px) {
            .gov-ui-theme nav.navbar .nav-item-box {
                display: flex;
                align-items: center;
            }

            .gov-ui-theme nav.navbar .navbar-collapse.collapse {
                display: block !important;
                height: auto !important;
                padding-bottom: 0;
                overflow: visible !important;
            }

            .gov-ui-theme nav.navbar .nav.navbar-nav {
                display: flex;
                flex-direction: row !important;
                align-items: center;
                flex-wrap: nowrap;
                gap: 2px;
                margin: 0;
                float: none !important;
            }

            .gov-ui-theme nav.navbar .nav.navbar-nav>li {
                display: inline-block !important;
                float: none !important;
            }

            .gov-ui-theme nav.navbar .nav.navbar-nav>li>a {
                padding: 30px 12px;
                display: inline-block;
            }
        }
    </style>
</head>

<body class="gov-ui-theme">
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
                            <a href="tel:{{ $websiteSettings->getSettings()?->primary_phone ?? '' }}">
                                <img src="{{ asset('frontend/img/icon/2.png') }}" alt="Icon">
                                Phone: {{ $websiteSettings->getSettings()?->primary_phone ?? 'Not Available' }}
                            </a>
                        </li>
                        <li>
                            <a href="mailto:{{ $websiteSettings->getSettings()?->primary_email ?? '' }}">
                                <img src="{{ asset('frontend/img/icon/3.png') }}" alt="Icon">
                                Email: {{ $websiteSettings->getSettings()?->primary_email ?? 'Not Available' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('student.certificate.verify') }}">
                                Verify Certificate
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

    <div class="gov-main-wrapper">
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
                                    <a href="#courses">Courses</a>
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
                                            <a href="tel:{{ getWebsiteSettings()?->primary_phone ?? '' }}">
                                                {{ getWebsiteSettings()?->primary_phone ?? 'Not Available' }}
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
