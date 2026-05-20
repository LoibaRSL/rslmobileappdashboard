{{-- resources/views/ds/dashboard.blade.php --}}
@extends('layouts.ds')

@section('page-title', 'Digital Services Dashboard')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('ds-content')
<div class="row">
    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6">
        <div class="card widget-flat border-primary">
            <div class="card-body">
                <div class="float-end">
                    <i class="ri-inbox-unarchive-line text-primary widget-icon"></i>
                </div>
                <h5 class="text-muted fw-normal mt-0" title="Unassigned">Unassigned Submissions</h5>
                <h3 class="mt-3 mb-1" id="unassigned-count">0</h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">Awaiting assignment</span>
                </p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card widget-flat border-info">
            <div class="card-body">
                <div class="float-end">
                    <i class="ri-user-star-line text-info widget-icon"></i>
                </div>
                <h5 class="text-muted fw-normal mt-0" title="Assigned to me">Assigned to Me</h5>
                <h3 class="mt-3 mb-1" id="assigned-count">0</h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">Pending review</span>
                </p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card widget-flat border-success">
            <div class="card-body">
                <div class="float-end">
                    <i class="ri-checkbox-circle-line text-success widget-icon"></i>
                </div>
                <h5 class="text-muted fw-normal mt-0" title="Approved">Approved</h5>
                <h3 class="mt-3 mb-1" id="approved-count">0</h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">Successfully processed</span>
                </p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card widget-flat border-danger">
            <div class="card-body">
                <div class="float-end">
                    <i class="ri-close-circle-line text-danger widget-icon"></i>
                </div>
                <h5 class="text-muted fw-normal mt-0" title="Rejected">Rejected</h5>
                <h3 class="mt-3 mb-1" id="rejected-count">0</h3>
                <p class="mb-0 text-muted">
                    <span class="text-nowrap">Returned for corrections</span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Action Cards / Quick Stats -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary bg-gradient">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="text-white mb-0" id="total-pending">0</h4>
                        <p class="text-white-50 mb-0">Total Pending</p>
                    </div>
                    <i class="ri-time-line text-white-50 font-24"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success bg-gradient">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="text-white mb-0" id="approval-rate">0%</h4>
                        <p class="text-white-50 mb-0">Approval Rate</p>
                    </div>
                    <i class="ri-bar-chart-2-line text-white-50 font-24"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Section -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs nav-bordered mb-3" id="dsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="unassigned-tab" data-bs-toggle="tab" 
                                data-bs-target="#unassigned" type="button" role="tab">
                            <i class="ri-inbox-unarchive-line me-1"></i> Unassigned
                            <span class="badge bg-danger ms-1" id="unassigned-badge">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="my-assignments-tab" data-bs-toggle="tab" 
                                data-bs-target="#my-assignments" type="button" role="tab">
                            <i class="ri-user-star-line me-1"></i> My Assignments
                            <span class="badge bg-primary ms-1" id="my-assignments-badge">0</span>
                        </button>
                    </li>
                    @if(auth()->user()->isAdmin())
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="all-tab" data-bs-toggle="tab" 
                                data-bs-target="#all" type="button" role="tab">
                            <i class="ri-list-check-2 me-1"></i> All Applications
                        </button>
                    </li>
                    @endif
                </ul>

                <div class="tab-content">
                    <!-- Unassigned Tab -->
                    <div class="tab-pane fade show active" id="unassigned" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-centered table-nowrap mb-0" id="unassigned-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Reference</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Submitted Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- My Assignments Tab -->
                    <div class="tab-pane fade" id="my-assignments" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-centered table-nowrap mb-0" id="assignments-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Reference</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Assigned Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- All Applications Tab (Admin only) -->
                    @if(auth()->user()->isAdmin())
                    <div class="tab-pane fade" id="all" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <select class="form-select" id="status-filter">
                                    <option value="">All Status</option>
                                    <option value="PENDING">Pending</option>
                                    <option value="UNDER_REVIEW">Under Review</option>
                                    <option value="APPROVED">Approved</option>
                                    <option value="REJECTED">Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="assigned-filter">
                                    <option value="">All Users</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="search-input" placeholder="Search by name, TIN, ref...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-success w-100" id="export-btn">
                                    <i class="ri-download-line me-1"></i> Export
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-centered table-nowrap mb-0" id="all-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Reference</th>
                                        <th>TIN</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Assigned To</th>
                                        <th>Status</th>
                                        <th>Submitted Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Registration Details Modal -->
