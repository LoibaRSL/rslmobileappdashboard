@extends("shared.base", ["title" => "Boxed Layout"])

@section("html_attribute")
    data-layout-width="boxed" data-sidenav-size="on-hover"
@endsection

@section("styles")
@endsection

@section("content")
    <div class="wrapper">
        @include("shared.partials.topbar") @include("shared.partials.sidenav")

        <div class="content-page">
            <div class="container-fluid">
                @include("shared.partials.page-title", ["subtitle" => "Layouts", "title" => "Boxed"])

                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info alert-bordered border-start border-info d-flex align-items-start gap-2">
                            <i class="fs-xxl" data-lucide="info"></i>
                            <div>
                                To enable the boxed layout, add
                                <code>data-layout-width="boxed"</code>
                                to the
                                <code>&lt;html&gt;</code>
                                tag. For optimal spacing and usability, we also recommend adding
                                <code>data-sidenav-size="on-hover"</code>
                                to make the sidebar compact while keeping more room for content.
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
