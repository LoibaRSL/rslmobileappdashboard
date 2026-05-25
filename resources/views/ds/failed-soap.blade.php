@extends('layouts.ds')

@section('page-title', 'Failed SOAP Submissions')

@section('breadcrumbs')
    <li class="breadcrumb-item">Digital Services</li>
    <li class="breadcrumb-item active">Failed SOAP</li>
@endsection

@section('ds-content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Failed SOAP Retry Queue</h5>
                <p class="text-muted mb-0">Retry approvals that failed while sending to the backend SOAP service.</p>
            </div>
            <button class="btn btn-outline-primary" id="refreshBtn"><i class="mdi mdi-refresh"></i> Refresh</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Failed At</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="failedSoapBody">
                    <tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('ds-scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('failedSoapBody');
    const csrf = @json(csrf_token());
    document.getElementById('refreshBtn').addEventListener('click', loadQueue);
    body.addEventListener('click', async event => {
        const explainButton = event.target.closest('.explain-btn');
        if (explainButton) {
            return explainFailure(explainButton);
        }

        const button = event.target.closest('.retry-btn');
        if (!button || !await AppUX.confirm('Retry this SOAP submission?', 'Retry backend submission')) return;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Retrying';
        try {
            const response = await fetch(`{{ url('api/ds/operations/failed-soap') }}/${button.dataset.id}/retry`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const json = await response.json();
            if (!response.ok || json.success === false) throw new Error(json.message || 'Retry failed');
            AppUX.toast(json.message);
            loadQueue();
        } catch (error) {
            AppUX.toast(error.message, 'error');
            button.disabled = false;
            button.innerHTML = 'Retry';
        }
    });
    loadQueue();

    async function loadQueue() {
        body.innerHTML = AppUX.skeletonRows(5, 5);
        const response = await fetch('{{ route('ds.api.operations.failed-soap') }}', { headers: { Accept: 'application/json' } });
        const json = await response.json();
        const rows = json.data || [];
        if (!rows.length) {
            body.innerHTML = AppUX.emptyState(
                5,
                'mdi-check-decagram-outline',
                'No failed SOAP submissions',
                'Backend submissions are healthy and no retry work is waiting.'
            );
            return;
        }
        body.innerHTML = rows.map(row => `
            <tr>
                <td class="fw-semibold">${escapeHtml(row.subject_label || 'N/A')}</td>
                <td>${escapeHtml(row.subject_type_label)}</td>
                <td>${escapeHtml(row.message || 'N/A')}</td>
                <td>${escapeHtml(row.failed_at)}</td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info explain-btn" data-id="${row.id}">Explain</button>
                        <button class="btn btn-primary retry-btn" data-id="${row.id}">Retry</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async function explainFailure(button) {
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        try {
            const response = await fetch(`{{ url('api/ds/operations/failed-soap') }}/${button.dataset.id}/ai-explain`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) throw new Error(json.message || 'AI analysis failed');
            showAiExplanation(json.content || 'No AI analysis returned.');
        } catch (error) {
            AppUX.toast(error.message, 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = 'Explain';
        }
    }

    function showAiExplanation(content) {
        if (!window.Swal) {
            return AppUX.toast(content, 'info');
        }

        Swal.fire({
            title: 'AI SOAP Analysis',
            html: `<div class="text-start white-space-pre-line">${escapeHtml(content)}</div>`,
            icon: 'info',
            confirmButtonText: 'Close',
            width: 720
        });
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }
});
</script>
@endpush
