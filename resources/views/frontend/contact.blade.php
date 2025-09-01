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
                        <form action="{{ asset('frontend/mail/contact.php') }}" method="POST"
                            class="contact-form contact-form">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" id="name" name="name" placeholder="Name *"
                                            type="text">
                                        <span class="alert-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <input class="form-control" id="email" name="email" placeholder="Email*"
                                            type="email">
                                        <span class="alert-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <input class="form-control" id="phone" name="phone" placeholder="Phone"
                                            type="text">
                                        <span class="alert-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group comments">
                                        <textarea class="form-control" id="comments" name="message" placeholder="Write a message *"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <button type="submit" name="submit" id="submit">
                                        <i class="fa fa-paper-plane"></i> Get in Touch
                                    </button>
                                </div>
                            </div>
                            <!-- Alert Message -->
                            <div class="col-lg-12 alert-notification">
                                <div id="message" class="alert-msg"></div>
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
                                            <a href="tel:+4733378901">+4733378901</a>
                                        </li>
                                        <li>
                                            <a href="tel:+1433378912">+1433378912</a>
                                        </li>
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
                                        55 Main Street, The Grand Avenue 2nd Block, New York City
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
                                            <a href="mailto:info@tiitvt.com">info@tiitvt.com</a>
                                        </li>
                                        <li>
                                            <a href="mailto:support@tiitvt.com">support@tiitvt.com</a>
                                        </li>
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
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d48388.929990966964!2d-74.00332!3d40.711233!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY!5e0!3m2!1sen!2sus!4v1653598669477!5m2!1sen!2sus"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Map -->
@endsection
