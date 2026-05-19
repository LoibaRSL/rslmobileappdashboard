@extends('layouts.app')

@section('title', 'Registration Details - ' . $registration->reference_number)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.registrations.index') }}">Registrations</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $registration->reference_number }}</li>
                    </ol>
                </div>
                <h4 class="page-title">Registration Details</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <!-- Main Information -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Business Information</h5>
                        <span class="badge bg-{{ $registration->status === 'approved' ? 'success' : ($registration->status === 'rejected' ? 'danger' : 'warning') }} fs-14">
                            {{ ucfirst($registration->status) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Reference Number:</th>
                                    <td><strong>{{ $registration->reference_number }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Legal Name:</th>
                                    <td>{{ $registration->legal_name }}</td>
                                </tr>
                                <tr>
                                    <th>Business Type:</th>
                                    <td>{{ $registration->business_type }}</td>
                                </tr>
                                <tr>
                                    <th>Application Type:</th>
                                    <td>{{ $registration->application_type }}</td>
                                </tr>
                                <tr>
                                    <th>Old TIN:</th>
                                    <td>{{ $registration->old_tin ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>New TIN:</th>
                                    <td>{{ $registration->new_tin ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Registration Number:</th>
                                    <td>{{ $registration->registration_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Sole Trader:</th>
                                    <td>{{ $registration->is_sole_trader ? 'Yes' : 'No' }}</td>
                                </tr>
                                @if($registration->is_sole_trader)
                                <tr>
                                    <th>Surname/Forename:</th>
                                    <td>{{ $registration->surname }} {{ $registration->forename }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th>Submitted Date:</th>
                                    <td>{{ $registration->created_at->format('d M Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated:</th>
                                    <td>{{ $registration->updated_at->format('d M Y H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Details -->
            @if($registration->contactDetails)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Contact Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Postal Address:</h6>
                            <p class="mb-3">
                                {{ $registration->contactDetails->postal_address1 }}<br>
                                @if($registration->contactDetails->postal_address2){{ $registration->contactDetails->postal_address2 }}<br>@endif
                                @if($registration->contactDetails->postal_address3){{ $registration->contactDetails->postal_address3 }}<br>@endif
                                {{ $registration->contactDetails->postal_city }}, {{ $registration->contactDetails->postal_county }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Physical Address:</h6>
                            <p class="mb-3">
                                {{ $registration->contactDetails->physical_address1 }}<br>
                                @if($registration->contactDetails->physical_address2){{ $registration->contactDetails->physical_address2 }}<br>@endif
                                @if($registration->contactDetails->physical_address3){{ $registration->contactDetails->physical_address3 }}<br>@endif
                                {{ $registration->contactDetails->physical_city }}, {{ $registration->contactDetails->physical_county }}
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Email:</h6>
                            <p>{{ $registration->contactDetails->email }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Phone Numbers:</h6>
                            <p>
                                Cell: {{ $registration->contactDetails->cell_phone }}<br>
                                Office: {{ $registration->contactDetails->office_phone ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Accountant Details -->
            @if($registration->accountantDetails)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Accountant Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> {{ $registration->accountantDetails->name }}</p>
                            <p><strong>TIN:</strong> {{ $registration->accountantDetails->tin }}</p>
                            <p><strong>Email:</strong> {{ $registration->accountantDetails->email }}</p>
                            <p><strong>Phone:</strong> {{ $registration->accountantDetails->cell_phone }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Address:</strong></p>
                            <p>{{ $registration->accountantDetails->postal_address1 }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Director Partners -->
            @if($registration->directorPartners && $registration->directorPartners->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Directors/Partners</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>TIN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($registration->directorPartners as $index => $director)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $director->name }}</td>
                                    <td>{{ $director->tin }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-xl-4">
            <!-- Action Buttons -->
            @if($registration->status === 'pending')
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Actions</h5>
                    <div class="d-grid gap-2">
                        @can('registration.approve')
                        <button onclick="approveRegistration({{ $registration->id }})" class="btn btn-success">
                            <i class="mdi mdi-check"></i> Approve Registration
                        </button>
                        @endcan
                        @can('registration.reject')
                        <button onclick="showRejectModal({{ $registration->id }})" class="btn btn-danger">
                            <i class="mdi mdi-close"></i> Reject Registration
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
            @endif

            <!-- Tax Registration Details -->
            @if($registration->vatDetails || $registration->payeDetails)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tax Registrations</h5>
                </div>
                <div class="card-body">
                    @if($registration->vatDetails)
                    <div class="mb-3">
                        <strong>VAT:</strong>
                        @if($registration->vatDetails->register_for_vat)
                            <span class="badge bg-success">Registered</span>
                            <small class="d-block">Effective: {{ $registration->vatDetails->vat_effective_date }}</small>
                        @else
                            <span class="badge bg-secondary">Not Registered</span>
                        @endif
                    </div>
                    @endif
                    
                    @if($registration->payeDetails)
                    <div>
                        <strong>PAYE:</strong>
                        @if($registration->payeDetails->register_for_paye)
                            <span class="badge bg-success">Registered</span>
                            <small class="d-block">Employer Date: {{ $registration->payeDetails->paye_employer_date }}</small>
                        @else
                            <span class="badge bg-secondary">Not Registered</span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Declaration -->
            @if($registration->declarationDetails)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Declaration</h5>
                </div>
                <div class="card-body">
                    <p><strong>Declared By:</strong> {{ $registration->declarationDetails->declaration_name }}</p>
                    <p><strong>Capacity:</strong> {{ $registration->declarationDetails->declaration_capacity }}</p>
                    <p><strong>Date:</strong> {{ $registration->declarationDetails->declaration_date }}</p>
                    <p><strong>Declaration Accepted:</strong> 
                        <span class="badge bg-success">Yes</span>
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Reject Modal -->
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
                        <textarea name="rejection_reason" class="form-control" rows="5" required></textarea>
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

<script>
function approveRegistration(id) {
    if (confirm('Are you sure you want to approve this registration?')) {
        window.location.href = '{{ route("admin.registrations.approve", "") }}/' + id;
    }
}

function showRejectModal(id) {
    const form = document.getElementById('rejectForm');
    form.action = '{{ route("admin.registrations.reject", "") }}/' + id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endsection