<style>
    .sidenav-menu > .logo {
        min-height: 66px;
        line-height: 66px;
        padding-inline: 18px;
    }

    .sidenav-menu > .logo .logo-lg img {
        height: 38px;
        max-width: 176px;
        object-fit: contain;
    }

    .sidenav-menu > .logo .logo-sm img {
        height: 26px;
        object-fit: contain;
    }

    .side-nav-link.active {
        background: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
        font-weight: 700;
    }
</style>

@php
    $routeName = request()->route()?->getName() ?? '';
    $isAdminMenu = request()->routeIs('admin.users.*', 'admin.roles.*');
    $isTinMenu = request()->routeIs('ds.registrations.*', 'ds.business-registrations.*');
    $isIndividualTinMenu = request()->routeIs('ds.registrations.*');
    $isBusinessTinMenu = request()->routeIs('ds.business-registrations.*');
    $isOperationsMenu = request()->routeIs('ds.operations.*');
    $isAmendmentsMenu = request()->routeIs('amendments.*');
    $isReportsRegistrationMenu = request()->routeIs('reports.registration.*');
    $isReportsAmendmentsMenu = request()->routeIs('reports.amendments.*');
    $isAiReportsMenu = request()->routeIs('reports.ai-builder*');
@endphp

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
                        @include('shared.partials.user-avatar', ['user' => auth()->user(), 'size' => 48, 'class' => 'mb-2 avatar-md'])
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
                    <a class="side-nav-link {{ request()->routeIs('dashboard', 'ds.dashboard') ? 'active' : '' }}" href="{{ url("/dashboard") }}">
                        <span class="menu-icon"><i data-lucide="layout-dashboard"></i></span>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>

                @if(auth()->check() && (auth()->user()->hasPermission('users.view') || auth()->user()->hasPermission('roles.view')))
                <li class="side-nav-title mt-2">Admin</li>
                <li class="side-nav-item">
                    <a aria-controls="admin-management" aria-expanded="{{ $isAdminMenu ? 'true' : 'false' }}" class="side-nav-link {{ $isAdminMenu ? 'active' : '' }}" data-bs-toggle="collapse" href="#admin-management">
                        <span class="menu-icon"><i data-lucide="shield-check"></i></span>
                        <span class="menu-text">User Management</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse {{ $isAdminMenu ? 'show' : '' }}" id="admin-management">
                        <ul class="sub-menu">
                            @if(auth()->user()->hasPermission('users.view'))
                            <li class="side-nav-item">
                                <a class="side-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                    <span class="menu-text">Users</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('roles.view'))
                            <li class="side-nav-item">
                                <a class="side-nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                                    <span class="menu-text">Roles</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif
               
                @if(auth()->check() && auth()->user()->isDigitalServices())
                <li class="side-nav-title mt-2">Operations</li>
                <li class="side-nav-item">
                    <a aria-controls="tin-applications" aria-expanded="{{ $isTinMenu ? 'true' : 'false' }}" class="side-nav-link {{ $isTinMenu ? 'active' : '' }}" data-bs-toggle="collapse" href="#tin-applications">
                        <span class="menu-icon"><i data-lucide="file-user"></i></span>
                        <span class="menu-text">TIN Applications</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse {{ $isTinMenu ? 'show' : '' }}" id="tin-applications">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a aria-controls="individual-apps" aria-expanded="{{ $isIndividualTinMenu ? 'true' : 'false' }}" class="side-nav-link {{ $isIndividualTinMenu ? 'active' : '' }}" data-bs-toggle="collapse" href="#individual-apps">
                                    <span class="menu-text">Individual</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse {{ $isIndividualTinMenu ? 'show' : '' }}" id="individual-apps">
                                    <ul class="sub-menu">
                                        <li class="side-nav-item"><a class="side-nav-link {{ request()->routeIs('ds.registrations.all') ? 'active' : '' }}" href="{{ route('ds.registrations.all') }}"><span class="menu-text">All Submissions</span></a></li>
                                        <li class="side-nav-item"><a class="side-nav-link {{ request()->routeIs('ds.registrations.unassigned') ? 'active' : '' }}" href="{{ route('ds.registrations.unassigned') }}"><span class="menu-text">Pending (Unassigned)</span></a></li>
                                        <li class="side-nav-item"><a class="side-nav-link {{ request()->routeIs('ds.registrations.approved') ? 'active' : '' }}" href="{{ route('ds.registrations.approved') }}"><span class="menu-text">Approved</span></a></li>
                                        <li class="side-nav-item"><a class="side-nav-link {{ request()->routeIs('ds.registrations.rejected') ? 'active' : '' }}" href="{{ route('ds.registrations.rejected') }}"><span class="menu-text">Rejected</span></a></li>
                                        <li class="side-nav-item"><a class="side-nav-link {{ request()->routeIs('ds.registrations.my-assignments') ? 'active' : '' }}" href="{{ route('ds.registrations.my-assignments') }}"><span class="menu-text">My Assignments</span></a></li>
                                    </ul>
                                </div>
                            </li>
                            <li class="side-nav-item">
                                <a aria-controls="business-apps" aria-expanded="{{ $isBusinessTinMenu ? 'true' : 'false' }}" class="side-nav-link {{ $isBusinessTinMenu ? 'active' : '' }}" data-bs-toggle="collapse" href="#business-apps">
                                    <span class="menu-text">Business</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <div class="collapse {{ $isBusinessTinMenu ? 'show' : '' }}" id="business-apps">
                                    <ul class="sub-menu">
                                        <li class="side-nav-item"><a class="side-nav-link {{ request()->routeIs('ds.business-registrations.all') ? 'active' : '' }}" href="{{ route('ds.business-registrations.all') }}"><span class="menu-text">All Submissions</span></a></li>
                                        <li class="side-nav-item"><a class="side-nav-link {{ request()->routeIs('ds.business-registrations.unassigned') ? 'active' : '' }}" href="{{ route('ds.business-registrations.unassigned') }}"><span class="menu-text">Pending (Unassigned)</span></a></li>
                                        <li class="side-nav-item"><a class="side-nav-link {{ request()->routeIs('ds.business-registrations.approved') ? 'active' : '' }}" href="{{ route('ds.business-registrations.approved') }}"><span class="menu-text">Approved</span></a></li>
                                        <li class="side-nav-item"><a class="side-nav-link {{ request()->routeIs('ds.business-registrations.rejected') ? 'active' : '' }}" href="{{ route('ds.business-registrations.rejected') }}"><span class="menu-text">Rejected</span></a></li>
                                        <li class="side-nav-item"><a class="side-nav-link {{ request()->routeIs('ds.business-registrations.my-assignments') ? 'active' : '' }}" href="{{ route('ds.business-registrations.my-assignments') }}"><span class="menu-text">My Assignments</span></a></li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="side-nav-item">
                    <a class="side-nav-link {{ request()->routeIs('ds.operations.failed-soap') ? 'active' : '' }}" href="{{ route('ds.operations.failed-soap') }}">
                        <span class="menu-icon"><i data-lucide="rotate-ccw"></i></span>
                        <span class="menu-text">SOAP Retry Queue</span>
                    </a>
                </li>
                <li class="side-nav-item">
                    <a class="side-nav-link {{ request()->routeIs('ds.operations.sla') ? 'active' : '' }}" href="{{ route('ds.operations.sla') }}">
                        <span class="menu-icon"><i data-lucide="timer"></i></span>
                        <span class="menu-text">SLA Monitor</span>
                    </a>
                </li>

                <!-- Amendments Section -->
                <li class="side-nav-item">
                    <a aria-controls="amendments" aria-expanded="{{ $isAmendmentsMenu ? 'true' : 'false' }}" class="side-nav-link {{ $isAmendmentsMenu ? 'active' : '' }}" data-bs-toggle="collapse" href="#amendments">
                        <span class="menu-icon"><i data-lucide="book-check"></i></span>
                        <span class="menu-text">Amendments</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse {{ $isAmendmentsMenu ? 'show' : '' }}" id="amendments">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a class="side-nav-link {{ request()->routeIs('amendments.individual') ? 'active' : '' }}" href="{{ route('amendments.individual') }}">
                                    <span class="menu-text">Individual</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link {{ request()->routeIs('amendments.business') ? 'active' : '' }}" href="{{ route('amendments.business') }}">
                                    <span class="menu-text">Business</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif
                
                <!-- Returns Section -->
                <li class="side-nav-title mt-2">Returns</li>
                <li class="side-nav-item">
                    <a class="side-nav-link {{ request()->routeIs('returns.*') ? 'active' : '' }}" href="{{ route('returns.resident-tax') }}">
                        <span class="menu-icon"><i data-lucide="map-pin-house"></i></span>
                        <span class="menu-text">Resident Individual Tax</span>
                    </a>
                </li>
                
                @if(auth()->check() && auth()->user()->isDigitalServices())
                <!-- Reports Section -->
                <li class="side-nav-title mt-2">Reports</li>
                <li class="side-nav-item">
                    <a class="side-nav-link {{ $isAiReportsMenu ? 'active' : '' }}" href="{{ route('reports.ai-builder') }}">
                        <span class="menu-icon"><i data-lucide="sparkles"></i></span>
                        <span class="menu-text">AI Report Builder</span>
                    </a>
                </li>
                <li class="side-nav-item">
                    <a aria-controls="reports-registration" aria-expanded="{{ $isReportsRegistrationMenu ? 'true' : 'false' }}" class="side-nav-link {{ $isReportsRegistrationMenu ? 'active' : '' }}" data-bs-toggle="collapse" href="#reports-registration">
                        <span class="menu-icon"><i data-lucide="book-open-text"></i></span>
                        <span class="menu-text">Registration</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse {{ $isReportsRegistrationMenu ? 'show' : '' }}" id="reports-registration">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a class="side-nav-link {{ request()->routeIs('reports.registration.individual') ? 'active' : '' }}" href="{{ route('reports.registration.individual') }}">
                                    <span class="menu-text">Individual</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link {{ request()->routeIs('reports.registration.business') ? 'active' : '' }}" href="{{ route('reports.registration.business') }}">
                                    <span class="menu-text">Business</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <li class="side-nav-item">
                    <a aria-controls="reports-amendments" aria-expanded="{{ $isReportsAmendmentsMenu ? 'true' : 'false' }}" class="side-nav-link {{ $isReportsAmendmentsMenu ? 'active' : '' }}" data-bs-toggle="collapse" href="#reports-amendments">
                        <span class="menu-icon"><i data-lucide="book-check"></i></span>
                        <span class="menu-text">Amendments</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse {{ $isReportsAmendmentsMenu ? 'show' : '' }}" id="reports-amendments">
                        <ul class="sub-menu">
                            <li class="side-nav-item">
                                <a class="side-nav-link {{ request()->routeIs('reports.amendments.individual') ? 'active' : '' }}" href="{{ route('reports.amendments.individual') }}">
                                    <span class="menu-text">Individual</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a class="side-nav-link {{ request()->routeIs('reports.amendments.business') ? 'active' : '' }}" href="{{ route('reports.amendments.business') }}">
                                    <span class="menu-text">Business</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>
<!-- Sidenav Menu End -->
