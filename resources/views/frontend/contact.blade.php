@extends('frontend.layouts.app')
@section('page_name', 'Contact Us')
@section('content')
    <!-- Start Breadcrumb -->
    <div class="breadcrumb-area text-center bg-gray-gradient-secondary">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <h1>Get in Touch</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                            <li class="active">Contact</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- End Breadcrumb -->

    <!-- Start Contact Us -->
    <div class="contact-style-one-area overflow-hidden default-padding-bottom">
        <div class="container">
            <div class="row">
                <div class="contact-stye-one col-lg-10 offset-lg-1">
                    <div class="contact-form-style-one">
                        <h2 class="heading">Send us a message</h2>

                        @if (session('success'))
                            <div class="alert alert-success mt-3">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger mt-3">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form action="{{ route('frontend.contact_submit') }}" method="POST"
                            class="contact-form contact-form">
                            @csrf
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" id="name" name="name" placeholder="Name *"
                                            type="text">
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <input class="form-control" id="email" name="email" placeholder="Email*"
                                            type="email">
                                        @error('email')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <input class="form-control" id="phone" name="phone" placeholder="Phone"
                                            type="text">
                                        @error('phone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group comments">
                                        <textarea class="form-control" id="comments" name="message" placeholder="Write a message *"></textarea>
                                        @error('message')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <button type="submit" onclick="this.disabled=true; this.form.submit();">
                                        <i class="fa fa-paper-plane"></i> Get in Touch
                                    </button>
                                </div>
                            </div>
                        </form>
                        <img src="{{ asset('frontend/img/shape/88.png') }}" alt="Image Not Found">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Contact -->

    <!-- Start Contact Information -->
    <div class="contact-info-area overflow-hidden default-padding-bottom pt-20 mt--20">
        <div class="container">
            <div class="row">
                <div class="contact-stye-one col-lg-10 offset-lg-1">
                    <div class="contact-style-one-info">
                        <div class="heading text-center">
                            <h4 class="sub-title">Have Questions?</h4>
                            <h2 class="title">Contact Information</h2>
                        </div>
                        <div class="contact-info-items">
                            <div class="item-single wow fadeInUp">
                                <div class="icon">
                                    <img src="{{ asset('frontend/img/icon/68.png') }}" alt="Image Not Found">
                                </div>
                                <div class="content">
                                    <h4>Contact Number</h4>
                                    <ul>
                                        <li>
                                            <a href="tel:{{ getWebsitePrimaryPhone() }}">
                                                {{ getWebsitePrimaryPhone() }}
                                            </a>
                                        </li>
                                        @if (getWebsiteSecondaryPhone())
                                            <li>
                                                <a href="tel:{{ getWebsiteSecondaryPhone() }}">
                                                    {{ getWebsiteSecondaryPhone() }}
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                            <div class="item-single wow fadeInUp" data-wow-delay="300ms">
                                <div class="icon">
                                    <img src="{{ asset('frontend/img/icon/70.png') }}" alt="Image Not Found">
                                </div>
                                <div class="info">
                                    <h4>Our Location</h4>
                                    <p>
                                        {{ getWebsiteSettings()->address ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>
                            <div class="item-single wow fadeInUp" data-wow-delay="500ms">
                                <div class="icon">
                                    <img src="{{ asset('frontend/img/icon/69.png') }}" alt="Image Not Found">
                                </div>
                                <div class="info">
                                    <h4>Official Email</h4>
                                    <ul>
                                        <li>
                                            <a href="mailto:{{ getWebsitePrimaryEmail() }}">
                                                {{ getWebsitePrimaryEmail() }}
                                            </a>
                                        </li>

                                        @if (getWebsiteSecondaryEmail())
                                            <li>
                                                <a href="mailto:{{ getWebsiteSecondaryEmail() }}">
                                                    {{ getWebsiteSecondaryEmail() }}
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Contact Information -->

    <!-- Start Map -->
    <div class="maps-area overflow-hidden default-padding-bottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 offset-lg-1">
                    <div class="google-maps">
                        @if (getWebsiteSettings()?->map_embed_url)
                            <iframe src="{{ getWebsiteSettings()->map_embed_url }}"></iframe>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Map -->
@endsection
