@extends("shared.base", ["title" => "Sign In"])

@section("styles")
@endsection

@section("content")
    <div class="auth-box p-0 w-100">
        <div class="row w-100 g-0">
            <div class="col-md-auto">
                <div class="card auth-box-form border-0 mb-0">
                    <div class="position-absolute top-0 end-0" style="width: 180px">
                        <img alt="auth-card-bg" class="auth-card-bg-img" src="/images/auth-card-bg.svg" />
                    </div>
                    <div class="card-body min-vh-100 position-relative d-flex flex-column justify-content-center">
                        <div class="auth-brand mb-0 text-center">
                            <a class="logo-dark" href="{{ url("/") }}">
                                <img alt="dark logo" src="/images/logo-black.png" />
                            </a>
                            <a class="logo-light" href="{{ url("/") }}">
                                <img alt="logo" src="/images/logo.png" />
                            </a>
                        </div>
                        <div class="mt-auto">
                            <p class="text-muted text-center auth-sub-text mx-auto">Let’s get you signed in. Enter your email and password to continue.</p>
                            <form class="mt-4">
                                <div class="mb-3">
                                    <label class="form-label" for="userEmail">
                                        Email address
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="app-search">
                                        <input class="form-control" id="userEmail" placeholder="you@example.com" required="" type="email" />
                                        <i class="app-search-icon text-muted" data-lucide="mail"></i>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="userPassword">
                                        Password
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="app-search">
                                        <input class="form-control" id="userPassword" placeholder="••••••••" required="" type="password" />
                                        <i class="app-search-icon text-muted" data-lucide="lock-keyhole"></i>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="form-check">
                                        <input checked="" class="form-check-input form-check-input-light fs-14" id="rememberMe" type="checkbox" />
                                        <label class="form-check-label" for="rememberMe">Keep me signed in</label>
                                    </div>
                                    <a class="text-decoration-underline link-offset-3 text-muted" href="{{ url("/auth-split/reset-pass") }}">Forgot Password?</a>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-primary fw-bold py-2" type="submit">Sign In</button>
                                </div>
                            </form>
                        </div>
                        <p class="text-muted text-center mt-4 mb-0">
                            New here?
                            <a class="text-decoration-underline link-offset-3 fw-semibold" href="{{ url("/auth-split/sign-up") }}">Create an account</a>
                        </p>
                        <p class="text-center text-muted mt-auto mb-0">
                            ©
                            <span data-current-year=""></span>
                            UBold — by
                            <span class="fw-bold">Coderthemes</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="h-100 position-relative card-side-img rounded-0 overflow-hidden" style="background-image: url(/images/auth.jpg)">
                    <div class="p-4 card-img-overlay auth-overlay d-flex align-items-end justify-content-center"></div>
                </div>
            </div>
        </div>
    </div>

    @include("shared.partials.footer-scripts")
@endsection

@section("scripts")
@endsection
