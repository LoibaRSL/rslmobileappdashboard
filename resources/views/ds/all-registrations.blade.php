@extends("shared.base", ["title" => "All TIN Registrations"])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">RSL Mobile Dashboard</a></li>
                        <li class="breadcrumb-item active">All Registrations</li>
                    </ol>
                </div>
                <h4 class="page-title">All TIN Registrations</h4>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Name, TIN, Reference...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All</option>
                                <option value="PENDING">Pending</option>
                                <option value="UNDER_REVIEW">Under Review</option>
                                <option value="APPROVED">Approved</option>
                                <option value="REJECTED">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Assigned To</label>
                            <select name="assigned_to" id="assigned_to" class="form-select">
                                <option value="">All</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" id="resetBtn" class="btn btn-secondary w-100">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Button -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Registration List</h5>
                        <a href="#" id="exportBtn" class="btn btn-info">
                            <i class="mdi mdi-download"></i> Export to CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registrations Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0" id="registrations-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
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
                                <tr>
                                    <td colspan="9" class="text-center py-4">
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
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registration Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

<script>
$(document).ready(function() {
    var table = $('#registrations-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("ds.api.registrations.all") }}',
            data: function(d) {
                d.search = $('#search').val();
                d.status = $('#status').val();
                d.assigned_to = $('#assigned_to').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            },
            dataSrc: 'data.data'
        },
        columns: [
            { data: 'id' },
            { data: 'ref' },
            { data: 'tin', defaultContent: 'N/A' },
            { data: 'full_name' },
            { data: 'email' },
            { data: 'assigned_to', defaultContent: 'Unassigned' },
            { 
                data: 'status',
                render: function(data) {
                    var badges = {
                        'PENDING': 'bg-warning',
                        'APPROVED': 'bg-success',
                        'REJECTED': 'bg-danger',
                        'UNDER_REVIEW': 'bg-info'
                    };
                    return `<span class="badge ${badges[data] || 'bg-secondary'}">${data}</span>`;
                }
            },
            { data: 'submitted_at' },
            {
                data: null,
                render: function(data) {
                    return `<button class="btn btn-sm btn-info view-btn" data-id="${data.id}">
                                <i class="mdi mdi-eye"></i> View
                            </button>`;
                }
            }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: "No registrations found"
        }
    });
    
    // Load DS users for filter
    $.get('{{ route("ds.api.users.ds-users") }}', function(response) {
        if (response.success) {
            let options = '<option value="">All</option>';
            response.users.forEach(user => {
                options += `<option value="${user.id}">${user.name}</option>`;
            });
            $('#assigned_to').html(options);
        }
    });
    
    // Filter change events
    $('#search, #status, #assigned_to, #date_from, #date_to').on('keyup change', function() {
        table.ajax.reload();
    });
    
    $('#resetBtn').click(function() {
        $('#search').val('');
        $('#status').val('');
        $('#assigned_to').val('');
        $('#date_from').val('');
        $('#date_to').val('');
        table.ajax.reload();
    });
    
    $('#exportBtn').click(function(e) {
        e.preventDefault();
        let params = new URLSearchParams({
            search: $('#search').val(),
            status: $('#status').val(),
            assigned_to: $('#assigned_to').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        }).toString();
        window.location.href = `{{ url('api/ds/export') }}?${params}`;
    });
    
    $(document).on('click', '.view-btn', function() {
        var id = $(this).data('id');
        $('#registrationModal').modal('show');
        
        $.get(`{{ url('api/ds/registrations') }}/${id}`, function(response) {
            displayRegistrationDetails(response.registration);
        }).fail(function() {
            $('#registration-details').html('<div class="alert alert-danger">Failed to load registration details</div>');
        });
    });
    
    function displayRegistrationDetails(reg) {
        var html = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th width="35%">Full Name:</th><td>${reg.title} ${reg.forenames} ${reg.surname}</td></tr>
                                <tr><th>Date of Birth:</th><td>${reg.date_of_birth || 'N/A'}</td></tr>
                                <tr><th>Email:</th><td>${reg.email}</td></tr>
                                <tr><th>Marital Status:</th><td>${reg.marital_status || 'N/A'}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Status Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th>Status:</th><td><span class="badge bg-${reg.status === 'APPROVED' ? 'success' : (reg.status === 'REJECTED' ? 'danger' : 'warning')}">${reg.status}</span></td></tr>
                                <tr><th>Assigned To:</th><td>${reg.assigned_to || 'Unassigned'}</td></tr>
                                <tr><th>Submitted:</th><td>${reg.submitted_at}</td></tr>
                                <tr><th>Remarks:</th><td>${reg.remarks || 'N/A'}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">Employers</h6>
                        </div>
                        <div class="card-body">
                            ${reg.employers && reg.employers.length ? 
                                `<ul class="list-group list-group-flush">
                                    ${reg.employers.map(e => `<li class="list-group-item">${e.employer_name}${e.file_path ? ' <i class="mdi mdi-attachment"></i>' : ''}</li>`).join('')}
                                </ul>` : 
                                '<p class="text-muted mb-0">No employers listed</p>'}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">Documents</h6>
                        </div>
                        <div class="card-body">
                            ${reg.files && reg.files.length ? 
                                `<div class="list-group">
                                    ${reg.files.map(f => `<a href="/storage/${f.file_path}" target="_blank" class="list-group-item list-group-item-action">
                                        <i class="mdi mdi-file-pdf"></i> ${f.file_name}
                                    </a>`).join('')}
                                </div>` : 
                                '<p class="text-muted mb-0">No documents uploaded</p>'}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#registration-details').html(html);
        
        // Add action buttons if needed
        let actions = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
        $('#modal-actions').html(actions);
    }
});
</script>
@endsection