@extends("shared.base", ["title" => "408 Error"])

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
                        <div class="auth-brand text-center mb-2">
                            <a class="logo-dark" href="{{ url("/") }}">
                                <img alt="dark logo" height="28" src="/images/logo-black.png" />
                            </a>
                            <a class="logo-light" href="{{ url("/") }}">
                                <img alt="logo" height="28" src="/images/logo.png" />
                            </a>
                        </div>
                        <div class="p-4 text-center">
                            <div class="error-text-alt fs-72 text-warning">408</div>
                            <h3 class="fw-bold text-uppercase">Request Timeout</h3>
                            <p class="text-muted fs-5">The server timed out waiting for your request. Please try again or check your connection.</p>
                            <div class="mt-4 d-flex justify-content-center gap-1">
                                <button class="btn btn-primary" onclick="window.location.reload()">Retry</button>
                                <button class="btn btn-outline-info">Contact Support</button>
                            </div>
                        </div>
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
