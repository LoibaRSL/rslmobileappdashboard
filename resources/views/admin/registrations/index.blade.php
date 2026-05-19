@extends("shared.base", ["title" => "Dashboard"])

@section('title', 'Business Registrations')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Business Registrations</li>
                    </ol>
                </div>
                <h4 class="page-title">Business Registrations Management</h4>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2">{{ $stats['total'] }}</h4>
                            <p class="mb-0 text-muted">Total Applications</p>
                        </div>
                        <div class="avatar-sm bg-primary-subtle rounded">
                            <i class="mdi mdi-chart-line avatar-title text-primary fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2 text-warning">{{ $stats['pending'] }}</h4>
                            <p class="mb-0 text-muted">Pending Approval</p>
                        </div>
                        <div class="avatar-sm bg-warning-subtle rounded">
                            <i class="mdi mdi-clock avatar-title text-warning fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2 text-success">{{ $stats['approved'] }}</h4>
                            <p class="mb-0 text-muted">Approved</p>
                        </div>
                        <div class="avatar-sm bg-success-subtle rounded">
                            <i class="mdi mdi-check-circle avatar-title text-success fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-2 text-danger">{{ $stats['rejected'] }}</h4>
                            <p class="mb-0 text-muted">Rejected</p>
                        </div>
                        <div class="avatar-sm bg-danger-subtle rounded">
                            <i class="mdi mdi-close-circle avatar-title text-danger fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Name, TIN, Reference..." 
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Application Type</label>
                            <select name="application_type" class="form-select">
                                <option value="">All</option>
                                <option value="New" {{ request('application_type') == 'New' ? 'selected' : '' }}>New</option>
                                <option value="Amendment" {{ request('application_type') == 'Amendment' ? 'selected' : '' }}>Amendment</option>
                                <option value="Renewal" {{ request('application_type') == 'Renewal' ? 'selected' : '' }}>Renewal</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <a href="{{ route('admin.registrations.index') }}" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    @can('registration.approve')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button class="btn btn-success" onclick="bulkApprove()" id="bulkApproveBtn" disabled>
                            <i class="mdi mdi-check-all"></i> Bulk Approve
                        </button>
                        <button class="btn btn-danger" onclick="showBulkRejectModal()" id="bulkRejectBtn" disabled>
                            <i class="mdi mdi-close"></i> Bulk Reject
                        </button>
                        <a href="{{ route('admin.registrations.export', request()->query()) }}" class="btn btn-info">
                            <i class="mdi mdi-download"></i> Export to CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <!-- Registrations Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    @can('registration.approve')
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    @endcan
                                    <th>Reference No</th>
                                    <th>Legal Name</th>
                                    <th>Business Type</th>
                                    <th>Application Type</th>
                                    <th>TIN</th>
                                    <th>Status</th>
                                    <th>Submitted Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($registrations as $reg)
                                <tr>
                                    @can('registration.approve')
                                    <td>
                                        @if($reg->status === 'pending')
                                        <input type="checkbox" class="form-check-input registration-checkbox" 
                                               value="{{ $reg->id }}">
                                        @endif
                                    </td>
                                    @endcan
                                    <td>
                                        <strong>{{ $reg->reference_number }}</strong>
                                    </td>
                                    <td>{{ $reg->legal_name }}</td>
                                    <td>{{ $reg->business_type }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $reg->application_type }}</span>
                                    </td>
                                    <td>{{ $reg->new_tin ?? $reg->old_tin ?? 'N/A' }}</td>
                                    <td>
                                        @if($reg->status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($reg->status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @else
                                            <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>{{ $reg->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.registrations.show', $reg->id) }}" 
                                               class="btn btn-sm btn-info">
                                                <i class="mdi mdi-eye"></i> View
                                            </a>
                                            @if($reg->status === 'pending')
                                                @can('registration.approve')
                                                <button onclick="approveRegistration({{ $reg->id }})" 
                                                        class="btn btn-sm btn-success">
                                                    <i class="mdi mdi-check"></i> Approve
                                                </button>
                                                @endcan
                                                @can('registration.reject')
                                                <button onclick="showRejectModal({{ $reg->id }})" 
                                                        class="btn btn-sm btn-danger">
                                                    <i class="mdi mdi-close"></i> Reject
                                                </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="mdi mdi-inbox fs-48 text-muted"></i>
                                        <p class="mt-2 mb-0">No registrations found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-end">
                        {{ $registrations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Single Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="rejectForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="5" required 
                                  placeholder="Please provide detailed reason for rejection..."></textarea>
                        <small class="text-muted">Minimum 10 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Reject Modal -->
<div class="modal fade" id="bulkRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.registrations.bulk-reject') }}">
                @csrf
                <input type="hidden" name="registration_ids" id="bulkRegistrationIds">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Reject Registrations</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="bulk_rejection_reason" class="form-control" rows="5" required 
                                  placeholder="Please provide detailed reason for rejection..."></textarea>
                        <small class="text-muted">Minimum 10 characters</small>
                    </div>
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert"></i>
                        You are about to reject <span id="rejectCount">0</span> registration(s). This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Bulk Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Approve Form -->
<form method="POST" id="bulkApproveForm" action="{{ route('admin.registrations.bulk-approve') }}">
    @csrf
    <input type="hidden" name="registration_ids" id="bulkApproveIds">
</form>

<script>
let selectedIds = [];

// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function(e) {
    const checkboxes = document.querySelectorAll('.registration-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = e.target.checked;
    });
    updateBulkButtons();
});

// Individual checkboxes
document.querySelectorAll('.registration-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkButtons);
});

function updateBulkButtons() {
    const checkboxes = document.querySelectorAll('.registration-checkbox:checked');
    selectedIds = Array.from(checkboxes).map(cb => cb.value);
    
    const approveBtn = document.getElementById('bulkApproveBtn');
    const rejectBtn = document.getElementById('bulkRejectBtn');
    
    if (selectedIds.length > 0) {
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
    } else {
        approveBtn.disabled = true;
        rejectBtn.disabled = true;
    }
}

function approveRegistration(id) {
    if (confirm('Are you sure you want to approve this registration?')) {
        // Create a form and submit it (since approve uses POST method)
        const form = document.createElement('form');
        form.method = 'POST';
        // Use the route helper with a placeholder
        form.action = '{{ route("admin.registrations.approve", ":id") }}'.replace(':id', id);
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectModal(id) {
    // Store the ID for use when confirming
    window.currentRejectId = id;
    // Update the form action with the ID
    updateRejectFormAction(id);
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function updateRejectFormAction(id) {
    const form = document.getElementById('rejectForm');
    if (form) {
        form.action = '{{ route("admin.registrations.reject", ":id") }}'.replace(':id', id);
    }
}

// When the reject modal is about to be shown, ensure the form action is correct
document.addEventListener('DOMContentLoaded', function() {
    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', function() {
            if (window.currentRejectId) {
                updateRejectFormAction(window.currentRejectId);
            }
        });
    }
});}
</script>
@endsection