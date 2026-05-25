@extends("shared.base", ["title" => "Dashboard"])

@section("content")
<div class="wrapper">
    @include("shared.partials.topbar")
    @include("shared.partials.sidenav")

    <div class="content-page">
        <div class="container-fluid">
            @include("shared.partials.page-title", ["subtitle" => "Workspace", "title" => "Dashboard"])

            <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 mb-4">
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24 p-3">
                                    <i data-lucide="receipt-text"></i>
                                </span>
                                <div class="text-end">
                                    <h3 class="mb-1">{{ number_format($stats['resident_returns']) }}</h3>
                                    <p class="text-muted mb-0">Resident Returns</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="avatar-title bg-success-subtle text-success rounded-circle fs-24 p-3">
                                    <i data-lucide="calendar-days"></i>
                                </span>
                                <div class="text-end">
                                    <h3 class="mb-1">{{ number_format($stats['returns_this_month']) }}</h3>
                                    <p class="text-muted mb-0">This Month</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-24 p-3">
                                    <i data-lucide="clock-3"></i>
                                </span>
                                <div class="text-end">
                                    <h3 class="mb-1">{{ number_format($stats['returns_today']) }}</h3>
                                    <p class="text-muted mb-0">Today</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-24 p-3">
                                    <i data-lucide="paperclip"></i>
                                </span>
                                <div class="text-end">
                                    <h3 class="mb-1">{{ number_format($stats['attachments']) }}</h3>
                                    <p class="text-muted mb-0">Attachments</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-1">Recent Resident Returns</h5>
                                    <p class="text-muted mb-0">Read-only return submissions available to your role.</p>
                                </div>
                                <a class="btn btn-sm btn-primary" href="{{ route('returns.resident-tax') }}">
                                    <i class="mdi mdi-open-in-new"></i> Open Returns
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Reference</th>
                                            <th>TIN</th>
                                            <th>Type</th>
                                            <th>Period</th>
                                            <th>Tax Due</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recent_returns as $return)
                                            <tr>
                                                <td class="fw-semibold">{{ $return->reference_number }}</td>
                                                <td>{{ $return->tin }}</td>
                                                <td>{{ ucfirst($return->return_type) }}</td>
                                                <td>{{ $return->period_start_date?->format('Y-m-d') }} to {{ $return->period_end_date?->format('Y-m-d') }}</td>
                                                <td>M {{ number_format((float) $return->tax_due, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No resident returns available yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="mb-3">My Workspace</h5>
                            <div class="list-group list-group-flush">
                                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ route('returns.resident-tax') }}">
                                    <span><i class="mdi mdi-file-document-outline me-1"></i> Resident Individual Tax</span>
                                    <i data-lucide="chevron-right"></i>
                                </a>
                            </div>
                            <div class="alert alert-info mt-3 mb-0">
                                Registration workflow tools are available only to Admin and Digital Services users.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include("shared.partials.footer")
    </div>
</div>

@include("shared.partials.customizer")
@include("shared.partials.footer-scripts")
@endsection

@section("scripts")
<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
@endsection
