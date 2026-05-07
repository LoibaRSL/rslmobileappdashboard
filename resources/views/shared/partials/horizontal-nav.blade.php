<header class="topnav">
    <nav class="navbar navbar-expand-lg">
        <nav class="container-fluid">
            <div class="collapse navbar-collapse" id="topnav-menu">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a aria-expanded="false" aria-haspopup="true" class="nav-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-main" role="button">
                            <span class="menu-icon"><i class="fs-xl" data-lucide="layout-dashboard"></i></span>
                            <span class="menu-text" data-lang="main">Main</span>
                            <div class="menu-arrow"></div>
                        </a>
                        <div aria-labelledby="topnav-main" class="dropdown-menu">
                            <a class="dropdown-item" href="{{ url("/") }}"><span data-lang="dashboard-ecommerce">Ecommerce</span></a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a aria-expanded="false" aria-haspopup="true" class="nav-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-custom-pages" role="button">
                            <span class="menu-icon"><i class="fs-xl" data-lucide="book-open-text"></i></span>
                            <span class="menu-text" data-lang="custom-pages">Custom Pages</span>
                            <div class="menu-arrow"></div>
                        </a>
                        <div aria-labelledby="topnav-custom-pages" class="dropdown-menu">
                            <a class="dropdown-item" href="{{ url("/pages/empty") }}"><span data-lang="pages-empty">Empty Page</span></a>
                            <div class="dropdown">
                                <a aria-expanded="false" aria-haspopup="true" class="dropdown-item dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-submenu-authentication" role="button">
                                    <span data-lang="authentication">Authentication</span>
                                    <div class="menu-arrow"></div>
                                </a>
                                <div aria-labelledby="topnav-submenu-authentication" class="dropdown-menu">
                                    <div class="dropdown">
                                        <a aria-expanded="false" aria-haspopup="true" class="dropdown-item dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-submenu-auth-basic" role="button">
                                            <span data-lang="auth-basic">Basic</span>
                                            <div class="menu-arrow"></div>
                                        </a>
                                        <div aria-labelledby="topnav-submenu-auth-basic" class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ url("/auth/sign-in") }}"><span data-lang="auth-sign-in">Sign In</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth/sign-up") }}"><span data-lang="auth-sign-up">Sign Up</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth/reset-pass") }}"><span data-lang="auth-reset-pass">Reset Password</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth/new-pass") }}"><span data-lang="auth-new-pass">New Password</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth/two-factor") }}"><span data-lang="auth-two-factor">Two Factor</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth/lock-screen") }}"><span data-lang="auth-lock-screen">Lock Screen</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth/success-mail") }}"><span data-lang="auth-success-mail">Success Mail</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth/login-pin") }}"><span data-lang="auth-login-pin">Login with PIN</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth/delete-account") }}"><span data-lang="auth-delete-account">Delete Account</span></a>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" aria-haspopup="true" class="dropdown-item dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-submenu-auth-split" role="button">
                                            <span data-lang="auth-split">Split</span>
                                            <div class="menu-arrow"></div>
                                        </a>
                                        <div aria-labelledby="topnav-submenu-auth-split" class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ url("/auth-split/sign-in") }}"><span data-lang="auth-split-sign-in">Sign In</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth-split/sign-up") }}"><span data-lang="auth-split-sign-up">Sign Up</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth-split/reset-pass") }}"><span data-lang="auth-split-reset-pass">Reset Password</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth-split/new-pass") }}"><span data-lang="auth-split-new-pass">New Password</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth-split/two-factor") }}"><span data-lang="auth-split-two-factor">Two Factor</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth-split/lock-screen") }}"><span data-lang="auth-split-lock-screen">Lock Screen</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth-split/success-mail") }}"><span data-lang="auth-split-success-mail">Success Mail</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth-split/login-pin") }}"><span data-lang="auth-split-login-pin">Login with PIN</span></a>
                                            <a class="dropdown-item" href="{{ url("/auth-split/delete-account") }}"><span data-lang="auth-split-delete-account">Delete Account</span></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a aria-expanded="false" aria-haspopup="true" class="dropdown-item dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-submenu-error-pages" role="button">
                                    <span data-lang="error-pages">Error Pages</span>
                                    <div class="menu-arrow"></div>
                                </a>
                                <div aria-labelledby="topnav-submenu-error-pages" class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ url("/error/400") }}"><span data-lang="error-400">400 Bad Request</span></a>
                                    <a class="dropdown-item" href="{{ url("/error/401") }}"><span data-lang="error-401">401 Unauthorized</span></a>
                                    <a class="dropdown-item" href="{{ url("/error/403") }}"><span data-lang="error-403">403 Forbidden</span></a>
                                    <a class="dropdown-item" href="{{ url("/error/404") }}"><span data-lang="error-404">404 Not Found</span></a>
                                    <a class="dropdown-item" href="{{ url("/error/408") }}"><span data-lang="error-408">408 Request Timeout</span></a>
                                    <a class="dropdown-item" href="{{ url("/error/500") }}"><span data-lang="error-500">500 Internal Server</span></a>
                                    <a class="dropdown-item" href="{{ url("/error/maintenance") }}"><span data-lang="error-maintenance">Maintenance</span></a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a aria-expanded="false" aria-haspopup="true" class="nav-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-layouts" role="button">
                            <span class="menu-icon"><i class="fs-xl" data-lucide="table-2"></i></span>
                            <span class="menu-text" data-lang="layouts">Layouts</span>
                            <div class="menu-arrow"></div>
                        </a>
                        <div aria-labelledby="topnav-layouts" class="dropdown-menu">
                            <div class="dropdown">
                                <a aria-expanded="false" aria-haspopup="true" class="dropdown-item dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-submenu-layout-options" role="button">
                                    <span data-lang="layout-options">Layout Options</span>
                                    <div class="menu-arrow"></div>
                                </a>
                                <div aria-labelledby="topnav-submenu-layout-options" class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ url("/layouts/scrollable") }}" target="_blank"><span data-lang="layouts-scrollable">Scrollable</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/compact") }}" target="_blank"><span data-lang="layouts-compact">Compact</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/boxed") }}" target="_blank"><span data-lang="layouts-boxed">Boxed</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/horizontal") }}" target="_blank"><span data-lang="layouts-horizontal">Horizontal</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/preloader") }}" target="_blank"><span data-lang="layouts-preloader">Preloader</span></a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a aria-expanded="false" aria-haspopup="true" class="dropdown-item dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-submenu-sidebars" role="button">
                                    <span data-lang="sidebars">Sidebars</span>
                                    <div class="menu-arrow"></div>
                                </a>
                                <div aria-labelledby="topnav-submenu-sidebars" class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ url("/layouts/sidebar/dark") }}" target="_blank"><span data-lang="layouts-sidebar-dark">Dark Menu</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/sidebar/gradient") }}" target="_blank"><span data-lang="layouts-sidebar-gradient">Gradient Menu</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/sidebar/gray") }}" target="_blank"><span data-lang="layouts-sidebar-gray">Gray Menu</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/sidebar/image") }}" target="_blank"><span data-lang="layouts-sidebar-image">Image Menu</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/sidebar/compact") }}" target="_blank"><span data-lang="layouts-sidebar-compact">Compact Menu</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/sidebar/on-hover") }}" target="_blank"><span data-lang="layouts-sidebar-on-hover">On Hover Menu</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/sidebar/on-hover-active") }}" target="_blank"><span data-lang="layouts-sidebar-on-hover-active">On Hover Active</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/sidebar/offcanvas") }}" target="_blank"><span data-lang="layouts-sidebar-offcanvas">Offcanvas Menu</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/sidebar/no-icons") }}" target="_blank"><span data-lang="layouts-sidebar-no-icons">No Icons with Lines</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/sidebar/with-lines") }}" target="_blank"><span data-lang="layouts-sidebar-with-lines">Sidebar with Lines</span></a>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a aria-expanded="false" aria-haspopup="true" class="dropdown-item dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-submenu-topbar" role="button">
                                    <span data-lang="topbar">Topbar</span>
                                    <div class="menu-arrow"></div>
                                </a>
                                <div aria-labelledby="topnav-submenu-topbar" class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ url("/layouts/topbar/light") }}" target="_blank"><span data-lang="layouts-topbar-light">Light Topbar</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/topbar/gray") }}" target="_blank"><span data-lang="layouts-topbar-gray">Gray Topbar</span></a>
                                    <a class="dropdown-item" href="{{ url("/layouts/topbar/gradient") }}" target="_blank"><span data-lang="layouts-topbar-gradient">Gradient Topbar</span></a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a aria-expanded="false" aria-haspopup="true" class="nav-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" href="#" id="topnav-components" role="button">
                            <span class="menu-icon"><i class="fs-xl" data-lucide="component"></i></span>
                            <span class="menu-text" data-lang="components">Components</span>
                            <div class="menu-arrow"></div>
                        </a>
                        <div aria-labelledby="topnav-components" class="dropdown-menu">
                            <a class="dropdown-item" href="{{ url("/icons/lucide") }}"><span data-lang="icons-lucide">Lucide</span></a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </nav>
</header>
<!-- Horizontal Menu End -->
