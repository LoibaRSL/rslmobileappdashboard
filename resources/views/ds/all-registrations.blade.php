@extends('layouts.ds')

@section('page-title', 'All Registrations')

@section('breadcrumbs')
    <li class="breadcrumb-item active">All Registrations</li>
@endsection

@section('ds-content')
<div class="row mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card border-primary">
            <div class="card-body">
                <p class="text-muted mb-1">Total Results</p>
                <h3 class="mb-0" id="total-count">0</h3>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-warning">
            <div class="card-body">
                <p class="text-muted mb-1">Pending</p>
                <h3 class="mb-0 text-warning" id="pending-count">0</h3>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-info">
            <div class="card-body">
                <p class="text-muted mb-1">Under Review</p>
                <h3 class="mb-0 text-info" id="review-count">0</h3>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-success">
            <div class="card-body">
                <p class="text-muted mb-1">Approved</p>
                <h3 class="mb-0 text-success" id="approved-count">0</h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" id="filterForm" class="row g-2 align-items-end">
            <div class="col-xl-3 col-md-6">
                <label class="form-label">Search</label>
                <input type="search" name="search" id="search" class="form-control" placeholder="Name, TIN, reference, email">
            </div>
            <div class="col-xl-2 col-md-6">
                <label class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="PENDING">Pending</option>
                    <option value="UNDER_REVIEW">Under Review</option>
                    <option value="APPROVED">Approved</option>
                    <option value="REJECTED">Rejected</option>
                </select>
            </div>
            <div class="col-xl-2 col-md-6">
                <label class="form-label">Assigned To</label>
                <select name="assigned_to" id="assigned_to" class="form-select">
                    <option value="">All Users</option>
                </select>
            </div>
            <div class="col-xl-2 col-md-6">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control">
            </div>
            <div class="col-xl-2 col-md-6">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control">
            </div>
            <div class="col-xl-1 col-md-6 d-flex gap-1">
                <button type="button" id="resetBtn" class="btn btn-secondary w-100">Reset</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="mb-1">Registration Queue</h5>
                <p class="text-muted mb-0">Individual and business registrations in one review list.</p>
            </div>
            <a href="#" id="exportBtn" class="btn btn-info">
                <i class="mdi mdi-download"></i> Export CSV
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-centered align-middle mb-0 w-100" id="registrations-table">
                <thead class="table-light">
                    <tr>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>TIN</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="registrations-body">
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2 mb-0">Loading registrations...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted" id="result-summary">Showing 0 registrations</small>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="prev-page">Previous</button>
                <button type="button" class="btn btn-outline-secondary btn-sm disabled" id="current-page">Page 1</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="next-page">Next</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registrationModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Registration Details <span class="badge bg-light text-dark ms-2" id="modal-ref"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="registration-details">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('ds-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const state = { page: 1, length: 25, total: 0 };
    const body = document.getElementById('registrations-body');
    const filters = ['search', 'status', 'assigned_to', 'date_from', 'date_to'].reduce((items, id) => {
        items[id] = document.getElementById(id);
        return items;
    }, {});

    loadUsers();
    loadRegistrations();

    filters.search.addEventListener('keyup', debounce(() => {
        state.page = 1;
        loadRegistrations();
    }, 350));

    ['status', 'assigned_to', 'date_from', 'date_to'].forEach(id => {
        filters[id].addEventListener('change', () => {
            state.page = 1;
            loadRegistrations();
        });
    });

    document.getElementById('resetBtn').addEventListener('click', function() {
        document.getElementById('filterForm').reset();
        state.page = 1;
        loadRegistrations();
    });

    document.getElementById('prev-page').addEventListener('click', function() {
        if (state.page > 1) {
            state.page--;
            loadRegistrations();
        }
    });

    document.getElementById('next-page').addEventListener('click', function() {
        if (state.page * state.length < state.total) {
            state.page++;
            loadRegistrations();
        }
    });

    document.getElementById('exportBtn').addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = `{{ url('api/ds/export') }}?${buildParams(false).toString()}`;
    });

    body.addEventListener('click', function(event) {
        const button = event.target.closest('.view-btn');
        if (!button) return;

        openDetails(button.dataset.id, button.dataset.type || 'individual');
    });

    async function loadUsers() {
        try {
            const response = await fetch('{{ route("ds.api.users.ds-users") }}', { headers: { Accept: 'application/json' } });
            const json = await response.json();
            if (!json.success) return;

            filters.assigned_to.innerHTML = '<option value="">All Users</option>' + json.users
                .map(user => `<option value="${escapeHtml(user.id)}">${escapeHtml(user.name)}</option>`)
                .join('');
        } catch (error) {
            console.warn('Unable to load DS users', error);
        }
    }

    async function loadRegistrations() {
        setLoading();

        try {
            const response = await fetch(`{{ route("ds.api.registrations.all") }}?${buildParams(true).toString()}`, {
                headers: { Accept: 'application/json' }
            });

            if (!response.ok) {
                throw new Error(response.status === 401 ? 'Please sign in again.' : 'Failed to load registrations.');
            }

            const json = await response.json();
            const rows = json.data?.data || json.data || [];
            state.total = json.recordsFiltered || rows.length;
            renderRows(rows);
            updateSummary(rows, state.total);
            updatePagination(rows.length);
        } catch (error) {
            body.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
            updateSummary([], 0);
            updatePagination(0);
        }
    }

    function buildParams(includePaging) {
        const params = new URLSearchParams({
            search: filters.search.value,
            status: filters.status.value,
            assigned_to: filters.assigned_to.value,
            date_from: filters.date_from.value,
            date_to: filters.date_to.value
        });

        if (includePaging) {
            params.set('start', (state.page - 1) * state.length);
            params.set('length', state.length);
        }

        return params;
    }

    function renderRows(rows) {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">No registrations found</td></tr>';
            return;
        }

        body.innerHTML = rows.map(row => `
            <tr>
                <td>${typeBadge(row)}</td>
                <td class="fw-semibold">${escapeHtml(row.ref || 'N/A')}</td>
                <td>${escapeHtml(row.tin || 'N/A')}</td>
                <td>${escapeHtml(row.full_name || 'N/A')}</td>
                <td>${escapeHtml(row.email || 'N/A')}</td>
                <td>${escapeHtml(row.assigned_to || 'Unassigned')}</td>
                <td>${statusBadge(row.status)}</td>
                <td>${escapeHtml(row.submitted_at || 'N/A')}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-info view-btn" data-id="${escapeHtml(row.id)}" data-type="${escapeHtml(row.registration_kind || 'individual')}">
                        <i class="mdi mdi-eye"></i> View
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function displayRegistrationDetails(reg) {
        document.getElementById('modal-ref').textContent = reg.ref || 'N/A';
        document.getElementById('registration-details').innerHTML = `
            <div class="row">
                <div class="col-lg-6">
                    <h6 class="text-uppercase text-muted">Profile</h6>
                    <table class="table table-sm table-borderless">
                        <tr><th width="35%">Type</th><td>${escapeHtml(reg.registration_type_label || 'Individual')}</td></tr>
                        <tr><th>Name</th><td>${escapeHtml(reg.full_name || [reg.title, reg.forenames, reg.surname].filter(Boolean).join(' ') || 'N/A')}</td></tr>
                        <tr><th>Email</th><td>${escapeHtml(reg.email || 'N/A')}</td></tr>
                        <tr><th>TIN</th><td>${escapeHtml(reg.tin || 'N/A')}</td></tr>
                    </table>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-uppercase text-muted">Workflow</h6>
                    <table class="table table-sm table-borderless">
                        <tr><th width="35%">Status</th><td>${escapeHtml(reg.status || 'N/A')}</td></tr>
                        <tr><th>Assigned To</th><td>${escapeHtml(reg.assigned_to || 'Unassigned')}</td></tr>
                        <tr><th>Submitted</th><td>${escapeHtml(reg.submitted_at || 'N/A')}</td></tr>
                        <tr><th>Notes</th><td>${escapeHtml(reg.remarks || 'N/A')}</td></tr>
                    </table>
                </div>
            </div>
        `;
    }

    async function openDetails(id, type) {
        const baseUrl = type === 'business' ? '{{ url('api/ds/business-registrations') }}' : '{{ url('api/ds/registrations') }}';
        document.getElementById('registration-details').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('registrationModal')).show();

        try {
            const response = await fetch(`${baseUrl}/${id}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Failed to load registration details.');
            const json = await response.json();
            displayRegistrationDetails(json.registration);
        } catch (error) {
            document.getElementById('registration-details').innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
        }
    }

    function updateSummary(rows, total) {
        document.getElementById('total-count').textContent = total;
        document.getElementById('pending-count').textContent = rows.filter(row => row.status === 'PENDING').length;
        document.getElementById('review-count').textContent = rows.filter(row => row.status === 'UNDER_REVIEW').length;
        document.getElementById('approved-count').textContent = rows.filter(row => row.status === 'APPROVED').length;
    }

    function updatePagination(rowCount) {
        const start = state.total === 0 ? 0 : ((state.page - 1) * state.length) + 1;
        const end = Math.min(state.page * state.length, state.total);
        document.getElementById('result-summary').textContent = `Showing ${start}-${end} of ${state.total} registrations`;
        document.getElementById('current-page').textContent = `Page ${state.page}`;
        document.getElementById('prev-page').disabled = state.page === 1;
        document.getElementById('next-page').disabled = rowCount < state.length || state.page * state.length >= state.total;
    }

    function setLoading() {
        body.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 mb-0">Loading registrations...</p>
                </td>
            </tr>
        `;
    }

    function typeBadge(row) {
        const isBusiness = row.registration_kind === 'business';
        return `<span class="badge ${isBusiness ? 'bg-dark' : 'bg-primary'}">${escapeHtml(row.registration_type_label || 'Individual')}</span>`;
    }

    function statusBadge(status) {
        const badges = {
            PENDING: 'bg-warning',
            APPROVED: 'bg-success',
            REJECTED: 'bg-danger',
            UNDER_REVIEW: 'bg-info'
        };
        return `<span class="badge ${badges[status] || 'bg-secondary'}">${escapeHtml(status || 'N/A')}</span>`;
    }

    function debounce(callback, wait) {
        let timeout;
        return function() {
            clearTimeout(timeout);
            timeout = setTimeout(callback, wait);
        };
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }
});
</script>
@endpush
