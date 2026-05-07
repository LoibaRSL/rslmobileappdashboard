@extends("shared.base", ["title" => "Maintenance"])

@section("styles")
@endsection

@section("content")
    <div class="auth-box d-flex align-items-center">
        <div class="container-xxl">
            <div class="row align-items-center justify-content-center">
                <div class="col-xl-6">
                    <div class="card mb-0">
                        <div class="position-absolute top-0 end-0" style="width: 280px">
                            <img alt="auth-card-bg" class="auth-card-bg-img" src="/images/auth-card-bg.svg" />
                        </div>
                        <div class="card-body">
                            <div class="auth-brand text-center mb-0">
                                <a class="logo-dark" href="{{ url("/") }}">
                                    <img alt="dark logo" height="32" src="/images/logo-black.png" />
                                </a>
                                <a class="logo-light" href="{{ url("/") }}">
                                    <img alt="logo" height="32" src="/images/logo.png" />
                                </a>
                            </div>
                            <div class="p-2 text-center">
                                <div class="w-md-50 mx-auto">
                                    <img alt="Maintenance" class="img-fluid" src="/images/maintenance.svg" />
                                </div>
                                <h3 class="fw-bold text-uppercase">Site Under Maintenance</h3>
                                <p class="text-muted">
                                    We’re currently performing scheduled maintenance.
                                    <br />
                                    Please check back soon.
                                </p>
                                <button class="btn btn-primary mt-3 me-1">Call Now</button>
                                <button class="btn btn-info mt-3">Email Us</button>
                            </div>
                            <p class="text-center text-muted mt-5 mb-0">
                                ©
                                <span data-current-year=""></span>
                                UBold — by
                                <span class="fw-semibold">Coderthemes</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include("shared.partials.footer-scripts")
@endsection

@section("scripts")
@endsection
