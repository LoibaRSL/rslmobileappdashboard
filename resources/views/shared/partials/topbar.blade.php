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
            <div class="topbar-item" id="apps-dropdown-grid">
                <div class="dropdown">
                    <button aria-expanded="false" aria-haspopup="false" class="topbar-link dropdown-toggle drop-arrow-none" data-bs-auto-close="outside" data-bs-toggle="dropdown" type="button">
                        <i class="topbar-link-icon" data-lucide="layout-grid"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg p-2 dropdown-menu-end">
                        <div class="row align-items-center g-1">
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2" href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title text-bg-light rounded-circle">
                                            <img alt="Google Logo" height="18" src="/images/logos/google.svg" />
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Google</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2" href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title text-bg-light rounded-circle">
                                            <img alt="Figma Logo" height="18" src="/images/logos/figma.svg" />
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Figma</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2" href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title text-bg-light rounded-circle">
                                            <img alt="Slack Logo" height="18" src="/images/logos/slack.svg" />
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Slack</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2" href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title text-bg-light rounded-circle">
                                            <img alt="Dropbox Logo" height="18" src="/images/logos/dropbox.svg" />
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Dropbox</span>
                                </a>
                            </div>
                            <div class="col-4 text-center">
                                <a class="btn btn-sm rounded-circle btn-icon btn-danger" href="javascript:void(0);">
                                    <i class="fs-18" data-lucide="circle-plus"></i>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2" href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                            <i class="fs-18" data-lucide="calendar"></i>
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Calendar</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2" href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                            <i class="fs-18" data-lucide="message-circle"></i>
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Chat</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2" href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                            <i class="fs-18" data-lucide="folder"></i>
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Files</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2" href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                            <i class="fs-18" data-lucide="users"></i>
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Team</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- End dropdown-menu -->
                </div>
                <!-- end dropdown-->
            </div>
            <div class="topbar-item" id="notification-dropdown-people">
                <div class="dropdown">
                    <button aria-expanded="false" aria-haspopup="false" class="topbar-link dropdown-toggle drop-arrow-none" data-bs-auto-close="outside" data-bs-toggle="dropdown" type="button">
                        <i class="topbar-link-icon animate-ring" data-lucide="bell"></i>
                        <span class="badge text-bg-danger badge-circle topbar-badge">5</span>
                    </button>
                    <div class="dropdown-menu p-0 dropdown-menu-end dropdown-menu-lg">
                        <div class="px-3 py-2 border-bottom">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0 fs-md fw-semibold">Notifications</h6>
                                </div>
                                <div class="col text-end">
                                    <a class="badge badge-soft-success badge-label py-1" href="#!">07 Notifications</a>
                                </div>
                            </div>
                        </div>
                        <div data-simplebar="" style="max-height: 300px">
                            <!-- Notification 1 -->
                            <div class="dropdown-item notification-item py-2 text-wrap" id="message-1">
                                <span class="d-flex align-items-center gap-3">
                                    <span class="flex-shrink-0 position-relative">
                                        <img alt="User Avatar" class="avatar-md rounded-circle" src="/images/users/user-4.jpg" />
                                        <span class="position-absolute rounded-pill bg-success notification-badge">
                                            <i class="align-middle" data-lucide="bell"></i>
                                            <span class="visually-hidden">unread notification</span>
                                        </span>
                                    </span>
                                    <span class="flex-grow-1 text-muted">
                                        <span class="fw-medium text-body">Emily Johnson</span>
                                        commented on a task in
                                        <span class="fw-medium text-body">Design Sprint</span>
                                        <br />
                                        <span class="fs-xs">12 minutes ago</span>
                                    </span>
                                    <button class="flex-shrink-0 text-muted btn btn-link p-0 position-absolute end-0 me-2 d-none noti-close-btn" data-dismissible="#message-1" type="button">
                                        <i class="fs-xxl" data-lucide="x-square"></i>
                                    </button>
                                </span>
                            </div>
                            <!-- Notification 2 -->
                            <div class="dropdown-item notification-item py-2 text-wrap" id="message-2">
                                <span class="d-flex align-items-center gap-3">
                                    <span class="flex-shrink-0 position-relative">
                                        <img alt="User Avatar" class="avatar-md rounded-circle" src="/images/users/user-5.jpg" />
                                        <span class="position-absolute rounded-pill bg-info notification-badge">
                                            <i class="align-middle" data-lucide="cloud-upload"></i>
                                            <span class="visually-hidden">upload notification</span>
                                        </span>
                                    </span>
                                    <span class="flex-grow-1 text-muted">
                                        <span class="fw-medium text-body">Michael Lee</span>
                                        uploaded files to
                                        <span class="fw-medium text-body">Marketing </span>
                                        <br />
                                        <span class="fs-xs">25 minutes ago</span>
                                    </span>
                                    <button class="flex-shrink-0 text-muted btn btn-link p-0 position-absolute end-0 me-2 d-none noti-close-btn" data-dismissible="#message-2" type="button">
                                        <i class="fs-xxl" data-lucide="x-square"></i>
                                    </button>
                                </span>
                            </div>
                            <!-- Notification 3 - Server CPU Alert -->
                            <div class="dropdown-item notification-item py-2 text-wrap" id="message-6">
                                <span class="d-flex align-items-center gap-3">
                                    <span class="flex-shrink-0 position-relative">
                                        <span class="avatar-md rounded-circle bg-light d-flex align-items-center justify-content-center">
                                            <i class="fs-4" data-lucide="database"></i>
                                        </span>
                                        <span class="position-absolute rounded-pill bg-danger notification-badge">
                                            <i class="align-middle" data-lucide="circle-alert"></i>
                                            <span class="visually-hidden">server alert</span>
                                        </span>
                                    </span>
                                    <span class="flex-grow-1 text-muted">
                                        <span class="fw-medium text-body">Server #3</span>
                                        CPU usage exceeded 90%
                                        <br />
                                        <span class="fs-xs">Just now</span>
                                    </span>
                                    <button class="flex-shrink-0 text-muted btn btn-link p-0 position-absolute end-0 me-2 d-none noti-close-btn" data-dismissible="#message-6" type="button">
                                        <i class="fs-xxl" data-lucide="x-square"></i>
                                    </button>
                                </span>
                            </div>
                            <!-- Notification 4 -->
                            <div class="dropdown-item notification-item py-2 text-wrap" id="message-3">
                                <span class="d-flex align-items-center gap-3">
                                    <span class="flex-shrink-0 position-relative">
                                        <img alt="User Avatar" class="avatar-md rounded-circle" src="/images/users/user-6.jpg" />
                                        <span class="position-absolute rounded-pill bg-warning notification-badge">
                                            <i class="align-middle" data-lucide="alert-triangle"></i>
                                            <span class="visually-hidden">alert</span>
                                        </span>
                                    </span>
                                    <span class="flex-grow-1 text-muted">
                                        <span class="fw-medium text-body">Sophia Ray</span>
                                        flagged an issue in
                                        <span class="fw-medium text-body">Bug Tracker</span>
                                        <br />
                                        <span class="fs-xs">40 minutes ago</span>
                                    </span>
                                    <button class="flex-shrink-0 text-muted btn btn-link p-0 position-absolute end-0 me-2 d-none noti-close-btn" data-dismissible="#message-3" type="button">
                                        <i class="fs-xxl" data-lucide="x-square"></i>
                                    </button>
                                </span>
                            </div>
                            <!-- Notification 5 -->
                            <div class="dropdown-item notification-item py-2 text-wrap" id="message-4">
                                <span class="d-flex align-items-center gap-3">
                                    <span class="flex-shrink-0 position-relative">
                                        <img alt="User Avatar" class="avatar-md rounded-circle" src="/images/users/user-7.jpg" />
                                        <span class="position-absolute rounded-pill bg-primary notification-badge">
                                            <i class="align-middle" data-lucide="calendar-check"></i>
                                            <span class="visually-hidden">event notification</span>
                                        </span>
                                    </span>
                                    <span class="flex-grow-1 text-muted">
                                        <span class="fw-medium text-body">David Kim</span>
                                        scheduled a meeting for
                                        <span class="fw-medium text-body">UX Review</span>
                                        <br />
                                        <span class="fs-xs">1 hour ago</span>
                                    </span>
                                    <button class="flex-shrink-0 text-muted btn btn-link p-0 position-absolute end-0 me-2 d-none noti-close-btn" data-dismissible="#message-4" type="button">
                                        <i class="fs-xxl" data-lucide="x-square"></i>
                                    </button>
                                </span>
                            </div>
                            <!-- Notification 6 -->
                            <div class="dropdown-item notification-item py-2 text-wrap" id="message-5">
                                <span class="d-flex align-items-center gap-3">
                                    <span class="flex-shrink-0 position-relative">
                                        <img alt="User Avatar" class="avatar-md rounded-circle" src="/images/users/user-8.jpg" />
                                        <span class="position-absolute rounded-pill bg-secondary notification-badge">
                                            <i class="align-middle" data-lucide="square-pen"></i>
                                            <span class="visually-hidden">edit</span>
                                        </span>
                                    </span>
                                    <span class="flex-grow-1 text-muted">
                                        <span class="fw-medium text-body">Isabella White</span>
                                        updated the document in
                                        <span class="fw-medium text-body">Product Specs</span>
                                        <br />
                                        <span class="fs-xs">2 hours ago</span>
                                    </span>
                                    <button class="flex-shrink-0 text-muted btn btn-link p-0 position-absolute end-0 me-2 d-none noti-close-btn" data-dismissible="#message-5" type="button">
                                        <i class="fs-xxl" data-lucide="x-square"></i>
                                    </button>
                                </span>
                            </div>
                            <!-- Notification 7 - Deployment Success -->
                            <div class="dropdown-item notification-item py-2 text-wrap" id="message-7">
                                <span class="d-flex align-items-center gap-3">
                                    <span class="flex-shrink-0 position-relative">
                                        <span class="avatar-md rounded-circle bg-light d-flex align-items-center justify-content-center">
                                            <i class="fs-4" data-lucide="rocket"></i>
                                        </span>
                                        <span class="position-absolute rounded-pill bg-success notification-badge">
                                            <i class="align-middle" data-lucide="check"></i>
                                            <span class="visually-hidden">deployment</span>
                                        </span>
                                    </span>
                                    <span class="flex-grow-1 text-muted">
                                        <span class="fw-medium text-body">Production Server</span>
                                        deployment completed successfully
                                        <br />
                                        <span class="fs-xs">30 minutes ago</span>
                                    </span>
                                    <button class="flex-shrink-0 text-muted btn btn-link p-0 position-absolute end-0 me-2 d-none noti-close-btn" data-dismissible="#message-7" type="button">
                                        <i class="fs-xxl" data-lucide="x-square"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                        <!-- All-->
                        <a class="dropdown-item text-center text-reset text-decoration-underline link-offset-2 fw-bold notify-item border-top border-light py-2" href="javascript:void(0);">Read All Messages</a>
                    </div>
                    <!-- End dropdown-menu -->
                </div>
                <!-- end dropdown-->
            </div>
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
                        <img alt="user-image" class="rounded-circle me-lg-2 d-flex" src="/images/users/user-1.jpg" width="32" />
                        <div class="d-lg-flex align-items-center gap-1 d-none">
                            <h5 class="my-0">Geneva K.</h5>
                            <i class="align-middle" data-lucide="chevron-down"></i>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- Header -->
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome back!</h6>
                        </div>
                        <!-- My Profile -->
                        <a class="dropdown-item" href="#!">
                            <i class="me-1 fs-lg align-middle" data-lucide="circle-user-round"></i>
                            <span class="align-middle">Profile</span>
                        </a>
                        <!-- Notifications -->
                        <a class="dropdown-item" href="javascript:void(0);">
                            <i class="me-1 fs-lg align-middle" data-lucide="bell-ring"></i>
                            <span class="align-middle">Notifications</span>
                        </a>
                      
                    
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
                        <a class="dropdown-item text-danger fw-semibold" href="javascript:void(0);">
                            <i class="me-1 fs-lg align-middle" data-lucide="log-out"></i>
                            <span class="align-middle">Log Out</span>
                        </a>
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
