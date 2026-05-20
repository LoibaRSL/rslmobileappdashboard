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
                        <span class="sidenav-user-name fw-bold">{{ auth()->user()->name ?? 'User' }}</span>
                        <span class="fs-12 fw-semibold" data-lang="user-role">
                            @php
                                $user = auth()->user();
                                if($user && $user->roles->count() > 0) {
                                    echo $user->roles->first()->display_name;
                                } else {
                                    echo 'User';
                                }
                            @endphp
                        </span>
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
                        <form method="POST" action="{{ route('auth.logout') }}" style="display: inline;">
                            @csrf
                            <a class="dropdown-item text-danger fw-semibold" href="javascript:void(0);" onclick="this.closest('form').submit();">
                                <i class="me-1 fs-lg align-middle" data-lucide="log-out"></i>
                                <span class="align-middle">Log Out</span>
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--- Sidenav Menu -->
        <div id="sidenav-menu">
            <ul class="side-nav">
                <li class="side-nav-title mt-2" data-lang="main">Main</li>
                <li class="side-nav-item">
                    <a class="side-nav-link" href="{{ url("/dashboard") }}">
                        <span class="menu-icon"><i data-lucide="layout-dashboard"></i></span>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>
               
                <!-- TIN Applications Section -->
                <li class="side-nav-item">
                    <a aria-controls="tin-applications" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#tin-applications">
                        <span class="menu-icon"><i data-lucide="file-user"></i></span>
                        <span class="menu-text">TIN Applications</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="tin-applications">
                        <ul class="sub-menu">
                            <!-- Individual Applications -->
                            <li class="side-nav-item">
                                <a aria-controls="individual-apps" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#individual-apps">
                                    <span class="menu-text">Individual</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="individual-apps">
                                    <ul class="sub-menu">
                                   
                                            <!-- In your navigation, use these route names -->
<a class="side-nav-link" href="{{ route('ds.registrations.all') }}">
    <span class="menu-text">All Submissions</span>
</a>

<a class="side-nav-link" href="{{ route('ds.registrations.unassigned') }}">
    <span class="menu-text">Pending (Unassigned)</span>
</a>

<a class="side-nav-link" href="{{ route('ds.registrations.approved') }}">
    <span class="menu-text">Approved</span>
</a>

<a class="side-nav-link" href="{{ route('ds.registrations.rejected') }}">
    <span class="menu-text">Rejected</span>
</a>

<a class="side-nav-link" href="{{ route('ds.registrations.my-assignments') }}">
    <span class="menu-text">My Assignments</span>
</a>
                                    </ul>
                                </div>
                            </li>
                            
                            <!-- Business Applications -->
                            <li class="side-nav-item">
                                <a aria-controls="business-apps" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#business-apps">
                                    <span class="menu-text">Business</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse" id="business-apps">
                                    <ul class="sub-menu">
                                        <!-- All Applications -->
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ route('tin.business.index') }}">
                                                <span class="menu-text">All Applications</span>
                                              
                                                   
                                               
                                            </a>
                                        </li>
                                        
                                        <!-- Pending Applications -->
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ route('tin.business.pending') }}">
                                                
                                            </a>
                                        </li>
                                        
                                        <!-- Approved Applications -->
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ route('tin.business.approved') }}">
                                                <span class="menu-text">Approved</span>
                                            </a>
                                        </li>
                                        
                                        <!-- Rejected Applications -->
                                        <li class="side-nav-item">
                                            <a class="side-nav-link" href="{{ route('tin.business.rejected') }}">
                                                <span class="menu-text">Rejected</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <!-- Amendments Section -->
                <li class="side-nav-item">
                    <a aria-controls="amendments" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#amendments">
                        <span class="menu-icon"><i data-lucide="book-check"></i></span>
                        <span class="menu-text">Amendments</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="amendments">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ route('amendments.individual') }}">
                                    <span class="menu-text">Individual</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ route('amendments.business') }}">
                                    <span class="menu-text">Business</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ route('amendments.graduation') }}">
                                    <span class="menu-text">Graduation</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <!-- Returns Section -->
                <li class="side-nav-title mt-2">Returns</li>
                <li class="side-nav-item">
                    <a class="side-nav-link" href="{{ route('returns.resident-tax') }}">
                        <span class="menu-icon"><i data-lucide="map-pin-house"></i></span>
                        <span class="menu-text">Resident Individual Tax</span>
                    </a>
                </li>
                
                <!-- Reports Section -->
                <li class="side-nav-title mt-2">Reports</li>
                <li class="side-nav-item">
                    <a aria-controls="reports-registration" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#reports-registration">
                        <span class="menu-icon"><i data-lucide="book-open-text"></i></span>
                        <span class="menu-text">Registration</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="reports-registration">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ route('reports.registration.individual') }}">
                                    <span class="menu-text">Individual</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ route('reports.registration.business') }}">
                                    <span class="menu-text">Business</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <li class="side-nav-item">
                    <a aria-controls="reports-amendments" aria-expanded="false" class="side-nav-link" data-bs-toggle="collapse" href="#reports-amendments">
                        <span class="menu-icon"><i data-lucide="book-check"></i></span>
                        <span class="menu-text">Amendments</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="reports-amendments">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ route('reports.amendments.individual') }}">
                                    <span class="menu-text">Individual</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ route('reports.amendments.business') }}">
                                    <span class="menu-text">Business</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link" href="{{ route('reports.amendments.graduation') }}">
                                    <span class="menu-text">Graduation</span>
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