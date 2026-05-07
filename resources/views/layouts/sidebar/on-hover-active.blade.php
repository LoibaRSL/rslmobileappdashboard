@extends("shared.base", ["title" => "On Hover Menu"])

@section("html_attribute")
    data-sidenav-size="on-hover-active"
@endsection

@section("styles")
@endsection

@section("content")
    <div class="wrapper">
        @include("shared.partials.topbar") @include("shared.partials.sidenav")

        <div class="content-page">
            <div class="container-fluid">
                @include("shared.partials.page-title", ["subtitle" => "Layouts", "title" => "On Hover Active"])

                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-warning alert-bordered border-start border-warning d-flex align-items-start gap-2">
                            <i class="fs-xxl" data-lucide="info"></i>
                            <div>
                                To enable the full menu on hover after minimizing the sidebar, add
                                <code>data-sidenav-size="on-hover-active"</code>
                                to the
                                <code>&lt;html&gt;</code>
                                tag in your layout.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4 class="m-0">Your custom content here</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include("shared.partials.footer")
        </div>
    </div>

    @include("shared.partials.customizer") @include("shared.partials.footer-scripts")
@endsection

@section("scripts")
@endsection