<div class="modal fade" id="registrationModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="ri-file-copy-line me-2"></i>
                    Registration Details
                    <span class="badge bg-light text-dark ms-2" id="modal-ref"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="registration-details-content">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="modal-actions">
                <!-- Dynamic buttons will appear here -->
            </div>
        </div>
    </div>
</div>

<!-- Assign to User Modal -->
<div class="modal fade" id="assignUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign to DS User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assign-registration-id">
                <div class="mb-3">
                    <label class="form-label">Select DS User</label>
                    <select class="form-select" id="assign-user-id" required>
                        <option value="">Choose user...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea class="form-control" id="assign-notes" rows="3" 
                              placeholder="Add any notes about this assignment..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-assign-btn">Assign</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Approve Registration</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approve-registration-id">
                <div class="mb-3">
                    <label class="form-label">TIN Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="approve-tin" 
                           placeholder="Enter TIN number (e.g., IND202605200001)" required>
                    <small class="text-muted">Format: IND + YYYYMMDD + 4-digit sequence</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks (Optional)</label>
                    <textarea class="form-control" id="approve-remarks" rows="3" 
                              placeholder="Add approval notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirm-approve-btn">Approve</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Reject Registration</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reject-registration-id">
                <div class="mb-3">
                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reject-remarks" rows="4" 
                              placeholder="Please provide detailed reason for rejection..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-reject-btn">Reject</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('ds-scripts')
