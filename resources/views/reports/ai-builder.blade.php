@extends('layouts.ds')

@section('page-title', 'AI Report Builder')

@section('breadcrumbs')
    <li class="breadcrumb-item">Reports</li>
    <li class="breadcrumb-item active">AI Report Builder</li>
@endsection

@section('ds-content')
<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Generate Report</h5>

                @if(! $aiConfigured)
                    <div class="alert alert-warning">
                        AI reporting is not configured yet. Add the AI provider settings in <code>.env</code> to enable generation.
                    </div>
                @endif

                <form method="POST" action="{{ route('reports.ai-builder.generate') }}" class="d-grid gap-3">
                    @csrf
                    <div>
                        <label class="form-label">Report Scope</label>
                        <select name="scope" class="form-select">
                            @foreach([
                                'all' => 'All Reports',
                                'individual_registration' => 'Individual Registrations',
                                'business_registration' => 'Business Registrations',
                                'individual_amendment' => 'Individual Amendments',
                                'business_amendment' => 'Business Amendments',
                                'riit_returns' => 'RIIT Returns',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['scope'] ?? 'all') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('scope')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                            @error('date_from')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                            @error('date_to')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Question or Instruction</label>
                        <textarea name="prompt" rows="7" class="form-control" placeholder="Ask for an executive summary, operational risks, weekly performance, rejected cases, or bottlenecks.">{{ old('prompt', $filters['prompt'] ?? '') }}</textarea>
                        @error('prompt')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-auto-fix"></i> Generate AI Report
                    </button>
                </form>
            </div>
        </div>

  
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">Generated Report</h5>
                        <p class="text-muted mb-0">AI output is based only on the selected dashboard records and filters.</p>
                    </div>
                    @if($result)
                        <span class="badge {{ $result['success'] ? 'bg-success' : 'bg-warning' }}">
                            {{ $result['success'] ? 'Generated' : 'Needs Configuration' }}
                        </span>
                    @endif
                </div>

                @if($result)
                    @if($result['success'])
                        <div class="ai-report-output border rounded p-3">
                            {!! nl2br(e($result['content'])) !!}
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">{{ $result['message'] }}</div>
                    @endif
                @else
                    <div class="text-center text-muted py-5">
                        <i class="mdi mdi-file-chart-outline fs-1 d-block mb-2"></i>
                        Select the scope, add your instruction, and generate a report.
                    </div>
                @endif
            </div>
        </div>

        @if($context)
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Report Data Sent to AI</h5>
                    <pre class="bg-light border rounded p-3 mb-0" style="max-height: 420px; overflow:auto;">{{ json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('ds-styles')
<style>
.ai-report-output {
    background: var(--bs-tertiary-bg);
    line-height: 1.6;
    min-height: 280px;
    white-space: normal;
}
</style>
@endpush
