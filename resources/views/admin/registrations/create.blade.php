@extends('layouts.app')

@section('title', 'Create Business Registration')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.registrations.index') }}">Registrations</a></li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </div>
                <h4 class="page-title">Create Business Registration</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.registrations.store') }}" id="registrationForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Application Type</label>
                                <select name="application_type" class="form-select @error('application_type') is-invalid @enderror">
                                    <option value="New" {{ old('application_type') == 'New' ? 'selected' : '' }}>New</option>
                                    <option value="Amendment" {{ old('application_type') == 'Amendment' ? 'selected' : '' }}>Amendment</option>
                                    <option value="Renewal" {{ old('application_type') == 'Renewal' ? 'selected' : '' }}>Renewal</option>
                                </select>
                                @error('application_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Type</label>
                                <select name="business_type" class="form-select @error('business_type') is-invalid @enderror" required>
                                    <option value="">Select Business Type</option>
                                    <option value="SOLE_TRADER" {{ old('business_type') == 'SOLE_TRADER' ? 'selected' : '' }}>Sole Trader</option>
                                    <option value="PARTNERSHIP" {{ old('business_type') == 'PARTNERSHIP' ? 'selected' : '' }}>Partnership</option>
                                    <option value="PRIVATE_LIMITED" {{ old('business_type') == 'PRIVATE_LIMITED' ? 'selected' : '' }}>Private Limited Company</option>
                                    <option value="PUBLIC_LIMITED" {{ old('business_type') == 'PUBLIC_LIMITED' ? 'selected' : '' }}>Public Limited Company</option>
                                    <option value="NGO" {{ old('business_type') == 'NGO' ? 'selected' : '' }}>NGO</option>
                                    <option value="TRUST" {{ old('business_type') == 'TRUST' ? 'selected' : '' }}>Trust</option>
                                    <option value="COOPERATIVE" {{ old('business_type') == 'COOPERATIVE' ? 'selected' : '' }}>Cooperative</option>
                                </select>
                                @error('business_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Legal Name <span class="text-danger">*</span></label>
                                <input type="text" name="legal_name" class="form-control @error('legal_name') is-invalid @enderror" 
                                       value="{{ old('legal_name') }}" required>
                                @error('legal_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Registration Number (if any)</label>
                                <input type="text" name="registration_number" class="form-control @error('registration_number') is-invalid @enderror" 
                                       value="{{ old('registration_number') }}">
                                @error('registration_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Old TIN (if any)</label>
                                <input type="text" name="old_tin" class="form-control @error('old_tin') is-invalid @enderror" 
                                       value="{{ old('old_tin') }}">
                                @error('old_tin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New TIN (will be generated)</label>
                                <input type="text" name="new_tin" class="form-control @error('new_tin') is-invalid @enderror" 
                                       value="{{ old('new_tin') }}" readonly>
                                <small class="text-muted">TIN will be assigned after approval</small>
                                @error('new_tin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone') }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Sole Trader Specific Fields -->
                        <div class="row" id="soleTraderFields" style="display: none;">
                            <div class="col-md-12">
                                <h5 class="mt-2 mb-3">Sole Trader Personal Details</h5>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Surname</label>
                                <input type="text" name="surname" class="form-control @error('surname') is-invalid @enderror" 
                                       value="{{ old('surname') }}">
                                @error('surname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Forename</label>
                                <input type="text" name="forename" class="form-control @error('forename') is-invalid @enderror" 
                                       value="{{ old('forename') }}">
                                @error('forename')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Maiden Name</label>
                                <input type="text" name="maiden_name" class="form-control @error('maiden_name') is-invalid @enderror" 
                                       value="{{ old('maiden_name') }}">
                                @error('maiden_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="is_sole_trader" class="form-check-input" id="isSoleTrader" value="1" 
                                           {{ old('is_sole_trader') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isSoleTrader">
                                        This is a Sole Trader registration
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Physical Address <span class="text-danger">*</span></label>
                                <textarea name="physical_address" class="form-control @error('physical_address') is-invalid @enderror" 
                                          rows="3" required>{{ old('physical_address') }}</textarea>
                                @error('physical_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Postal Address</label>
                                <textarea name="postal_address" class="form-control @error('postal_address') is-invalid @enderror" 
                                          rows="2">{{ old('postal_address') }}</textarea>
                                <small class="text-muted">If different from physical address</small>
                                @error('postal_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">City/Town</label>
                                <input type="text" name="city" class="form-control" value="{{ old('city', 'Maseru') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">District</label>
                                <input type="text" name="district" class="form-control" value="{{ old('district', 'Maseru') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" value="{{ old('country', 'Lesotho') }}" readonly>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check"></i> Create Registration
                            </button>
                            <a href="{{ route('admin.registrations.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-cancel"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isSoleTraderCheckbox = document.getElementById('isSoleTrader');
        const soleTraderFields = document.getElementById('soleTraderFields');
        
        function toggleSoleTraderFields() {
            if (isSoleTraderCheckbox.checked) {
                soleTraderFields.style.display = 'flex';
                // Make fields required
                document.querySelector('input[name="surname"]').required = true;
                document.querySelector('input[name="forename"]').required = true;
            } else {
                soleTraderFields.style.display = 'none';
                // Remove required attribute
                document.querySelector('input[name="surname"]').required = false;
                document.querySelector('input[name="forename"]').required = false;
            }
        }
        
        isSoleTraderCheckbox.addEventListener('change', toggleSoleTraderFields);
        toggleSoleTraderFields();
    });
</script>
@endsection