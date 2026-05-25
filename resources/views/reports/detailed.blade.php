@extends('layouts.ds')

@section('page-title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">Reports</li>
    <li class="breadcrumb-item active">{{ $title }}</li>
@endsection

@section('ds-content')
<div class="row mb-3">
    <div class="col-xl-2 col-md-4">
        <div class="card border-primary">
            <div class="card-body">
                <p class="text-muted mb-1">Total</p>
                <h3 class="mb-0">{{ number_format($stats['total']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4">
        <div class="card border-warning">
            <div class="card-body">
                <p class="text-muted mb-1">Pending</p>
                <h3 class="mb-0 text-warning">{{ number_format($stats['pending']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4">
        <div class="card border-info">
            <div class="card-body">
                <p class="text-muted mb-1">Under Review</p>
                <h3 class="mb-0 text-info">{{ number_format($stats['under_review']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4">
        <div class="card border-success">
            <div class="card-body">
                <p class="text-muted mb-1">Approved</p>
                <h3 class="mb-0 text-success">{{ number_format($stats['approved']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4">
        <div class="card border-danger">
            <div class="card-body">
                <p class="text-muted mb-1">Rejected</p>
                <h3 class="mb-0 text-danger">{{ number_format($stats['rejected']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4">
        <div class="card border-secondary">
            <div class="card-body">
                <p class="text-muted mb-1">Today</p>
                <h3 class="mb-0">{{ number_format($stats['submitted_today']) }}</h3>
            </div>
        </div>
    </div>
</div>

@php
    $statusColors = [
        'PENDING' => '#f7b84b',
        'UNDER_REVIEW' => '#299cdb',
        'APPROVED' => '#0ab39c',
        'REJECTED' => '#f06548',
    ];
    $statusTotal = max(1, collect($charts['status'])->sum());
    $trendMax = max(1, collect($charts['trend'])->max('count'));
    $distributionMax = max(1, collect($charts['distribution'])->max());
    $donutStart = 0;
    $donutSegments = collect($charts['status'])->map(function ($count, $status) use (&$donutStart, $statusTotal, $statusColors) {
        $start = $donutStart;
        $end = $start + (($count / $statusTotal) * 100);
        $donutStart = $end;
        return ($statusColors[$status] ?? '#6c757d') . ' ' . $start . '% ' . $end . '%';
    })->implode(', ');
@endphp

<div class="row mb-3">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3">Status Breakdown</h5>
                <div class="d-flex align-items-center gap-4">
                    <div class="report-donut" style="background: conic-gradient({{ $donutSegments ?: '#e9ecef 0 100%' }});">
                        <div class="report-donut-hole">{{ number_format($stats['total']) }}</div>
                    </div>
                    <div class="flex-grow-1">
                        @foreach($charts['status'] as $status => $count)
                            <a class="report-filter-link d-flex align-items-center justify-content-between mb-2" href="{{ request()->fullUrlWithQuery(['status' => $status, 'page' => null]) }}">
                                <span><i class="report-dot" style="background: {{ $statusColors[$status] ?? '#6c757d' }}"></i>{{ str_replace('_', ' ', $status) }}</span>
                                <strong>{{ number_format($count) }}</strong>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3">14-Day Submissions</h5>
                <div class="report-bars">
                    @foreach($charts['trend'] as $point)
                        <a class="report-bar-item" href="{{ request()->fullUrlWithQuery(['date_from' => $point['date'], 'date_to' => $point['date'], 'page' => null]) }}" title="{{ $point['label'] }}: {{ $point['count'] }}">
                            <div class="report-bar-value">{{ $point['count'] ?: '' }}</div>
                            <div class="report-bar-track">
                                <div class="report-bar-fill" style="height: {{ max(4, ($point['count'] / $trendMax) * 100) }}%"></div>
                            </div>
                            <small>{{ $point['label'] }}</small>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3">{{ $charts['distributionTitle'] }}</h5>
                @forelse($charts['distribution'] as $label => $count)
                    <a class="report-filter-link d-block mb-3" href="{{ request()->fullUrlWithQuery(['distribution' => $label, 'page' => null]) }}">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-truncate pe-2">{{ $label }}</span>
                            <strong>{{ number_format($count) }}</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" style="width: {{ ($count / $distributionMax) * 100 }}%;"></div>
                        </div>
                    </a>
                @empty
                    <p class="text-muted mb-0">No distribution data available for the current filters.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-xl-4 col-md-6">
                <label class="form-label">Search</label>
                <input type="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Reference, TIN, name, email">
            </div>
            <div class="col-xl-2 col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['PENDING' => 'Pending', 'UNDER_REVIEW' => 'Under Review', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-md-6">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-xl-2 col-md-6">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            @if(!empty($filters['distribution']))
                <input type="hidden" name="distribution" value="{{ $filters['distribution'] }}">
            @endif
            <div class="col-xl-2 col-md-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary flex-fill">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="mb-1">{{ $title }}</h5>
                <p class="text-muted mb-0">{{ $description }}</p>
                @if(!empty($filters['distribution']))
                    <span class="badge bg-primary-subtle text-primary mt-2">Visual filter: {{ $filters['distribution'] }}</span>
                @endif
            </div>
            <a href="{{ $exportUrl . (str_contains($exportUrl, '?') ? '&' : '?') . http_build_query(request()->except('page')) }}" class="btn btn-info">
                <i class="mdi mdi-download"></i> Export CSV
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-centered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @foreach($columns as $column)
                            <th>{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @foreach($columns as $column)
                                @php($value = app(\App\Http\Controllers\ReportsController::class)->reportValue($row, $column['key'], $type))
                                <td>
                                    @if($column['key'] === 'status')
                                        @php($badge = ['PENDING' => 'bg-warning', 'UNDER_REVIEW' => 'bg-info', 'APPROVED' => 'bg-success', 'REJECTED' => 'bg-danger'][$value] ?? 'bg-secondary')
                                        <span class="badge {{ $badge }}">{{ $value }}</span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="text-center text-muted py-4">No report records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted">
                Showing {{ $rows->firstItem() ?? 0 }}-{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }} records
            </small>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection

@push('ds-styles')
<style>
.report-donut {
    align-items: center;
    border-radius: 50%;
    display: flex;
    flex: 0 0 160px;
    height: 160px;
    justify-content: center;
    width: 160px;
}
.report-donut-hole {
    align-items: center;
    background: var(--bs-body-bg);
    border-radius: 50%;
    display: flex;
    font-size: 1.5rem;
    font-weight: 700;
    height: 96px;
    justify-content: center;
    width: 96px;
}
.report-dot {
    border-radius: 50%;
    display: inline-block;
    height: 10px;
    margin-right: 8px;
    width: 10px;
}
.report-filter-link {
    color: inherit;
    text-decoration: none;
}
.report-filter-link:hover {
    color: var(--bs-primary);
}
.report-bars {
    align-items: end;
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(14, minmax(20px, 1fr));
    min-height: 190px;
}
.report-bar-item {
    align-items: center;
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
    color: inherit;
    text-decoration: none;
}
.report-bar-item:hover .report-bar-fill {
    background: var(--bs-primary);
}
.report-bar-track {
    align-items: end;
    background: var(--bs-tertiary-bg);
    border-radius: 6px;
    display: flex;
    height: 120px;
    overflow: hidden;
    width: 100%;
}
.report-bar-fill {
    background: #299cdb;
    border-radius: 6px 6px 0 0;
    min-height: 4px;
    width: 100%;
}
.report-bar-value {
    font-size: .75rem;
    font-weight: 700;
    min-height: 16px;
}
.report-bar-item small {
    font-size: .68rem;
    text-align: center;
    white-space: nowrap;
    writing-mode: vertical-rl;
}
@media (max-width: 575.98px) {
    .report-donut {
        flex-basis: 130px;
        height: 130px;
        width: 130px;
    }
    .report-donut-hole {
        height: 78px;
        width: 78px;
    }
    .report-bars {
        gap: 4px;
    }
}
</style>
@endpush
