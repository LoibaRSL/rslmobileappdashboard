@extends("shared.base", ["title" => "Create New Account"])

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
                        <div class="auth-brand text-center mb-4">
                            <a class="logo-dark" href="{{ url("/") }}">
                                <img alt="dark logo" src="/images/logo-black.png" />
                            </a>
                            <a class="logo-light" href="{{ url("/") }}">
                                <img alt="logo" src="/images/logo.png" />
                            </a>
                            <p class="text-muted w-lg-75 mt-3 mx-auto">Let’s get you started. Create your account by entering your details below.</p>
                        </div>
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="userName">
                                    Name
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input class="form-control" id="userName" placeholder="Geneva K." required="" type="text" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="userEmail">
                                    Email address
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input class="form-control" id="userEmail" placeholder="you@example.com" required="" type="email" />
                                </div>
                            </div>
                            <div class="mb-3" data-password="bar">
                                <label class="form-label" for="userPassword">
                                    Password
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input class="form-control" id="userPassword" placeholder="••••••••" required="" type="password" />
                                </div>
                                <div class="password-bar my-2"></div>
                                <p class="text-muted fs-xs mb-0">Use 8+ characters with letters, numbers &amp; symbols.</p>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input form-check-input-light fs-14" id="termAndPolicy" type="checkbox" />
                                    <label class="form-check-label" for="termAndPolicy">Agree the Terms &amp; Policy</label>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary fw-semibold py-2" type="submit">Create Account</button>
                            </div>
                        </form>
                        <p class="text-muted text-center mt-4 mb-0">
                            Already have an account?
                            <a class="text-decoration-underline link-offset-3 fw-semibold" href="{{ url("/auth/sign-in") }}">Login</a>
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
    @vite(["resources/js/pages/auth-password.js"])
@endsection
