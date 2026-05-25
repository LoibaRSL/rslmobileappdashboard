@extends('layouts.ds')

@section('page-title', 'SLA Monitor')

@section('breadcrumbs')
    <li class="breadcrumb-item">Digital Services</li>
    <li class="breadcrumb-item active">SLA Monitor</li>
@endsection

@section('ds-content')
<div class="row mb-3" id="slaStats">
    <div class="col-md-3"><div class="card border-danger"><div class="card-body"><p class="text-muted mb-1">Overdue</p><h3 id="overdueCount" class="text-danger mb-0">0</h3></div></div></div>
    <div class="col-md-3"><div class="card border-warning"><div class="card-body"><p class="text-muted mb-1">Due Soon</p><h3 id="dueSoonCount" class="text-warning mb-0">0</h3></div></div></div>
    <div class="col-md-3"><div class="card border-info"><div class="card-body"><p class="text-muted mb-1">Average Age</p><h3 id="avgAge" class="mb-0">0d</h3></div></div></div>
    <div class="col-md-3"><div class="card border-primary"><div class="card-body"><p class="text-muted mb-1">Open Items</p><h3 id="openCount" class="mb-0">0</h3></div></div></div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Overdue and Aging Work</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Age</th>
                    </tr>
                </thead>
                <tbody id="slaBody">
                    <tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('ds-scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const response = await fetch('{{ route('ds.api.operations.sla') }}', { headers: { Accept: 'application/json' } });
    const json = await response.json();
    document.getElementById('overdueCount').textContent = json.stats.overdue;
    document.getElementById('dueSoonCount').textContent = json.stats.due_soon;
    document.getElementById('avgAge').textContent = `${json.stats.average_age_days}d`;
    document.getElementById('openCount').textContent = json.stats.open;
    const rows = json.data || [];
    document.getElementById('slaBody').innerHTML = rows.length ? rows.map(row => `
        <tr>
            <td class="fw-semibold">${escapeHtml(row.ref)}</td>
            <td>${escapeHtml(row.type)}</td>
            <td>${escapeHtml(row.name)}</td>
            <td><span class="badge ${row.overdue ? 'bg-danger' : 'bg-warning'}">${escapeHtml(row.status)}</span></td>
            <td>${escapeHtml(row.assigned_to)}</td>
            <td>${row.age_days} days</td>
        </tr>
    `).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">No aging open work found.</td></tr>';
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }
});
</script>
@endpush
