@php
    $topbarUser = auth()->user();
    $topbarUserName = $topbarUser?->name ?? 'User';
    $topbarUserEmail = $topbarUser?->email;
    $topbarUserRole = $topbarUser?->roles?->first()?->display_name ?? ($topbarUser?->role ?? 'User');
    $canSeeRegistrationWorkflow = $topbarUser?->isDigitalServices() ?? false;
    $unassignedTinCount = 0;
    $unassignedTinRegistrations = collect();

    if ($canSeeRegistrationWorkflow) {
        $unassignedTinQuery = \App\Models\TinRegistration::where('status', 'PENDING')
            ->where(function ($query) {
                $query->whereNull('assigned_to')->orWhere('assigned_to', '');
            });
        $unassignedBusinessQuery = \App\Models\BusinessRegistration::where('status', 'submitted')
            ->where(function ($query) {
                $query->whereNull('assigned_to')->orWhere('assigned_to', '');
            });
        $unassignedTinCount = (clone $unassignedTinQuery)->count() + (clone $unassignedBusinessQuery)->count();
        $unassignedTinRegistrations = (clone $unassignedTinQuery)->latest()->limit(5)->get()
            ->map(fn ($registration) => [
                'type' => 'Individual',
                'name' => trim(($registration->forenames ?? '') . ' ' . ($registration->surname ?? '')) ?: 'TIN Registration',
                'ref' => $registration->ref ?? 'N/A',
                'created_at' => $registration->created_at,
            ])
            ->merge((clone $unassignedBusinessQuery)->latest()->limit(5)->get()->map(fn ($registration) => [
                'type' => 'Business',
                'name' => $registration->display_name,
                'ref' => $registration->reference_number ?? 'N/A',
                'created_at' => $registration->created_at,
            ]))
            ->sortByDesc(fn ($registration) => $registration['created_at']?->timestamp ?? 0)
            ->take(5);
    }
@endphp

