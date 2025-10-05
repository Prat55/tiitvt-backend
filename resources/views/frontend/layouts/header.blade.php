 <!-- Header -->
 <header>
     <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
         @csrf
     </form>
     <!-- Start Navigation -->
     <nav
         class="navbar mobile-sidenav navbar-sticky navbar-default validnavs dark navbar-fixed no-background inc-topbar">
         <div class="container d-flex justify-content-between align-items-center">

             <!-- Start Header Navigation -->
             <div class="item-flex">
                 <div class="navbar-header">
                     <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                         <i class="fa fa-bars"></i>
                     </button>
                     <a class="navbar-brand" href="{{ route('frontend.index') }}">
                         <img src="{{ asset('default/tiitvt_logo.svg') }}" class="logo" alt="Logo">
                     </a>
                 </div>
                 <form class="search-form" action="#">
                     <input type="text" placeholder="Search" class="form-control" name="text">
                     <button type="submit">
                         <i class="fa fa-search"></i>
                     </button>
                 </form>
             </div>
             <!-- End Header Navigation -->

             <div class="nav-item-box d-flex justify-content-between align-items-center">
                 <!-- Collect the nav links, forms, and other content for toggling -->
                 <div class="collapse navbar-collapse" id="navbar-menu">

                     <img src="{{ asset('default/tiitvt_logo.svg') }}" alt="Logo">
                     <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                         <i class="fa fa-times"></i>
                     </button>

                     <ul class="nav navbar-nav navbar-right" data-in="fadeInDown" data-out="fadeOutUp">
                         <li><a href="{{ route('frontend.index') }}">Home</a></li>
                         <li><a href="{{ route('frontend.about') }}">About</a></li>
                         <li><a href="{{ route('frontend.index') }}">Courses</a></li>
                         <li><a href="{{ route('frontend.contact') }}">Contact</a></li>
                         @guest
                             <li><a href="{{ route('login') }}">Login</a></li>
                             <li><a href="{{ route('login') }}">Center Login</a></li>
                         @endguest

                         @auth
                             <li><a href="{{ route('admin.index') }}">Dashboard</a></li>
                             <li>
                                 <a href="#"
                                     onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                     Logout
                                 </a>
                             </li>
                         @endauth
                     </ul>
                 </div><!-- /.navbar-collapse -->

                 <div class="attr-right">
                     <!-- Start Atribute Navigation -->
                     <div class="attr-nav">
                         <ul>
                             <li class="side-menu">
                                 <a href="#">
                                     <div class="menu-icon">
                                         <span class="bar-1"></span>
                                         <span class="bar-2"></span>
                                         <span class="bar-3"></span>
                                     </div>
                                 </a>
                             </li>
                         </ul>
                     </div>
                     <!-- End Atribute Navigation -->
                 </div>
             </div>

             <!-- Start Side Menu -->
             <div class="side">
                 <a href="#" class="close-side"><i class="fas fa-times"></i></a>
                 <div class="widget">
                     <div class="logo">
                         <img src="{{ asset('default/tiitvt_logo.svg') }}" alt="Logo">
                     </div>
                     <p>
                         With a strong vision to make technical and vocational training accessible to all, TIITVT not
                         only supports students in building successful careers but also promotes local entrepreneurs to
                         establish training centers that contribute to skill development and economic growth.
                     </p>
                 </div>
                 <div class="widget address">
                     <div>
                         <ul>
                             <li>
                                 <div class="content">
                                     <p>Address</p>
                                     <strong>{{ getWebsiteSettings()->address }}</strong>
                                 </div>
                             </li>
                             <li>
                                 <div class="content">
                                     <p>Email</p>
                                     <strong>{{ getWebsiteSettings()->primary_email }}</strong>
                                 </div>
                             </li>
                             <li>
                                 <div class="content">
                                     <p>Contact</p>
                                     <strong>{{ getWebsiteSettings()?->primary_phone ?? 'Not Available' }}</strong>
                                 </div>
                             </li>
                         </ul>
                     </div>
                 </div>
                 <div class="widget newsletter">
                     <h4 class="title">Get Subscribed!</h4>
                     <form action="#">
                         <div class="input-group stylish-input-group">
                             <input type="email" placeholder="Enter your e-mail" class="form-control" name="email">
                             <span class="input-group-addon">
                                 <button type="submit">
                                     <i class="fa fa-long-arrow-right"></i>
                                 </button>
                             </span>
                         </div>
                     </form>
                 </div>
                 <div class="widget social">
                     <ul class="link">
                         <li>
                             <a href="{{ getWebsiteSettings()->facebook_url }}">
                                 <i class="fab fa-facebook-f"></i>
                             </a>
                         </li>

                         <li>
                             <a href="{{ getWebsiteSettings()->twitter_url }}">
                                 <i class="fab fa-twitter"></i>
                             </a>
                         </li>

                         <li>
                             <a href="{{ getWebsiteSettings()->linkedin_url }}">
                                 <i class="fab fa-linkedin-in"></i>
                             </a>
                         </li>

                         <li>
                             <a href="{{ getWebsiteSettings()->instagram_url }}">
                                 <i class="fab fa-instagram"></i>
                             </a>
                         </li>
                     </ul>
                 </div>

             </div>
             <!-- End Side Menu -->

         </div>
         <!-- Overlay screen for menu -->
         <div class="overlay-screen"></div>
         <!-- End Overlay screen for menu -->
     </nav>
     <!-- End Navigation -->
 </header>
 <!-- End Header -->
