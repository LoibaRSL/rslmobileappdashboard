@extends("shared.base", ["title" => "My Assignments"])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('ds.dashboard') }}">Digital Services</a></li>
                        <li class="breadcrumb-item active">My Assignments</li>
                    </ol>
                </div>
                <h4 class="page-title">My Assignments</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0" id="assignments-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Reference</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Assigned Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="spinner-border text-primary"></div>
                                        <p class="mt-2 mb-0">Loading data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Registration Details Modal -->
<div class="modal fade" id="registrationModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-file-document me-2"></i>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                <input type="hidden" id="approve-id">
                <div class="mb-3">
                    <label class="form-label">Remarks (Optional)</label>
                    <textarea id="approve-remarks" class="form-control" rows="3" placeholder="Add approval notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirm-approve">Approve</button>
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
                <input type="hidden" id="reject-id">
                <div class="mb-3">
                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea id="reject-remarks" class="form-control" rows="5" placeholder="Please provide detailed reason for rejection..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-reject">Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#assignments-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("ds.api.registrations.my-assignments") }}',
            dataSrc: 'data.data'
        },
        columns: [
            { data: 'id' },
            { data: 'ref' },
            { data: 'full_name' },
            { data: 'email' },
            { data: 'assigned_at' },
            { 
                data: 'status',
                render: function(data) {
                    var badges = {
                        'PENDING': 'bg-warning',
                        'APPROVED': 'bg-success',
                        'REJECTED': 'bg-danger',
                        'UNDER_REVIEW': 'bg-info'
                    };
                    var statusText = {
                        'PENDING': 'Pending',
                        'APPROVED': 'Approved',
                        'REJECTED': 'Rejected',
                        'UNDER_REVIEW': 'Under Review'
                    };
                    return `<span class="badge ${badges[data] || 'bg-secondary'}">${statusText[data] || data}</span>`;
                }
            },
            {
                data: null,
                render: function(data) {
                    let actions = `<button class="btn btn-sm btn-info view-btn" data-id="${data.id}" data-type="${data.registration_kind}">
                                        <i class="mdi mdi-eye"></i> View
                                    </button>`;
                    
                    if (data.status === 'PENDING' || data.status === 'UNDER_REVIEW') {
                        actions += `<button class="btn btn-sm btn-success approve-btn ms-1" data-id="${data.id}" data-type="${data.registration_kind}">
                                        <i class="mdi mdi-check"></i> Approve
                                    </button>
                                    <button class="btn btn-sm btn-danger reject-btn ms-1" data-id="${data.id}" data-type="${data.registration_kind}">
                                        <i class="mdi mdi-close"></i> Reject
                                    </button>`;
                    }
                    return actions;
                }
            }
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: "No assignments found"
        }
    });
    
    // View Registration Details
    $(document).on('click', '.view-btn', function() {
        var id = $(this).data('id');
        var type = $(this).data('type') || 'individual';
        var baseUrl = type === 'business' ? '{{ url('api/ds/business-registrations') }}' : '{{ url('api/ds/registrations') }}';
        $('#registrationModal').modal('show');
        $('#modal-ref').text('Loading...');
        
        $.get(`${baseUrl}/${id}`, function(response) {
            if (response.success) {
                displayRegistrationDetails(response.registration);
            } else {
                $('#registration-details-content').html('<div class="alert alert-danger">Failed to load registration details</div>');
            }
        }).fail(function() {
            $('#registration-details-content').html('<div class="alert alert-danger">Error loading registration details</div>');
        });
    });
    
    function displayRegistrationDetails(reg) {
        // Set reference in modal header
        $('#modal-ref').text(reg.ref || 'N/A');
        
        var html = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="mdi mdi-account"></i> Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Full Name:</th>
                                    <td>${reg.title || ''} ${reg.forenames || ''} ${reg.surname || ''}</td>
                                </tr>
                                <tr>
                                    <th>Maiden Name:</th>
                                    <td>${reg.maiden_name || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Date of Birth:</th>
                                    <td>${reg.date_of_birth || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Country of Birth:</th>
                                    <td>${reg.country_of_birth || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Citizenship:</th>
                                    <td>${reg.country_of_citizenship || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Residence:</th>
                                    <td>${reg.country_of_residence || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Marital Status:</th>
                                    <td>${reg.marital_status || 'N/A'}</td>
                                </tr>
                                ${reg.spouse_name ? `<tr><th>Spouse:</th><td>${reg.spouse_name}</td></tr>` : ''}
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="mdi mdi-email"></i> Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Email:</th>
                                    <td>${reg.email || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Phone Numbers:</th>
                                    <td>
                                        ${reg.phone_details && reg.phone_details.length ? 
                                            reg.phone_details.map(p => `${p.phone_code || '+266'}${p.phone_number}`).join('<br>') : 
                                            'N/A'}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Postal Address:</th>
                                    <td>
                                        ${reg.post_address1 || ''} ${reg.post_address2 || ''}<br>
                                        ${reg.post_city || ''} ${reg.post_code || ''}<br>
                                        ${reg.post_country || ''}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Physical Address:</th>
                                    <td>
                                        ${reg.street_name || ''}<br>
                                        ${reg.village || ''} ${reg.town || ''}<br>
                                        ${reg.physical_district || ''}, ${reg.physical_country || ''}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="mdi mdi-information"></i> Registration Status</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Reference:</th>
                                    <td><strong>${reg.ref || 'N/A'}</strong></td>
                                </tr>
                                <tr>
                                    <th>TIN:</th>
                                    <td>${reg.tin || '<span class="text-muted">Not assigned yet</span>'}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge ${reg.status === 'APPROVED' ? 'bg-success' : (reg.status === 'REJECTED' ? 'bg-danger' : 'bg-warning')}">
                                            ${reg.status || 'PENDING'}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Assigned To:</th>
                                    <td>${reg.assigned_to || 'Unassigned'}</td>
                                </tr>
                                <tr>
                                    <th>Assigned Date:</th>
                                    <td>${reg.assigned_at || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Submitted Date:</th>
                                    <td>${reg.submitted_at || reg.created_at || 'N/A'}</td>
                                </tr>
                                ${reg.remarks ? `<tr><th>Remarks:</th><td>${reg.remarks}</td></tr>` : ''}
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="mdi mdi-bank"></i> Banking Details</h6>
                        </div>
                        <div class="card-body">
                            ${reg.banking_details && reg.banking_details.length ? 
                                `<div class="list-group">
                                    ${reg.banking_details.map(bank => `
                                        <div class="list-group-item">
                                            <strong>${bank.bank_name || bank.bank_code || 'Bank'}</strong><br>
                                            Account: ${bank.account_number || 'N/A'}<br>
                                            Holder: ${bank.account_holder_name || 'N/A'}<br>
                                            Branch: ${bank.branch || 'N/A'}
                                        </div>
                                    `).join('')}
                                </div>` : 
                                '<p class="text-muted mb-0">No banking details provided</p>'}
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="mdi mdi-phone"></i> Mobile Money</h6>
                        </div>
                        <div class="card-body">
                            ${reg.mobile_money_details && reg.mobile_money_details.length ? 
                                `<div class="list-group">
                                    ${reg.mobile_money_details.map(mobile => `
                                        <div class="list-group-item">
                                            <strong>${mobile.mobile_money_type || 'Mobile Money'}</strong><br>
                                            Number: ${mobile.mobile_money_number || 'N/A'}
                                        </div>
                                    `).join('')}
                                </div>` : 
                                '<p class="text-muted mb-0">No mobile money details provided</p>'}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="mdi mdi-domain"></i> Employers</h6>
                        </div>
                        <div class="card-body">
                            ${reg.employers && reg.employers.length ? 
                                `<div class="list-group">
                                    ${reg.employers.map(emp => `
                                        <div class="list-group-item">
                                            <strong>${emp.employer_name}</strong>
                                            ${emp.file_path ? '<br><small><i class="mdi mdi-attachment"></i> Proof attached</small>' : ''}
                                        </div>
                                    `).join('')}
                                </div>` : 
                                '<p class="text-muted mb-0">No employers listed</p>'}
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="mdi mdi-file-document"></i> Uploaded Documents</h6>
                        </div>
                        <div class="card-body">
                            ${reg.files && reg.files.length ? 
                                `<div class="list-group">
                                    ${reg.files.map(file => `
                                        <a href="${attachmentUrl(reg, file)}" target="_blank" class="list-group-item list-group-item-action">
                                            <i class="mdi mdi-file-pdf"></i> ${file.file_name}
                                            <small class="text-muted d-block">Type: ${file.file_type}</small>
                                        </a>
                                    `).join('')}
                                </div>` : 
                                '<p class="text-muted mb-0">No documents uploaded</p>'}
                        </div>
                    </div>
                </div>
            </div>
            
            ${reg.assignment_history && reg.assignment_history.length ? `
            <div class="row">
                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0"><i class="mdi mdi-history"></i> Assignment History</h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                ${reg.assignment_history.map(hist => `
                                    <div class="timeline-item">
                                        <div class="timeline-badge bg-info"></div>
                                        <div class="timeline-panel">
                                            <div class="timeline-heading">
                                                <h6 class="timeline-title">${hist.action}</h6>
                                                <p><small class="text-muted">${hist.created_at}</small></p>
                                            </div>
                                            <div class="timeline-body">
                                                <p>Assigned by: <strong>${hist.assigned_by}</strong><br>
                                                Assigned to: <strong>${hist.assigned_to}</strong><br>
                                                ${hist.notes ? `Notes: ${hist.notes}` : ''}</p>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
        `;
        
        $('#registration-details-content').html(html);
        
        // Set action buttons based on status
        let actions = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
        
        if (reg.status === 'PENDING' || reg.status === 'UNDER_REVIEW') {
            actions += `
                <button type="button" class="btn btn-success" id="modal-approve" data-id="${reg.id}" data-type="${reg.registration_kind}">
                    <i class="mdi mdi-check"></i> Approve
                </button>
                <button type="button" class="btn btn-danger" id="modal-reject" data-id="${reg.id}" data-type="${reg.registration_kind}">
                    <i class="mdi mdi-close"></i> Reject
                </button>
            `;
        }
        
        $('#modal-actions').html(actions);
        
        // Bind modal actions
        $('#modal-approve').off('click').on('click', function() {
            $('#registrationModal').modal('hide');
            showApproveModal($(this).data('id'), $(this).data('type'));
        });
        
        $('#modal-reject').off('click').on('click', function() {
            $('#registrationModal').modal('hide');
            showRejectModal($(this).data('id'), $(this).data('type'));
        });
    }
    
    function showApproveModal(id, type = 'individual') {
        $('#approve-id').val(id);
        $('#approve-id').data('type', type);
        $('#approve-remarks').val('');
        $('#approveModal').modal('show');
    }
    
    function showRejectModal(id, type = 'individual') {
        $('#reject-id').val(id);
        $('#reject-id').data('type', type);
        $('#reject-remarks').val('');
        $('#rejectModal').modal('show');
    }

    function attachmentUrl(reg, file) {
        const kind = file.registration_kind || reg.registration_kind || (reg.registration_type === 'AMND' || reg.registration_type === 'AMEND' ? 'individual_amendment' : 'individual');
        return `{{ url('api/ds/attachments') }}/${kind}/${file.id}`;
    }
    
    // Approve button in table
    $(document).on('click', '.approve-btn', function() {
        var id = $(this).data('id');
        showApproveModal(id, $(this).data('type'));
    });
    
    // Reject button in table
    $(document).on('click', '.reject-btn', function() {
        var id = $(this).data('id');
        showRejectModal(id, $(this).data('type'));
    });
    
    // Confirm Approve
    $('#confirm-approve').click(function() {
        let id = $('#approve-id').val();
        let type = $('#approve-id').data('type') || 'individual';
        let baseUrl = type === 'business' ? '{{ url('api/ds/business-registrations') }}' : '{{ url('api/ds/registrations') }}';
        let remarks = $('#approve-remarks').val();
        
        $.post(`${baseUrl}/${id}/approve`, { 
            remarks: remarks 
        })
        .done(function(response) {
            Swal.fire('Approved!', response.message || 'Registration approved successfully', 'success');
            $('#approveModal').modal('hide');
            table.ajax.reload();
        })
        .fail(function(xhr) {
            let message = xhr.responseJSON?.message || 'Failed to approve registration';
            Swal.fire('Error', message, 'error');
        });
    });
    
    // Confirm Reject
    $('#confirm-reject').click(function() {
        let id = $('#reject-id').val();
        let type = $('#reject-id').data('type') || 'individual';
        let baseUrl = type === 'business' ? '{{ url('api/ds/business-registrations') }}' : '{{ url('api/ds/registrations') }}';
        let remarks = $('#reject-remarks').val();
        
        if (!remarks || remarks.length < 10) {
            Swal.fire('Error', 'Please provide a detailed rejection reason (minimum 10 characters)', 'error');
            return;
        }
        
        $.post(`${baseUrl}/${id}/reject`, { remarks: remarks })
        .done(function(response) {
            Swal.fire('Rejected!', response.message || 'Registration rejected', 'success');
            $('#rejectModal').modal('hide');
            table.ajax.reload();
        })
        .fail(function(xhr) {
            let message = xhr.responseJSON?.message || 'Failed to reject registration';
            Swal.fire('Error', message, 'error');
        });
    });
    
    // Auto-refresh every 60 seconds
    setInterval(function() {
        table.ajax.reload(null, false);
    }, 60000);
});
</script>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}
.timeline-item {
    position: relative;
    margin-bottom: 20px;
    display: flex;
}
.timeline-badge {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}
.timeline-panel {
    flex: 1;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}
.timeline-heading {
    margin-bottom: 10px;
}
.timeline-title {
    margin: 0;
    text-transform: capitalize;
}
</style>
@endsection
