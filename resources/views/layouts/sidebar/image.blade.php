@extends("shared.base", ["title" => "Image Menu"])

@section("html_attribute")
    data-menu-color="image" data-topbar-color="light"
@endsection

@section("styles")
@endsection

@section("content")
    <div class="wrapper">
        @include("shared.partials.topbar") @include("shared.partials.sidenav")

        <div class="content-page">
            <div class="container-fluid">
                @include("shared.partials.page-title", ["subtitle" => "Layouts", "title" => "Image Menu"])

                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-warning alert-bordered border-start border-warning d-flex align-items-start gap-2">
                            <i class="fs-xxl" data-lucide="info"></i>
                            <div>
                                To enable an image with a gradient sidebar style, add
                                <code>data-menu-color="image"</code>
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
