@extends("shared.base", ["title" => "Scrollable Layout"])

@section("html_attribute")
    data-layout-position="scrollable"
@endsection

@section("styles")
@endsection

@section("content")
    <div class="wrapper">
        @include("shared.partials.topbar") @include("shared.partials.sidenav")

        <div class="content-page">
            <div class="container-fluid">
                @include("shared.partials.page-title", ["subtitle" => "Layouts", "title" => "Scrollable"])

                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info alert-bordered border-start border-info d-flex align-items-start gap-2">
                            <i class="fs-xxl" data-lucide="info"></i>
                            <div>
                                To enable full scrolling and view all content, please add
                                <code>data-layout-position="scrollable"</code>
                                to the
                                <code>&lt;html&gt;</code>
                                tag.
                            </div>
                        </div>
                    </div>
                </div>
                <div style="min-height: 100vh"></div>
            </div>

            @include("shared.partials.footer")
        </div>
    </div>

    @include("shared.partials.customizer") @include("shared.partials.footer-scripts")
@endsection

@section("scripts")
@endsection
