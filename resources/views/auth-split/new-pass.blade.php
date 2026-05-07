@extends("shared.base", ["title" => "New Password"])

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
                            <p class="text-muted text-center auth-sub-text mx-auto">We've emailed you a 6-digit verification code. Please enter it below to confirm your Email Address.</p>
                            <form class="mt-4">
                                <div class="mb-3">
                                    <label class="form-label d-flex">
                                        Enter your 6-digit code
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="d-flex gap-2 two-factor">
                                        <input class="form-control text-center" required="" type="text" />
                                        <input class="form-control text-center" required="" type="text" />
                                        <input class="form-control text-center" required="" type="text" />
                                        <input class="form-control text-center" required="" type="text" />
                                        <input class="form-control text-center" required="" type="text" />
                                        <input class="form-control text-center" required="" type="text" />
                                    </div>
                                </div>
                                <div class="mb-3" data-password="bar">
                                    <label class="form-label" for="userPassword">
                                        New Password
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="app-search">
                                        <input class="form-control" id="userPassword" placeholder="••••••••" required="" type="password" />
                                        <i class="app-search-icon text-muted" data-lucide="lock-keyhole"></i>
                                    </div>
                                    <div class="password-bar my-2"></div>
                                    <p class="text-muted fs-xs mb-0">Use 8+ characters with letters, numbers &amp; symbols.</p>
                                </div>
                                <div class="mb-3 d-flex">
                                    <div class="form-check">
                                        <input checked="" class="form-check-input form-check-input-light fs-14" id="termAndPolicy" type="checkbox" />
                                        <label class="form-check-label" for="termAndPolicy">Agree the Terms &amp; Policy</label>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-primary fw-semibold py-2" type="submit">Update Password</button>
                                </div>
                            </form>
                        </div>
                        <p class="text-muted text-center mt-4 mb-0">
                            Don’t have a code?
                            <a class="text-decoration-underline link-offset-2 fw-semibold" href="#">Resend</a>
                            or
                            <a class="text-decoration-underline link-offset-2 fw-semibold" href="#">Call Us</a>
                        </p>
                        <p class="text-muted text-center mt-4 mb-0">
                            Return to
                            <a class="text-decoration-underline link-offset-3 fw-semibold" href="{{ url("/auth-split/sign-in") }}">Sign in</a>
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
    @vite(["resources/js/pages/auth-two-factor.js"])
    @vite(["resources/js/pages/auth-password.js"])
@endsection
