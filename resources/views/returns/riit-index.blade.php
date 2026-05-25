@extends('layouts.ds')

@section('page-title', 'Resident Individual Income Tax Returns')

@section('breadcrumbs')
    <li class="breadcrumb-item">Returns</li>
    <li class="breadcrumb-item active">Resident Individual Tax</li>
@endsection

@section('ds-content')
<div class="row mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card border-primary"><div class="card-body">
            <p class="text-muted mb-1">Total Returns</p>
            <h3 class="mb-0">{{ number_format($stats['total']) }}</h3>
        </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-success"><div class="card-body">
            <p class="text-muted mb-1">SOAP Success</p>
            <h3 class="mb-0 text-success">{{ number_format($stats['soap_success']) }}</h3>
        </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-danger"><div class="card-body">
            <p class="text-muted mb-1">SOAP Failed</p>
            <h3 class="mb-0 text-danger">{{ number_format($stats['soap_failed']) }}</h3>
        </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-warning"><div class="card-body">
            <p class="text-muted mb-1">Pending SOAP</p>
            <h3 class="mb-0 text-warning">{{ number_format($stats['pending']) }}</h3>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">RIIT Submissions</h5>
                <p class="text-muted mb-0">Read-only resident individual income tax returns submitted from the mobile app.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-centered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>TIN</th>
                        <th>Period</th>
                        <th>Type</th>
                        <th>Tax Due</th>
                        <th>Files</th>
                        <th>Submitted</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $return)
                        <tr>
                            <td class="fw-semibold">{{ $return->reference_number }}</td>
                            <td>{{ $return->tin }}</td>
                            <td>{{ $return->period_start_date?->format('Y-m-d') }} to {{ $return->period_end_date?->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">{{ ucfirst($return->return_type) }}</span>
                                @if($return->is_amendment)
                                    <span class="badge bg-info-subtle text-info">Amendment</span>
                                @endif
                            </td>
                            <td>M {{ number_format((float) $return->tax_due, 2) }}</td>
                            <td>{{ $return->attachments_count }}</td>
                            <td>{{ $return->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-info" href="{{ route('returns.resident-tax.show', $return) }}">
                                    <i class="mdi mdi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No RIIT returns have been submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $returns->links() }}
        </div>
    </div>
</div>
@endsection
