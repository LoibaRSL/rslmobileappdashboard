<div class="sidenav-menu">
    <!-- Brand Logo -->
    <a class="logo" href="{{ url("/") }}">
        <span class="logo logo-light">
            <span class="logo-lg"><img alt="logo" src="/images/logo.png" /></span>
            <span class="logo-sm"><img alt="small logo" src="/images/logo-sm.png" /></span>
        </span>
        <span class="logo logo-dark">
            <span class="logo-lg"><img alt="dark logo" src="/images/logo-black.png" /></span>
            <span class="logo-sm"><img alt="small logo" src="/images/logo-sm.png" /></span>
        </span>
    </a>
    <!-- Sidebar Hover Menu Toggle Button -->
    <button class="button-on-hover">
        <span class="btn-on-hover-icon"></span>
    </button>
    <!-- Full Sidebar Menu Close Button -->
    <button class="button-close-offcanvas">
        <i class="align-middle" data-lucide="menu"></i>
    </button>
    <div class="scrollbar" data-simplebar="">
        <div class="sidenav-user" id="user-profile-settings" style="background: url(/images/user-bg-pattern.svg)">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a class="link-reset" href="#!">
                        <img alt="user-image" class="rounded-circle mb-2 avatar-md" src="/images/users/user-1.jpg" />
                        <span class="sidenav-user-name fw-bold">Geneva K.</span>
                        <span class="fs-12 fw-semibold" data-lang="user-role">Art Director</span>
                    </a>
                </div>
                <div>
                    <a aria-expanded="false" aria-haspopup="false" class="dropdown-toggle drop-arrow-none link-reset sidenav-user-set-icon" data-bs-offset="0,12" data-bs-toggle="dropdown" href="#!">
                        <i class="fs-24 align-middle ms-1" data-lucide="settings"></i>
                    </a>
                    <div class="dropdown-menu">
                        <!-- Header -->
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome back!</h6>
                        </div>
                        <!-- My Profile -->
                        <a class="dropdown-item" href="#!">
                            <i class="me-1 fs-lg align-middle" data-lucide="circle-user-round"></i>
                            <span class="align-middle">Profile</span>
                        </a>
                        <!-- Settings -->
                        <a class="dropdown-item" href="javascript:void(0);">
                            <i class="me-1 fs-lg align-middle" data-lucide="bolt"></i>
                            <span class="align-middle">Account Settings</span>
                        </a>
                        <!-- Lock -->
                        <a class="dropdown-item" href="{{ url("/auth/lock-screen") }}">
                            <i class="me-1 fs-lg align-middle" data-lucide="lock-keyhole"></i>
                            <span class="align-middle">Lock Screen</span>
                        </a>
                        <!-- Logout -->
                        <a class="dropdown-item text-danger fw-semibold" href="javascript:void(0);">
                            <i class="me-1 fs-lg align-middle" data-lucide="log-out"></i>
                            <span class="align-middle">Log Out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!--- Sidenav Menu -->
        <div id="sidenav-menu">
            <ul class="side-nav">
                <li class="side-nav-title mt-2" data-lang="main">Main</li>
                <li class="side-nav-item">
                    <a class="side-nav-link" href="{{ url("/") }}">
                        <span class="menu-icon"><i data-lucide="layout-dashboard"></i></span>
                        <span class="menu-text" >Dashboard</span>
                    </a>
                </li>
               <!-- <li class="side-nav-title mt-2" data-lang="custom-pages">Custom Pages</li>
                <li class="side-nav-item">
                    <a class="side-nav-link" href="{{ url("/pages/empty") }}">
                        <span class="menu-icon"><i data-lucide="book-open-text"></i></span>
                        <span class="menu-text" data-lang="pages-empty">Empty Page</span>
                    </a>
                </li>-->
                <li class="side-nav-item">
                    <a aria-controls="authentication" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#authentication">
                        <span class="menu-icon"><i data-lucide="file-user"></i></span>
                        <span class="menu-text" >TIN Applications</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="authentication">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a aria-controls="auth-basic" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#auth-basic">
                                    <span class="menu-text" >Individusal</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="auth-basic">
                                    <ul class="sub-menu">
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ url("/auth/sign-in") }}">
                                                <span class="menu-text" >Pending</span>
                                            </a>
                                        </li>
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ url("/auth/sign-up") }}">
                                                <span class="menu-text" >Approved</span>
                                            </a>
                                        </li>
                                        
                                    </ul>
                                </div>
                            </li>
                            <li class="side-nav-item">
                                <a aria-controls="auth-split" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#auth-split">
                                    <span class="menu-text" >Business</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="auth-split">
                                    <ul class="sub-menu">
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ url("/auth-split/sign-in") }}">
                                                <span class="menu-text" >Pending</span>
                                            </a>
                                        </li>
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ url("/auth-split/sign-up") }}">
                                                <span class="menu-text" >Approved</span>
                                            </a>
                                        </li>
                                        
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="side-nav-item">
                    <a aria-controls="error-pages" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#error-pages">
                        <span class="menu-icon"><i data-lucide="book-check"></i></span>
                        <span class="menu-text" >Amendments</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="error-pages">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a aria-controls="auth-basic" aria-expanded="true" class="side-nav-link" data-bs-toggle="collapse" href="#auth-basic">
                                    <span class="menu-text" >Individusal</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="indivstatuse">
                                    <ul class="sub-menu">
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ url("/auth/sign-in") }}">
                                                <span class="menu-text" >Pending</span>
                                            </a>
                                        </li>
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ url("/auth/sign-up") }}">
                                                <span class="menu-text" >Approved</span>
                                            </a>
                                        </li>
                                        
                                    </ul>
                                </div>
                            </li>
                            <li class="side-nav-item">
                                <a aria-controls="auth-split" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#auth-split">
                                    <span class="menu-text" >Business</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="statusbusiness">
                                    <ul class="sub-menu">
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ url("/auth-split/sign-in") }}">
                                                <span class="menu-text" >Pending</span>
                                            </a>
                                        </li>
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ url("/auth-split/sign-up") }}">
                                                <span class="menu-text" >Approved</span>
                                            </a>
                                        </li>
                                        
                                    </ul>
                                </div>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ url("/error/403") }}">
                                    <span class="menu-text" >Graduation</span>
                                </a>
                            </li>

                        </ul>
                    </div>
                </li>
                <li class="side-nav-title mt-2" >Returns</li>
                <li class="side-nav-item">
                    <a aria-controls="layout-options" aria-expanded="false" class="side-nav-link"  href="#layout-options">
                        <span class="menu-icon"><i data-lucide="map-pin-house"></i></span>
                        <span class="menu-text" >Resident Individual Tax</span>
                       
                    </a>
                    
                </li>



                <li class="side-nav-title mt-2" >Reports</li>
                <li class="side-nav-item">
                    <a aria-controls="layout-options" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#layout-options">
                        <span class="menu-icon"><i data-lucide="book-open-text"></i></span>
                        <span class="menu-text" >Registration</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="layout-options">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ url("/layouts/scrollable") }}" target="_blank">
                                    <span class="menu-text" >Individual</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ url("/layouts/compact") }}" target="_blank">
                                    <span class="menu-text" >Business</span>
                                </a>
                            </li>
                            
                        </ul>
                    </div>
                </li>
                <li class="side-nav-item">
                    <a aria-controls="sidebars" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#sidebars">
                        <span class="menu-icon"><i data-lucide="book-check"></i></span>
                        <span class="menu-text" >Amendments</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebars">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ url("/layouts/sidebar/dark") }}" target="_blank">
                                    <span class="menu-text" >Individual</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ url("/layouts/sidebar/gradient") }}" target="_blank">
                                    <span class="menu-text" >Business</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ url("/layouts/sidebar/gray") }}" target="_blank">
                                    <span class="menu-text" >Graduation</span>
                                </a>
                            </li>
                            
                        </ul>
                    </div>
                </li>
               
               
            </ul>
        </div>
    </div>
</div>
<!-- Sidenav Menu End -->
