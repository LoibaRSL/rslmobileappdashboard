@extends("shared.base", ["title" => "Login with Pin"])

@section("styles")
@endsection

@section("content")
    <div class="auth-box overflow-hidden align-items-center d-flex">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-4 col-md-6 col-sm-8">
                    <div class="card p-4">
                        <div class="position-absolute top-0 end-0" style="width: 180px">
                            <img alt="auth-card-bg" class="auth-card-bg-img" src="/images/auth-card-bg.svg" />
                        </div>
                        <div class="auth-brand text-center mb-3 position-relative">
                            <a class="logo-dark" href="{{ url("/") }}">
                                <img alt="dark logo" src="/images/logo-black.png" />
                            </a>
                            <a class="logo-light" href="{{ url("/") }}">
                                <img alt="logo" src="/images/logo.png" />
                            </a>
                            <p class="text-muted w-lg-75 mt-3 mx-auto">This screen is locked. Enter your PIN to continue.</p>
                        </div>
                        <div class="text-center mb-4">
                            <img alt="thumbnail" class="rounded-circle img-thumbnail avatar-xxl mb-2" src="/images/users/user-1.jpg" />
                            <h5 class="fs-md">Geneva K.</h5>
                        </div>
                        <form>
                            <label class="form-label">
                                Enter your 6-digit code
                                <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex gap-2 mb-3 two-factor">
                                <input class="form-control text-center" required="" type="text" />
                                <input class="form-control text-center" required="" type="text" />
                                <input class="form-control text-center" required="" type="text" />
                                <input class="form-control text-center" required="" type="text" />
                                <input class="form-control text-center" required="" type="text" />
                                <input class="form-control text-center" required="" type="text" />
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary fw-semibold py-2" type="submit">Confirm</button>
                            </div>
                        </form>
                        <p class="text-muted text-center mt-4 mb-0">
                            Not you? Return to
                            <a class="text-decoration-underline link-offset-3 fw-semibold" href="{{ url("/auth/sign-in") }}">Sign in</a>
                        </p>
                    </div>
                    <p class="text-center text-muted mt-4 mb-0">
                        ©
                        <span data-current-year=""></span>
                        UBold — by
                        <span class="fw-semibold">Coderthemes</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @include("shared.partials.footer-scripts")
@endsection

@section("scripts")
    @vite(["resources/js/pages/auth-two-factor.js"])
@endsection
