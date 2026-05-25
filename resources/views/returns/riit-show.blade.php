@extends('layouts.ds')

@section('page-title', 'RIIT Return ' . $return->reference_number)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('returns.resident-tax') }}">Resident Individual Tax</a></li>
    <li class="breadcrumb-item active">{{ $return->reference_number }}</li>
@endsection

@section('ds-content')
@php
    $labelize = fn ($key) => \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', (string) $key));

    $formatScalar = function ($value) {
        if ($value === true) {
            return 'Yes';
        }

        if ($value === false) {
            return 'No';
        }

        if ($value === null || $value === '') {
            return 'N/A';
        }

        return $value;
    };

    $renderRiItValue = function ($value) use (&$renderRiItValue, $labelize, $formatScalar) {
        if (is_array($value)) {
            if ($value === []) {
                return '<span class="text-muted">N/A</span>';
            }

            $isList = array_keys($value) === range(0, count($value) - 1);
            if ($isList) {
                return collect($value)->map(function ($item, $index) use (&$renderRiItValue) {
                    return '<div class="border rounded p-2 mb-2"><div class="fw-semibold text-muted small mb-2">Item ' . ($index + 1) . '</div>' . $renderRiItValue($item) . '</div>';
                })->implode('');
            }

            $rows = collect($value)
                ->filter(fn ($item) => !($item === null || $item === '' || $item === []))
                ->map(function ($item, $key) use (&$renderRiItValue, $labelize) {
                    return '<tr><th>' . e($labelize($key)) . '</th><td>' . $renderRiItValue($item) . '</td></tr>';
                })
                ->implode('');

            return $rows
                ? '<div class="table-responsive"><table class="table table-sm table-borderless mb-0 detail-table"><tbody>' . $rows . '</tbody></table></div>'
                : '<span class="text-muted">N/A</span>';
        }

        return e($formatScalar($value));
    };

    $submittedSections = collect((array) $return->form_data)
        ->filter(fn ($value) => !($value === null || $value === '' || $value === []));
@endphp
<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Return Summary</h5>
                <table class="table table-sm table-borderless mb-0">
                    <tr><th>Reference</th><td>{{ $return->reference_number }}</td></tr>
                    <tr><th>Person ID</th><td>{{ $return->person_id }}</td></tr>
                    <tr><th>TIN</th><td>{{ $return->tin }}</td></tr>
                    <tr><th>Return Type</th><td>{{ ucfirst($return->return_type) }}</td></tr>
                    <tr><th>Amendment</th><td>{{ $return->is_amendment ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Tax Year End</th><td>{{ $return->tax_year_end ?: 'N/A' }}</td></tr>
                    <tr><th>Period</th><td>{{ $return->period_start_date?->format('Y-m-d') }} to {{ $return->period_end_date?->format('Y-m-d') }}</td></tr>
                    <tr><th>Submitted</th><td>{{ $return->created_at?->format('Y-m-d H:i:s') }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Tax Computation</h5>
                <div class="row">
                    <div class="col-md-6"><p class="text-muted mb-1">Total Chargeable Income</p><h4>M {{ number_format((float) $return->total_chargeable_income, 2) }}</h4></div>
                    <div class="col-md-6"><p class="text-muted mb-1">Tax Due</p><h4 class="text-danger">M {{ number_format((float) $return->tax_due, 2) }}</h4></div>
                </div>
                @if($return->nil_reason)
                    <div class="alert alert-info mb-0">Nil return reason: {{ $return->nil_reason }}</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Attachments</h5>
                @forelse($return->attachments as $attachment)
                    <a class="d-flex align-items-center justify-content-between border rounded p-2 mb-2" target="_blank" href="{{ route('returns.resident-tax.attachments.show', [$return, $attachment]) }}">
                        <span>
                            <i class="mdi mdi-file-document-outline me-1"></i>
                            {{ $attachment->original_filename }}
                            <small class="text-muted d-block">{{ $attachment->attachment_type }} - {{ number_format($attachment->file_size / 1024, 1) }} KB</small>
                        </span>
                        <span class="btn btn-sm btn-outline-secondary">Open</span>
                    </a>
                @empty
                    <p class="text-muted mb-0">No attachments were submitted with this return.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Submitted Data</h5>
                @if($submittedSections->isEmpty())
                    <p class="text-muted mb-0">No submitted form details were captured.</p>
                @else
                    <div class="accordion" id="riitSubmittedData">
                        @foreach($submittedSections as $section => $value)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="riit-heading-{{ $loop->index }}">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#riit-panel-{{ $loop->index }}">
                                        {{ $labelize($section) }}
                                    </button>
                                </h2>
                                <div id="riit-panel-{{ $loop->index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#riitSubmittedData">
                                    <div class="accordion-body">
                                        {!! $renderRiItValue($value) !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('ds-styles')
<style>
.detail-table th {
    color: #64748b;
    font-weight: 600;
    min-width: 180px;
    width: 30%;
}
.detail-table td {
    word-break: break-word;
}
</style>
@endpush