<script>
$(document).ready(function() {
    // Initialize DataTables
    let unassignedTable = $('#unassigned-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("ds.api.registrations.unassigned") }}',
            dataSrc: 'data.data'
        },
        columns: [
            { data: 'ref' },
            { data: 'full_name' },
            { data: 'email' },
            { data: 'submitted_at' },
            { 
                data: null,
                render: function(data) {
                    return `
                        <button class="btn btn-sm btn-info view-btn" data-id="${data.id}">
                            <i class="ri-eye-line"></i> View
                        </button>
                        <button class="btn btn-sm btn-primary assign-self-btn" data-id="${data.id}">
                            <i class="ri-user-add-line"></i> Assign to Me
                        </button>
                        ${isAdmin ? `
                        <button class="btn btn-sm btn-warning assign-user-btn" data-id="${data.id}">
                            <i class="ri-group-line"></i> Assign
                        </button>
                        ` : ''}
                    `;
                }
            }
        ],
        order: [[3, 'desc']],
        pageLength: 25
    });

    // My Assignments Table
    let assignmentsTable = $('#assignments-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("ds.api.registrations.my-assignments") }}',
            dataSrc: 'data.data'
        },
        columns: [
            { data: 'ref' },
            { data: 'full_name' },
            { data: 'email' },
            { data: 'assigned_at' },
            { 
                data: 'status',
                render: function(data) {
                    let badges = {
                        'PENDING': 'warning',
                        'UNDER_REVIEW': 'info',
                        'APPROVED': 'success',
                        'REJECTED': 'danger'
                    };
                    return `<span class="badge bg-${badges[data] || 'secondary'}">${data}</span>`;
                }
            },
            { 
                data: null,
                render: function(data) {
                    let actions = `
                        <button class="btn btn-sm btn-info view-btn" data-id="${data.id}">
                            <i class="ri-eye-line"></i>
                        </button>
                    `;
                    
                    if (data.status === 'UNDER_REVIEW' || data.status === 'PENDING') {
                        actions += `
                            <button class="btn btn-sm btn-success approve-btn" data-id="${data.id}">
                                <i class="ri-check-line"></i>
                            </button>
                            <button class="btn btn-sm btn-danger reject-btn" data-id="${data.id}">
                                <i class="ri-close-line"></i>
                            </button>
                        `;
                    }
                    
                    return actions;
                }
            }
        ],
        order: [[3, 'desc']],
        pageLength: 25
    });

    // All Applications Table (Admin only)
    let allTable = null;
    @if(auth()->user()->isAdmin())
    allTable = $('#all-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("ds.api.registrations.all") }}',
            dataSrc: 'data.data',
            data: function(d) {
                d.status = $('#status-filter').val();
                d.assigned_to = $('#assigned-filter').val();
                d.search = $('#search-input').val();
            }
        },
        columns: [
            { data: 'ref' },
            { data: 'tin' },
            { data: 'full_name' },
            { data: 'email' },
            { data: 'assigned_to' },
            { 
                data: 'status',
                render: function(data) {
                    let badges = {
                        'PENDING': 'warning',
                        'UNDER_REVIEW': 'info',
                        'APPROVED': 'success',
                        'REJECTED': 'danger'
                    };
                    return `<span class="badge bg-${badges[data] || 'secondary'}">${data}</span>`;
                }
            },
            { data: 'submitted_at' },
            { 
                data: null,
                render: function(data) {
                    return `<button class="btn btn-sm btn-info view-btn" data-id="${data.id}">
                                <i class="ri-eye-line"></i> View
                            </button>`;
                }
            }
        ],
        order: [[6, 'desc']],
        pageLength: 25
    });

    // Filter change handlers
    $('#status-filter, #assigned-filter, #search-input').on('change keyup', function() {
        allTable.ajax.reload();
    });

    // Load DS users for filter
    $.get('{{ route("ds.api.users.ds-users") }}', function(response) {
        if (response.success) {
            let options = '<option value="">All Users</option>';
            response.users.forEach(user => {
                options += `<option value="${user.id}">${user.name}</option>`;
            });
            $('#assigned-filter').html(options);
            $('#assign-user-id').html(options);
        }
    });
    @endif

    // Load dashboard stats
    function loadStats() {
        $.get('{{ route("ds.api.dashboard") }}', function(response) {
            if (response.success) {
                $('#unassigned-count').text(response.stats.unassigned);
                $('#assigned-count').text(response.stats.assigned_to_me);
                $('#approved-count').text(response.stats.my_approved);
                $('#rejected-count').text(response.stats.my_rejected);
                $('#total-pending').text(response.stats.total_pending);
                $('#approval-rate').text(response.stats.approval_rate + '%');
                $('#unassigned-badge').text(response.stats.unassigned);
                $('#my-assignments-badge').text(response.stats.assigned_to_me);
            }
        });
    }

    // View Registration Details
    $(document).on('click', '.view-btn', function() {
        let id = $(this).data('id');
        $('#registrationModal').modal('show');
        
        $.get(`/api/ds/registrations/${id}`, function(response) {
            if (response.success) {
                displayRegistrationDetails(response.registration);
            }
        }).fail(function(xhr) {
            Swal.fire('Error', 'Failed to load registration details', 'error');
        });
    });

    function displayRegistrationDetails(reg) {
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="ri-user-line me-2"></i>Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><td width="40%"><strong>Full Name:</strong></td><td>${reg.title} ${reg.forenames} ${reg.surname}</td></tr>
                                <tr><td><strong>Maiden Name:</strong></td><td>${reg.maiden_name || 'N/A'}</td></tr>
                                <tr><td><strong>Date of Birth:</strong></td><td>${reg.date_of_birth || 'N/A'}</td></tr>
                                <tr><td><strong>Country of Birth:</strong></td><td>${reg.country_of_birth || 'N/A'}</td></tr>
                                <tr><td><strong>Citizenship:</strong></td><td>${reg.country_of_citizenship || 'N/A'}</td></tr>
                                <tr><td><strong>Residence:</strong></td><td>${reg.country_of_residence || 'N/A'}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="ri-mail-line me-2"></i>Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><td width="40%"><strong>Email:</strong></td><td>${reg.email}</td></tr>
                                <tr><td><strong>Phone:</strong></td><td>${reg.phone_details?.map(p => `${p.phone_code}${p.phone_number}`).join(', ') || 'N/A'}</td></tr>
                                <tr><td><strong>Marital Status:</strong></td><td>${reg.marital_status || 'N/A'}</td></tr>
                                ${reg.spouse_name ? `<tr><td><strong>Spouse:</strong></td><td>${reg.spouse_name}</td></tr>` : ''}
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-secondary mb-3">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="ri-building-line me-2"></i>Employers</h6>
                        </div>
                        <div class="card-body">
                            ${reg.employers?.length ? `
                                <ul class="list-group">
                                    ${reg.employers.map(emp => `
                                        <li class="list-group-item">
                                            <strong>${emp.employer_name}</strong>
                                            ${emp.file_path ? `<br><small class="text-muted"><i class="ri-attachment-line"></i> Proof attached</small>` : ''}
                                        </li>
                                    `).join('')}
                                </ul>
                            ` : '<p class="text-muted">No employers listed</p>'}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-secondary mb-3">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="ri-bank-line me-2"></i>Banking Details</h6>
                        </div>
                        <div class="card-body">
                            ${reg.banking_details?.length ? `
                                <ul class="list-group">
                                    ${reg.banking_details.map(bank => `
                                        <li class="list-group-item">
                                            <strong>${bank.bank_name || bank.bank_code}</strong><br>
                                            Account: ${bank.account_number}<br>
                                            Holder: ${bank.account_holder_name}
                                        </li>
                                    `).join('')}
                                </ul>
                            ` : '<p class="text-muted">No banking details</p>'}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card border-warning mb-3">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0"><i class="ri-attachment-line me-2"></i>Uploaded Documents</h6>
                        </div>
                        <div class="card-body">
                            ${reg.files?.length ? `
                                <div class="row">
                                    ${reg.files.map(file => `
                                        <div class="col-md-4 mb-2">
                                            <a href="/storage/${file.url}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                                <i class="ri-file-pdf-line me-1"></i> ${file.name}
                                            </a>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : '<p class="text-muted">No documents uploaded</p>'}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card border-dark mb-3">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="ri-history-line me-2"></i>Assignment History</h6>
                        </div>
                        <div class="card-body">
                            ${reg.assignment_history?.length ? `
                                <ul class="list-group">
                                    ${reg.assignment_history.map(hist => `
                                        <li class="list-group-item">
                                            <strong>${hist.action}</strong> by ${hist.assigned_by} to ${hist.assigned_to}<br>
                                            <small class="text-muted">${hist.created_at}</small>
                                            ${hist.notes ? `<br><small>Note: ${hist.notes}</small>` : ''}
                                        </li>
                                    `).join('')}
                                </ul>
                            ` : '<p class="text-muted">No assignment history</p>'}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#registration-details-content').html(html);
        $('#modal-ref').text(reg.ref);
        
        // Set action buttons based on status
        let actions = `
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        `;
        
        if (reg.status === 'PENDING' && !reg.assigned_to) {
            actions += `
                <button type="button" class="btn btn-primary" id="modal-assign-self" data-id="${reg.id}">
                    <i class="ri-user-add-line"></i> Assign to Me
                </button>
                ${isAdmin ? `
                <button type="button" class="btn btn-warning" id="modal-assign-user" data-id="${reg.id}">
                    <i class="ri-group-line"></i> Assign to User
                </button>
                ` : ''}
            `;
        } else if (reg.assigned_to === '${currentUserName}' || isAdmin) {
            if (reg.status === 'UNDER_REVIEW' || reg.status === 'PENDING') {
                actions += `
                    <button type="button" class="btn btn-success" id="modal-approve" data-id="${reg.id}">
                        <i class="ri-check-line"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger" id="modal-reject" data-id="${reg.id}">
                        <i class="ri-close-line"></i> Reject
                    </button>
                `;
            }
        }
        
        $('#modal-actions').html(actions);
        
        // Bind modal actions
        $('#modal-assign-self').on('click', function() {
            $('#registrationModal').modal('hide');
            assignToSelf($(this).data('id'));
        });
        
        $('#modal-approve').on('click', function() {
            $('#registrationModal').modal('hide');
            showApproveModal($(this).data('id'));
        });
        
        $('#modal-reject').on('click', function() {
            $('#registrationModal').modal('hide');
            showRejectModal($(this).data('id'));
        });
    }

    // Assign to Self
    function assignToSelf(id) {
        Swal.fire({
            title: 'Assign to yourself?',
            text: "You will be responsible for reviewing this application.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Yes, assign to me'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`/api/ds/registrations/${id}/assign-to-self`)
                    .done(function() {
                        Swal.fire('Assigned!', 'Registration assigned to you successfully.', 'success');
                        loadStats();
                        unassignedTable.ajax.reload();
                        assignmentsTable.ajax.reload();
                        if (allTable) allTable.ajax.reload();
                    })
                    .fail(function() {
                        Swal.fire('Error!', 'Failed to assign registration.', 'error');
                    });
            }
        });
    }

    // Show Approve Modal
    function showApproveModal(id) {
        $('#approve-registration-id').val(id);
        $('#approve-tin').val('');
        $('#approve-remarks').val('');
        $('#approveModal').modal('show');
    }

    // Show Reject Modal
    function showRejectModal(id) {
        $('#reject-registration-id').val(id);
        $('#reject-remarks').val('');
        $('#rejectModal').modal('show');
    }

    // Confirm Approve
    $('#confirm-approve-btn').click(function() {
        let id = $('#approve-registration-id').val();
        let tin = $('#approve-tin').val();
        let remarks = $('#approve-remarks').val();
        
        if (!tin) {
            Swal.fire('Error', 'TIN number is required', 'error');
            return;
        }
        
        $.post(`/api/ds/registrations/${id}/approve`, { tin: tin, remarks: remarks })
            .done(function() {
                Swal.fire('Approved!', 'Registration approved successfully.', 'success');
                $('#approveModal').modal('hide');
                loadStats();
                assignmentsTable.ajax.reload();
                if (allTable) allTable.ajax.reload();
            })
            .fail(function(xhr) {
                Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to approve.', 'error');
            });
    });

    // Confirm Reject
    $('#confirm-reject-btn').click(function() {
        let id = $('#reject-registration-id').val();
        let remarks = $('#reject-remarks').val();
        
        if (!remarks) {
            Swal.fire('Error', 'Rejection reason is required', 'error');
            return;
        }
        
        $.post(`/api/ds/registrations/${id}/reject`, { remarks: remarks })
            .done(function() {
                Swal.fire('Rejected!', 'Registration has been rejected.', 'success');
                $('#rejectModal').modal('hide');
                loadStats();
                assignmentsTable.ajax.reload();
                if (allTable) allTable.ajax.reload();
            })
            .fail(function() {
                Swal.fire('Error!', 'Failed to reject registration.', 'error');
            });
    });

    // Event handlers for table buttons
    $(document).on('click', '.assign-self-btn', function() {
        assignToSelf($(this).data('id'));
    });
    
    $(document).on('click', '.approve-btn', function() {
        showApproveModal($(this).data('id'));
    });
    
    $(document).on('click', '.reject-btn', function() {
        showRejectModal($(this).data('id'));
    });
    
    @if(auth()->user()->isAdmin())
    $(document).on('click', '.assign-user-btn', function() {
        let id = $(this).data('id');
        $('#assign-registration-id').val(id);
        $('#assign-user-id').val('');
        $('#assign-notes').val('');
        $('#assignUserModal').modal('show');
    });
    
    $('#confirm-assign-btn').click(function() {
        let id = $('#assign-registration-id').val();
        let userId = $('#assign-user-id').val();
        let notes = $('#assign-notes').val();
        
        if (!userId) {
            Swal.fire('Error', 'Please select a user to assign to', 'error');
            return;
        }
        
        $.post(`/api/ds/registrations/${id}/assign-to-user`, { user_id: userId, notes: notes })
            .done(function() {
                Swal.fire('Assigned!', 'Registration assigned to user successfully.', 'success');
                $('#assignUserModal').modal('hide');
                loadStats();
                unassignedTable.ajax.reload();
                if (allTable) allTable.ajax.reload();
            })
            .fail(function() {
                Swal.fire('Error!', 'Failed to assign registration.', 'error');
            });
    });
    
    // Export
    $('#export-btn').click(function() {
        let params = new URLSearchParams({
            status: $('#status-filter').val(),
            assigned_to: $('#assigned-filter').val(),
            date_from: $('#date-from').val(),
            date_to: $('#date-to').val()
        }).toString();
        
        window.location.href = `/api/ds/export?${params}`;
    });
    @endif

    // Auto-refresh every 30 seconds
    setInterval(function() {
        loadStats();
        if (unassignedTable) unassignedTable.ajax.reload(null, false);
        if (assignmentsTable) assignmentsTable.ajax.reload(null, false);
    }, 30000);
    
    // Initial load
    loadStats();
});

// JavaScript variables
const isAdmin = @json(auth()->user()->isAdmin());
const currentUserName = @json(auth()->user()->name);
</script>
@endpush