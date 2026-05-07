@extends("shared.base", ["title" => "Lock Screen"])

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
                        </div>
                        <div class="text-center mb-4">
                            <img alt="thumbnail" class="rounded-circle img-thumbnail avatar-xxl mb-2" src="/images/users/user-1.jpg" />
                            <h5 class="fs-md">Geneva K.</h5>
                        </div>
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="userPassword">
                                    Password
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input class="form-control" id="userPassword" placeholder="••••••••" required="" type="password" />
                                </div>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary fw-semibold py-2" type="submit">Unlock</button>
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
@endsection
