@extends("shared.base", ["title" => "400 Error"])

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
                        <div class="p-2 text-center">
                            <div class="error-text-alt fs-72">400</div>
                            <h3 class="fw-bold text-uppercase">Bad Request</h3>
                            <p class="text-muted">Something's not right in the request you made.</p>
                            <button class="btn btn-primary mt-3 rounded-pill" onclick="window.location.href = 'index.html'">Go Home</button>
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
