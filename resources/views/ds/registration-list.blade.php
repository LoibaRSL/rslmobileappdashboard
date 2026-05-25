@extends('layouts.ds')

@section('page-title', $pageTitle)

@section('breadcrumbs')
    <li class="breadcrumb-item">{{ $registrationTypeLabel }}</li>
    <li class="breadcrumb-item active">{{ $scopeLabel }}</li>
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
            <div class="col-xl-1 col-md-6">
                <button type="button" id="resetBtn" class="btn btn-secondary w-100">Reset</button>
            </div>
            <div class="col-12">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" id="saveFilterBtn" class="btn btn-sm btn-outline-primary">
                        <i class="mdi mdi-content-save-outline"></i> Save View
                    </button>
                    <button type="button" id="restoreFilterBtn" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-restore"></i> Restore Saved View
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="mb-1">{{ $registrationTypeLabel }} Registration Queue</h5>
                <p class="text-muted mb-0">{{ $scopeLabel }} for {{ strtolower($registrationTypeLabel) }} registrations only.</p>
            </div>
            <a href="#" id="exportBtn" class="btn btn-info">
                <i class="mdi mdi-download"></i> Export CSV
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-centered align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>TIN</th>
                        <th>{{ $registrationType === 'business' ? 'Business Name' : 'Full Name' }}</th>
                        <th>Email</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="registrations-body">
                    <tr>
                        <td colspan="8" class="text-center py-4">
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
                <h5 class="modal-title">{{ $registrationTypeLabel }} Registration <span class="badge bg-light text-dark ms-2" id="modal-ref"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="registration-details">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer" id="modal-actions">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="review-header">
                <h5 class="modal-title" id="review-title">Review Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="review-id">
                <input type="hidden" id="review-action">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <label class="form-label mb-0">Remarks</label>
                        <button type="button" class="btn btn-sm btn-outline-info d-none" id="ai-draft-rejection">
                            <i class="mdi mdi-auto-fix"></i> Draft with AI
                        </button>
                    </div>
                    <textarea id="review-remarks" class="form-control" rows="4" placeholder="Add review notes"></textarea>
                    <small class="text-muted d-none" id="ai-draft-help">Uses only the reason you type here and removes identifiers before sending to the AI provider.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn" id="confirm-review">Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="assign-title">Assign Registration</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assign-id">
                <div class="mb-3">
                    <label class="form-label">Digital Services User</label>
                    <select id="assign-user-id" class="form-select">
                        <option value="">Choose user...</option>
                    </select>
                    <small class="text-muted">Choose yourself or any other Digital Services user.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-assign">Assign</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('ds-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const config = {
        apiUrl: @json($apiUrl),
        detailBaseUrl: @json($detailBaseUrl),
        exportType: @json($exportType),
        aiSummaryBaseUrl: @json(url('api/ds/operations')),
        aiRejectionDraftUrl: @json(route('ds.api.operations.ai.rejection-draft')),
        attachmentBaseUrl: @json(url('api/ds/attachments')),
        csrf: @json(csrf_token()),
        currentUserId: @json((string) auth()->id()),
        canAssign: @json(auth()->user()?->hasPermission('registration.assign') ?? false),
        canReassign: @json(auth()->user()?->hasPermission('registration.reassign') ?? false),
        canApprove: @json(auth()->user()?->hasPermission('registration.approve') ?? false),
        canReject: @json(auth()->user()?->hasPermission('registration.reject') ?? false)
    };
    const savedFilterKey = `ds-registration-filters:${config.exportType}`;
    const state = { page: 1, length: 25, total: 0, rows: [] };
    let dsUsers = [];
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

    document.getElementById('saveFilterBtn').addEventListener('click', saveCurrentFilters);
    document.getElementById('restoreFilterBtn').addEventListener('click', restoreSavedFilters);

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
        const params = buildParams(false);
        params.set('type', config.exportType);
        window.location.href = `{{ url('api/ds/export') }}?${params.toString()}`;
    });

    body.addEventListener('click', function(event) {
        const viewBtn = event.target.closest('.view-btn');
        const assignBtn = event.target.closest('.assign-btn');
        const approveBtn = event.target.closest('.approve-btn');
        const rejectBtn = event.target.closest('.reject-btn');

        if (viewBtn) openDetails(viewBtn.dataset.id);
        if (assignBtn) openAssign(assignBtn.dataset.id, assignBtn.dataset.assigned === '1');
        if (approveBtn) openReview(approveBtn.dataset.id, 'approve');
        if (rejectBtn) openReview(rejectBtn.dataset.id, 'reject');
    });

    document.getElementById('modal-actions').addEventListener('click', function(event) {
        const approveBtn = event.target.closest('.approve-btn');
        const rejectBtn = event.target.closest('.reject-btn');
        const assignBtn = event.target.closest('.assign-btn');

        if (approveBtn) openReview(approveBtn.dataset.id, 'approve');
        if (rejectBtn) openReview(rejectBtn.dataset.id, 'reject');
        if (assignBtn) openAssign(assignBtn.dataset.id, assignBtn.dataset.assigned === '1');
    });

    document.getElementById('registration-details').addEventListener('click', function(event) {
        const button = event.target.closest('#ai-summary-btn');
        if (button) loadAiSummary(button);
    });

    document.getElementById('confirm-review').addEventListener('click', submitReview);
    document.getElementById('confirm-assign').addEventListener('click', submitAssignment);
    document.getElementById('ai-draft-rejection').addEventListener('click', draftRejectionWithAi);

    async function loadUsers() {
        try {
            const response = await fetch('{{ route("ds.api.users.ds-users") }}', { headers: { Accept: 'application/json' } });
            const json = await response.json();
            if (!json.success) return;

            dsUsers = json.users || [];
            filters.assigned_to.innerHTML = '<option value="">All Users</option>' + json.users
                .map(user => `<option value="${escapeHtml(user.id)}">${escapeHtml(user.name)}</option>`)
                .join('');
            renderAssignUsers();
        } catch (error) {
            console.warn('Unable to load DS users', error);
        }
    }

    async function loadRegistrations() {
        setLoading();

        try {
            const response = await fetch(`${config.apiUrl}?${buildParams(true).toString()}`, {
                headers: { Accept: 'application/json' }
            });

            if (!response.ok) {
                throw new Error(response.status === 401 ? 'Please sign in again.' : 'Failed to load registrations.');
            }

            const json = await response.json();
            const rows = json.data?.data || json.data || [];
            state.rows = rows;
            state.total = json.recordsFiltered || rows.length;
            renderRows(rows);
            updateSummary(rows, state.total);
            updatePagination(rows.length);
        } catch (error) {
            body.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
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
            body.innerHTML = AppUX.emptyState(
                8,
                'mdi-file-search-outline',
                'No registrations found',
                'Try changing the search, date range, status, or assigned user filters.'
            );
            return;
        }

        body.innerHTML = rows.map(row => `
            <tr>
                <td class="fw-semibold">${escapeHtml(row.ref || 'N/A')}</td>
                <td>${escapeHtml(row.tin || 'N/A')}</td>
                <td>${escapeHtml(row.full_name || 'N/A')}</td>
                <td>${escapeHtml(row.email || 'N/A')}</td>
                <td>${escapeHtml(row.assigned_to || 'Unassigned')}</td>
                <td>${statusBadge(row.status)}</td>
                <td>${escapeHtml(row.submitted_at || 'N/A')}</td>
                <td class="text-end">${actionButtons(row)}</td>
            </tr>
        `).join('');
    }

    function actionButtons(row) {
        const id = escapeHtml(row.id);
        const assigned = row.assigned_to && row.assigned_to !== 'Unassigned';
        let buttons = `<button class="btn btn-sm btn-outline-info btn-icon-text view-btn" data-id="${id}" title="View details"><i class="mdi mdi-eye"></i><span>View</span></button>`;

        if ((row.status === 'PENDING' || row.status === 'UNDER_REVIEW') && (assigned ? config.canReassign : config.canAssign)) {
            buttons += ` <button class="btn btn-sm btn-outline-primary btn-icon-text assign-btn" data-id="${id}" data-assigned="${assigned ? '1' : '0'}" title="${assigned ? 'Reassign' : 'Assign'}"><i class="mdi mdi-account-switch"></i><span>${assigned ? 'Reassign' : 'Assign'}</span></button>`;
        }

        if (row.status === 'PENDING' || row.status === 'UNDER_REVIEW') {
            if (config.canApprove) {
                buttons += ` <button class="btn btn-sm btn-outline-success btn-icon-text approve-btn" data-id="${id}" title="Approve"><i class="mdi mdi-check"></i><span>Approve</span></button>`;
            }

            if (config.canReject) {
                buttons += ` <button class="btn btn-sm btn-outline-danger btn-icon-text reject-btn" data-id="${id}" title="Reject"><i class="mdi mdi-close"></i><span>Reject</span></button>`;
            }
        }

        return buttons;
    }

    async function openDetails(id) {
        document.getElementById('registration-details').innerHTML = '<div class="p-2">' + AppUX.skeletonRows(2, 5).replaceAll('<tr', '<div').replaceAll('</tr>', '</div>').replaceAll('<td>', '<div class="mb-3">').replaceAll('</td>', '</div>') + '</div>';
        document.getElementById('modal-actions').innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('registrationModal')).show();

        try {
            const response = await fetch(`${config.detailBaseUrl}/${id}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Failed to load registration details.');
            const json = await response.json();
            displayRegistrationDetails(json.registration);
        } catch (error) {
            document.getElementById('registration-details').innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
        }
    }

    function displayRegistrationDetails(reg) {
        document.getElementById('modal-ref').textContent = reg.ref || 'N/A';
        document.getElementById('registration-details').innerHTML = `
            <div class="row">
                <div class="col-lg-6">
                    <h6 class="text-uppercase text-muted">Profile</h6>
                    <table class="table table-sm table-borderless">
                        <tr><th width="35%">Type</th><td>${escapeHtml(reg.registration_type_label || 'N/A')}</td></tr>
                        <tr><th>Name</th><td>${escapeHtml(reg.full_name || [reg.title, reg.forenames, reg.surname].filter(Boolean).join(' ') || 'N/A')}</td></tr>
                        <tr><th>Email</th><td>${escapeHtml(reg.email || 'N/A')}</td></tr>
                        <tr><th>TIN</th><td>${escapeHtml(reg.tin || 'N/A')}</td></tr>
                        <tr><th>Business Type</th><td>${escapeHtml(reg.business_type || 'N/A')}</td></tr>
                    </table>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-uppercase text-muted">Workflow</h6>
                    <table class="table table-sm table-borderless">
                        <tr><th width="35%">Status</th><td>${statusBadge(reg.status)}</td></tr>
                        <tr><th>Assigned To</th><td>${escapeHtml(reg.assigned_to || 'Unassigned')}</td></tr>
                        <tr><th>Submitted</th><td>${escapeHtml(reg.submitted_at || 'N/A')}</td></tr>
                        <tr><th>Notes</th><td>${escapeHtml(reg.remarks || 'N/A')}</td></tr>
                    </table>
                </div>
            </div>
            <div class="mt-3">
                <h6 class="text-uppercase text-muted">Uploaded Documents</h6>
                ${documentsList(reg.files || [])}
            </div>
            <div class="mt-3">
                <h6 class="text-uppercase text-muted">Complete Application Information</h6>
                ${detailSections(reg.sections || {})}
            </div>
            <div class="mt-3 border rounded p-3 bg-light">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="text-uppercase text-muted mb-1">AI Case Assistant</h6>
                        <p class="text-muted small mb-0">Privacy-safe summary based on workflow status, completeness, and operational events.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-info" id="ai-summary-btn" data-type="${escapeHtml(reg.registration_kind || config.exportType || 'individual')}" data-id="${escapeHtml(reg.id)}">
                        <i class="mdi mdi-auto-fix"></i> Generate Summary
                    </button>
                </div>
                <div class="mt-3 small" id="ai-summary-output"></div>
            </div>
            <div class="mt-3">
                <h6 class="text-uppercase text-muted">Operational Timeline</h6>
                ${timelineList(reg.timeline || [])}
            </div>
        `;

        let actions = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
        if (reg.status === 'PENDING' || reg.status === 'UNDER_REVIEW') {
            const assigned = reg.assigned_to && reg.assigned_to !== 'Unassigned';
            if (assigned ? config.canReassign : config.canAssign) {
                actions += `<button type="button" class="btn btn-primary assign-btn" data-id="${escapeHtml(reg.id)}" data-assigned="${assigned ? '1' : '0'}">${assigned ? 'Reassign' : 'Assign'}</button>`;
            }

            if (config.canApprove) {
                actions += `<button type="button" class="btn btn-success approve-btn" data-id="${escapeHtml(reg.id)}">Approve</button>`;
            }

            if (config.canReject) {
                actions += `<button type="button" class="btn btn-danger reject-btn" data-id="${escapeHtml(reg.id)}">Reject</button>`;
            }
        }
        document.getElementById('modal-actions').innerHTML = actions;
    }

    function documentsList(files) {
        if (!files.length) return '<p class="text-muted mb-0">No documents uploaded</p>';

        return `<div class="list-group">${files.map(file => `
            <a href="${config.attachmentBaseUrl}/${escapeHtml(file.registration_kind || file.kind || '') || escapeHtml(currentRegistrationKind())}/${escapeHtml(file.id || '')}" target="_blank" class="list-group-item list-group-item-action">
                <i class="mdi mdi-file-document"></i> ${escapeHtml(file.file_name || file.original_filename || file.original_name || 'Document')}
            </a>
        `).join('')}</div>`;
    }

    function currentRegistrationKind() {
        return config.exportType === 'individual_amendment' ? 'individual_amendment' : config.exportType;
    }

    function detailSections(sections) {
        const entries = Object.entries(sections).filter(([, data]) => hasDisplayValue(data));
        if (!entries.length) return '<p class="text-muted mb-0">No additional information available.</p>';

        return `<div class="accordion" id="detailSections">${entries.map(([title, data], index) => `
            <div class="accordion-item">
                <h2 class="accordion-header" id="section-heading-${index}">
                    <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#section-panel-${index}">
                        ${escapeHtml(title)}
                    </button>
                </h2>
                <div id="section-panel-${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#detailSections">
                    <div class="accordion-body">
                        ${valueTable(data)}
                    </div>
                </div>
            </div>
        `).join('')}</div>`;
    }

    function valueTable(data) {
        if (Array.isArray(data)) {
            if (!data.length) return '<p class="text-muted mb-0">N/A</p>';
            return data.every(item => typeof item !== 'object' || item === null)
                ? `<div class="d-flex flex-wrap gap-1">${data.map(item => `<span class="badge bg-secondary-subtle text-secondary">${escapeHtml(formatValue(item))}</span>`).join('')}</div>`
                : data.map((item, index) => `<div class="border rounded p-2 mb-2"><div class="fw-semibold small text-muted mb-2">Item ${index + 1}</div>${valueTable(item)}</div>`).join('');
        }

        if (typeof data === 'object' && data !== null) {
            const rows = Object.entries(data).filter(([, value]) => hasDisplayValue(value));
            if (!rows.length) return '<p class="text-muted mb-0">N/A</p>';
            return `<div class="table-responsive"><table class="table table-sm table-borderless mb-0 detail-table"><tbody>${rows.map(([key, value]) => `
                <tr>
                    <th>${escapeHtml(labelize(key))}</th>
                    <td>${typeof value === 'object' && value !== null ? valueTable(value) : escapeHtml(formatValue(value))}</td>
                </tr>
            `).join('')}</tbody></table></div>`;
        }

        return escapeHtml(formatValue(data));
    }

    function hasDisplayValue(value) {
        if (value === null || value === undefined || value === '') return false;
        if (Array.isArray(value)) return value.length > 0;
        if (typeof value === 'object') return Object.values(value).some(item => hasDisplayValue(item));
        return true;
    }

    function formatValue(value) {
        if (value === true) return 'Yes';
        if (value === false) return 'No';
        return String(value ?? 'N/A');
    }

    function labelize(key) {
        return String(key).replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
    }

    function timelineList(events) {
        if (!events.length) return '<p class="text-muted mb-0">No operational events recorded yet.</p>';

        const badge = status => ({
            success: 'bg-success',
            failed: 'bg-danger',
            retried: 'bg-info',
            info: 'bg-secondary'
        }[status] || 'bg-secondary');

        return `<div class="list-group">${events.map(event => `
            <div class="list-group-item">
                <div class="d-flex justify-content-between gap-2">
                    <div>
                        <div class="fw-semibold">${escapeHtml(event.title || 'Event')} <span class="badge ${badge(event.status)}">${escapeHtml(event.status || 'info')}</span></div>
                        <div class="text-muted small">${escapeHtml(event.message || '')}</div>
                        <div class="text-muted small">${escapeHtml(event.user || 'System')}</div>
                    </div>
                    <small class="text-muted text-nowrap">${escapeHtml(event.created_at || '')}</small>
                </div>
            </div>
        `).join('')}</div>`;
    }

    function renderAssignUsers() {
        const select = document.getElementById('assign-user-id');
        const sorted = [...dsUsers].sort((a, b) => {
            if (String(a.id) === config.currentUserId) return -1;
            if (String(b.id) === config.currentUserId) return 1;
            return String(a.name).localeCompare(String(b.name));
        });

        select.innerHTML = '<option value="">Choose user...</option>' + sorted.map(user => {
            const isMe = String(user.id) === config.currentUserId;
            return `<option value="${escapeHtml(user.id)}">${escapeHtml(user.name)}${isMe ? ' (Me)' : ''}</option>`;
        }).join('');
    }

    function openAssign(id, isReassign) {
        document.getElementById('assign-id').value = id;
        document.getElementById('assign-title').textContent = isReassign ? 'Reassign Registration' : 'Assign Registration';
        document.getElementById('confirm-assign').textContent = isReassign ? 'Reassign' : 'Assign';
        document.getElementById('assign-user-id').value = config.currentUserId;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('assignModal')).show();
    }

    async function submitAssignment() {
        const id = document.getElementById('assign-id').value;
        const userId = document.getElementById('assign-user-id').value;

        if (!userId) {
            AppUX.toast('Please choose a Digital Services user.', 'warning');
            return;
        }

        await postAction(`${config.detailBaseUrl}/${id}/assign-to-user`, { user_id: userId }, 'Registration assigned successfully');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('assignModal')).hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('registrationModal')).hide();
    }

    function openReview(id, action) {
        document.getElementById('review-id').value = id;
        document.getElementById('review-action').value = action;
        document.getElementById('review-remarks').value = '';
        document.getElementById('ai-draft-rejection').classList.toggle('d-none', action !== 'reject');
        document.getElementById('ai-draft-help').classList.toggle('d-none', action !== 'reject');
        document.getElementById('review-title').textContent = action === 'approve' ? 'Approve Registration' : 'Reject Registration';
        document.getElementById('review-header').className = action === 'approve' ? 'modal-header bg-success text-white' : 'modal-header bg-danger text-white';
        document.getElementById('confirm-review').className = action === 'approve' ? 'btn btn-success' : 'btn btn-danger';
        document.getElementById('confirm-review').textContent = action === 'approve' ? 'Approve' : 'Reject';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal')).show();
    }

    async function loadAiSummary(button) {
        const output = document.getElementById('ai-summary-output');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating';
        output.innerHTML = '<div class="text-muted">Generating privacy-safe case summary...</div>';

        try {
            const response = await fetch(`${config.aiSummaryBaseUrl}/${button.dataset.type}/${button.dataset.id}/ai-summary`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf }
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) throw new Error(json.message || 'AI summary failed.');
            output.innerHTML = `<div class="alert alert-info mb-0 white-space-pre-line">${escapeHtml(json.content || 'No summary returned.')}</div>`;
        } catch (error) {
            output.innerHTML = `<div class="alert alert-warning mb-0">${escapeHtml(error.message)}</div>`;
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="mdi mdi-auto-fix"></i> Generate Summary';
        }
    }

    async function draftRejectionWithAi() {
        const button = document.getElementById('ai-draft-rejection');
        const textarea = document.getElementById('review-remarks');
        const reason = textarea.value.trim();

        if (reason.length < 3) {
            AppUX.toast('Type the rough rejection reason first, then AI can clean it up.', 'warning');
            return;
        }

        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Drafting';

        try {
            const response = await fetch(config.aiRejectionDraftUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf
                },
                body: JSON.stringify({ reason, type: config.exportType })
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) throw new Error(json.message || 'AI draft failed.');
            textarea.value = (json.content || reason).trim();
            AppUX.toast('AI draft added to the remarks box.');
        } catch (error) {
            AppUX.toast(error.message, 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="mdi mdi-auto-fix"></i> Draft with AI';
        }
    }

    async function submitReview() {
        const id = document.getElementById('review-id').value;
        const action = document.getElementById('review-action').value;
        const remarks = document.getElementById('review-remarks').value.trim();

        if (action === 'reject' && remarks.length < 5) {
            AppUX.toast('Please provide a rejection reason.', 'warning');
            return;
        }

        await postAction(`${config.detailBaseUrl}/${id}/${action}`, { remarks }, `Registration ${action}d successfully`);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal')).hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('registrationModal')).hide();
    }

    async function postAction(url, payload, fallbackMessage) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf
                },
                body: JSON.stringify(payload)
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) throw new Error(json.message || 'Request failed.');
            AppUX.toast(json.message || fallbackMessage);
            loadRegistrations();
        } catch (error) {
            AppUX.toast(error.message, 'error');
        }
    }

    function saveCurrentFilters() {
        const values = {};
        Object.entries(filters).forEach(([key, input]) => values[key] = input.value);
        localStorage.setItem(savedFilterKey, JSON.stringify(values));
        AppUX.toast('Current queue view saved.');
    }

    function restoreSavedFilters() {
        const raw = localStorage.getItem(savedFilterKey);
        if (!raw) {
            AppUX.toast('No saved view found for this queue.', 'info');
            return;
        }

        let values = {};
        try {
            values = JSON.parse(raw);
        } catch (error) {
            localStorage.removeItem(savedFilterKey);
            AppUX.toast('Saved view could not be restored.', 'error');
            return;
        }
        Object.entries(filters).forEach(([key, input]) => {
            input.value = values[key] || '';
        });
        state.page = 1;
        AppUX.toast('Saved queue view restored.');
        loadRegistrations();
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
        body.innerHTML = AppUX.skeletonRows(8, 6);
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

@push('ds-styles')
<style>
.btn-icon-text {
    align-items: center;
    display: inline-flex;
    gap: 4px;
    margin-block: 2px;
}
.badge.bg-warning {
    color: #2f2300;
}
.white-space-pre-line {
    white-space: pre-line;
}
.detail-table th {
    color: #64748b;
    font-weight: 600;
    min-width: 180px;
    width: 28%;
}
.detail-table td {
    word-break: break-word;
}
</style>
@endpush
