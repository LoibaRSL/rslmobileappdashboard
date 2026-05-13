@extends("shared.base", ["title" => "Dashboard"])

@section("styles")
@endsection

@section("content")
    <div class="wrapper">
        @include("shared.partials.topbar") @include("shared.partials.sidenav")

        <div class="content-page">
            <div class="container-fluid">
                @include("shared.partials.page-title", ["subtitle" => "Dashboards", "title" => "Dashboard"])

                <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1">
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                                            <i data-lucide="credit-card"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-normal"><span data-target="30">0</span></h3>
                                        <p class="mb-0 text-muted"><span>New Applications</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-24">
                                            <i data-lucide="shopping-cart"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-normal"><span data-target="2358">0</span></h3>
                                        <p class="mb-0 text-muted"><span>Approved Applications</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-24">
                                            <i data-lucide="users"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-normal"><span data-target="839">0</span></h3>
                                        <p class="mb-0 text-muted"><span>Rejected Applications</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-24">
                                            <i data-lucide="banknote-arrow-down"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-normal"><span data-target="41">0</span></h3>
                                        <p class="mb-0 text-muted"><span>Upgrades</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="row g-0">
                                    <div class="col-xxl-3 col-xl-6 order-xl-1 order-xxl-0">
                                        <div class="p-3 border-end border-dashed">
                                            <h4 class="card-title mb-0">Total New Applications</h4>
                                            <p class="text-muted fs-xs">You have 21 pending Application awaiting Approvals.</p>
                                            <div class="row mt-4">
                                                <div class="col-lg-12">
                                                    <div style="height: 300px">
                                                        <canvas id="multi-pie-chart"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <hr class="d-xxl-none border-light m-0" />
                                    </div>
                                    <div class="col-xxl-9 order-xl-3 order-xxl-1">
                                        <div class="px-4 py-3">
                                            <div class="d-flex justify-content-between mb-3">
                                                <h4 class="card-title">Applications Analytics</h4>
                                                <a class="link-reset text-decoration-underline fw-semibold link-offset-3" href="#!">View Reports <i data-lucide="arrow-right"></i></a>
                                            </div>
                                            <div dir="ltr">
                                                <div class="mt-3" style="height: 330px">
                                                    <canvas id="sales-analytics-chart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xxl-6">
                        <div class="card" data-table="" data-table-rows-per-page="7">
                            <div class="card-header justify-content-between align-items-center border-dashed">
                                <h4 class="card-title mb-0">Recent Individuals Applications</h4>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-soft-secondary" href="#!"> <i class="me-1" data-lucide="plus"></i> Add Product </a>
                                    <a class="btn btn-sm btn-primary" href="javascript:void(0);"> <i class="me-1" data-lucide="download"></i> Export CSV </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-centered table-custom table-sm table-nowrap table-hover mb-0">
                                        <tbody>
                                            
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer border-0">
                                <div class="align-items-center justify-content-between row text-center text-sm-start">
                                    <div class="col-sm">
                                        <div data-table-pagination-info="products"></div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div data-table-pagination=""></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-6">
                        <div class="card" data-table="" data-table-rows-per-page="7">
                            <div class="card-header justify-content-between align-items-center border-dashed">
                                <h4 class="card-title mb-0">Recent Business Applications</h4>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-soft-secondary" href="#!"> <i class="me-1" data-lucide="plus"></i> Add Product </a>
                                    <a class="btn btn-sm btn-primary" href="javascript:void(0);"> <i class="me-1" data-lucide="download"></i> Export CSV </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-centered table-custom table-sm table-nowrap table-hover mb-0">
                                        <tbody>
                                                                                  </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer border-0">
                                <div class="align-items-center justify-content-between row text-center text-sm-start">
                                    <div class="col-sm">
                                        <div data-table-pagination-info="orders"></div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div data-table-pagination=""></div>
                                    </div>
                                </div>
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
    @vite(["resources/js/pages/custom-table.js"])
    @vite(["resources/js/pages/dashboard-ecommerce.js"])
@endsection
