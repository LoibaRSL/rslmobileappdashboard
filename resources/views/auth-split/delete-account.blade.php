@extends("shared.base", ["title" => "Delete Account"])

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
                            <form class="mt-4">
                                <div class="mb-4">
                                    <div class="avatar-xxl mx-auto mt-2">
                                        <div class="avatar-title bg-light-subtle border border-light border-dashed rounded-circle">
                                            <img alt="dark logo" height="64" src="/images/delete.png" />
                                        </div>
                                    </div>
                                </div>
                                <h4 class="fw-bold text-center mb-3">Account Deactivated</h4>
                                <p class="text-muted text-center mb-4">Your account is currently inactive. Reactivate now to regain access to all features and opportunities.</p>
                                <div class="d-grid">
                                    <button class="btn btn-primary fw-semibold py-2" type="submit">Reactivate Now</button>
                                </div>
                            </form>
                        </div>
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