<header class="app-topbar">
    <div class="container-fluid topbar-menu">
        <div class="d-flex align-items-center gap-2">
            <!-- Topbar Brand Logo -->
            <div class="logo-topbar">
                <!-- Logo light -->
                <a class="logo-light" href="{{ url("/") }}">
                    <span class="logo-lg">
                        <img alt="logo" src="/images/logos.png" />
                    </span>
                    <span class="logo-sm">
                        <img alt="small logo" src="/images/logo-sm.png" />
                    </span>
                </a>
                <!-- Logo Dark -->
                <a class="logo-dark" href="{{ url("/") }}">
                    <span class="logo-lg">
                        <img alt="dark logo" src="/images/logo-blacks.png" />
                    </span>
                    <span class="logo-sm">
                        <img alt="small logo" src="/images/logo-sms.png" />
                    </span>
                </a>
            </div>
            <!-- Sidebar Menu Toggle Button -->
            <button class="sidenav-toggle-button btn btn-default btn-icon">
                <i data-lucide="menu"></i>
            </button>
            <!-- Horizontal Menu Toggle Button -->
            <button class="topnav-toggle-button px-2" data-bs-target="#topnav-menu" data-bs-toggle="collapse">
                <i data-lucide="menu"></i>
            </button>

        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="app-search d-none d-xl-flex" id="search-box-rounded-right">
                <input class="form-control rounded-pill topbar-search" name="search" placeholder="Quick Search..." type="search" />
                <i class="app-search-icon text-muted" data-lucide="search"></i>
            </div>
            <div class="topbar-item" id="theme-dropdown">
                <div class="dropdown">
                    <button aria-expanded="false" aria-haspopup="false" class="topbar-link" data-bs-toggle="dropdown" type="button">
                        <i class="topbar-link-icon d-none" data-lucide="sun" id="theme-icon-light"></i>
                        <i class="topbar-link-icon d-none" data-lucide="moon" id="theme-icon-dark"></i>
                        <i class="topbar-link-icon d-none" data-lucide="sun-moon" id="theme-icon-system"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" data-thememode="dropdown">
                        <label class="dropdown-item">
                            <input class="form-check-input" name="data-bs-theme" style="display: none" type="radio" value="light" />
                            <i class="align-middle me-1 fs-16" data-lucide="sun"></i>
                            <span class="align-middle">Light</span>
                        </label>
                        <label class="dropdown-item">
                            <input class="form-check-input" name="data-bs-theme" style="display: none" type="radio" value="dark" />
                            <i class="align-middle me-1 fs-16" data-lucide="moon"></i>
                            <span class="align-middle">Dark</span>
                        </label>
                        <label class="dropdown-item">
                            <input class="form-check-input" name="data-bs-theme" style="display: none" type="radio" value="system" />
                            <i class="align-middle me-1 fs-16" data-lucide="sun-moon"></i>
                            <span class="align-middle">System</span>
                        </label>
                    </div>
                    <!-- end dropdown-menu-->
                </div>
                <!-- end dropdown-->
            </div>
            @if($canSeeRegistrationWorkflow)
            <div class="topbar-item" id="notification-dropdown-people">
                <div class="dropdown">
                    <button aria-expanded="false" aria-haspopup="false" class="topbar-link dropdown-toggle drop-arrow-none" data-bs-auto-close="outside" data-bs-toggle="dropdown" type="button">
                        <i class="topbar-link-icon animate-ring" data-lucide="bell"></i>
                        @if($unassignedTinCount > 0)
                            <span class="badge text-bg-danger badge-circle topbar-badge">{{ $unassignedTinCount > 99 ? '99+' : $unassignedTinCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu p-0 dropdown-menu-end dropdown-menu-lg">
                        <div class="px-3 py-2 border-bottom">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0 fs-md fw-semibold">Unassigned Registrations</h6>
                                </div>
                                <div class="col text-end">
                                    <a class="badge badge-soft-success badge-label py-1" href="{{ route('ds.registrations.unassigned') }}">
                                        {{ $unassignedTinCount }} Pending
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div data-simplebar="" style="max-height: 300px">
                            @forelse($unassignedTinRegistrations as $registration)
                                <a class="dropdown-item notification-item py-2 text-wrap" href="{{ route('ds.registrations.unassigned') }}">
                                    <span class="d-flex align-items-center gap-3">
                                        <span class="flex-shrink-0 position-relative">
                                            <span class="avatar-md rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center">
                                                <i class="fs-4" data-lucide="file-clock"></i>
                                            </span>
                                            <span class="position-absolute rounded-pill bg-danger notification-badge">
                                                <i class="align-middle" data-lucide="bell"></i>
                                                <span class="visually-hidden">unassigned registration</span>
                                            </span>
                                        </span>
                                        <span class="flex-grow-1 text-muted">
                                            <span class="fw-medium text-body">{{ $registration['name'] }}</span>
                                            is waiting for assignment
                                            <br />
                                            <span class="fs-xs">
                                                {{ $registration['type'] }} - Ref: {{ $registration['ref'] }}
                                                @if($registration['created_at'])
                                                    - {{ $registration['created_at']->diffForHumans() }}
                                                @endif
                                            </span>
                                        </span>
                                    </span>
                                </a>
                            @empty
                                <div class="dropdown-item notification-item py-3 text-center text-muted">
                                    <i class="d-block mb-1" data-lucide="check-circle"></i>
                                    No unassigned registrations
                                </div>
                            @endforelse
                        </div>
                        <a class="dropdown-item text-center text-reset text-decoration-underline link-offset-2 fw-bold notify-item border-top border-light py-2" href="{{ route('ds.registrations.unassigned') }}">View Unassigned Registrations</a>
                    </div>
                    <!-- End dropdown-menu -->
                </div>
                <!-- end dropdown-->
            </div>
            @endif
            <div class="topbar-item d-none d-sm-flex" id="fullscreen-toggler">
                <button class="topbar-link" data-toggle="fullscreen" type="button">
                    <i class="topbar-link-icon" data-lucide="maximize"></i>
                    <i class="topbar-link-icon d-none" data-lucide="minimize"></i>
                </button>
            </div>
            <div class="topbar-item d-none d-xl-flex" id="monochrome-toggler">
                <button class="topbar-link" data-toggle="monochrome" id="monochrome-mode" type="button">
                    <i class="topbar-link-icon" data-lucide="palette"></i>
                </button>
            </div>
            <!--<div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link btn-theme-setting" data-bs-target="#theme-settings-offcanvas" data-bs-toggle="offcanvas" type="button">
                    <i class="topbar-link-icon" data-lucide="settings"></i>
                </button>
            </div>-->
            
            <div class="topbar-item nav-user" id="simple-user-dropdown">
                <div class="dropdown">
                    <a aria-expanded="false" aria-haspopup="false" class="topbar-link dropdown-toggle drop-arrow-none px-2" data-bs-toggle="dropdown" href="#!">
                        @include('shared.partials.user-avatar', ['user' => $topbarUser, 'size' => 32, 'class' => 'me-lg-2 d-flex'])
                        <div class="d-lg-flex align-items-center gap-1 d-none">
                            <h5 class="my-0">{{ $topbarUserName }}</h5>
                            <i class="align-middle" data-lucide="chevron-down"></i>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- Header -->
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">{{ $topbarUserName }}</h6>
                            <small class="text-muted d-block">{{ $topbarUserRole }}</small>
                            @if($topbarUserEmail)
                                <small class="text-muted d-block">{{ $topbarUserEmail }}</small>
                            @endif
                        </div>
                        <!-- My Profile -->
                        <a class="dropdown-item" href="#!">
                            <i class="me-1 fs-lg align-middle" data-lucide="circle-user-round"></i>
                            <span class="align-middle">Profile</span>
                        </a>
                        @if($canSeeRegistrationWorkflow)
                            <a class="dropdown-item" href="{{ route('ds.registrations.unassigned') }}">
                                <i class="me-1 fs-lg align-middle" data-lucide="bell-ring"></i>
                                <span class="align-middle">Unassigned Registrations</span>
                                @if($unassignedTinCount > 0)
                                    <span class="badge text-bg-danger float-end">{{ $unassignedTinCount }}</span>
                                @endif
                            </a>
                        @endif
                      
                    
                        <!-- Settings -->
                        <a class="dropdown-item" href="javascript:void(0);">
                            <i class="me-1 fs-lg align-middle" data-lucide="bolt"></i>
                            <span class="align-middle">Account Settings</span>
                        </a>
                        <!-- Support -->
                        <a class="dropdown-item" href="javascript:void(0);">
                            <i class="me-1 fs-lg align-middle" data-lucide="headset"></i>
                            <span class="align-middle">Support Center</span>
                        </a>
                        <!-- Divider -->
                        <div class="dropdown-divider"></div>
                        <!-- Lock -->
                        <a class="dropdown-item" href="{{ url("/auth/lock-screen") }}">
                            <i class="me-1 fs-lg align-middle" data-lucide="lock-keyhole"></i>
                            <span class="align-middle">Lock Screen</span>
                        </a>
                        <!-- Logout -->
                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger fw-semibold" type="submit">
                                <i class="me-1 fs-lg align-middle" data-lucide="log-out"></i>
                                <span class="align-middle">Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- Topbar End -->
<div aria-hidden="true" aria-labelledby="searchModalLabel" class="modal fade" id="searchModal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-transparent">
            <form>
                <div class="card mb-1">
                    <div class="px-3 py-2 d-flex flex-row align-items-center" id="top-search">
                        <i class="fs-22" data-lucide="search"></i>
                        <input class="form-control border-0" id="search-modal-input" placeholder="Search for actions, people," type="search" />
                        <button aria-label="Close" class="btn p-0" data-bs-dismiss="modal" type="submit">[esc]</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
