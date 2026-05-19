@extends('layouts.app')

@section('title', 'Edit Business Registration')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.registrations.index') }}">Registrations</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
                <h4 class="page-title">Edit Business Registration: {{ $registration->reference_number }}</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.registrations.update', $registration->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" value="{{ $registration->reference_number }}" readonly disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="pending" {{ $registration->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $registration->status == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ $registration->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Application Type</label>
                                <select name="application_type" class="form-select @error('application_type') is-invalid @enderror">
                                    <option value="New" {{ old('application_type', $registration->application_type) == 'New' ? 'selected' : '' }}>New</option>
                                    <option value="Amendment" {{ old('application_type', $registration->application_type) == 'Amendment' ? 'selected' : '' }}>Amendment</option>
                                    <option value="Renewal" {{ old('application_type', $registration->application_type) == 'Renewal' ? 'selected' : '' }}>Renewal</option>
                                </select>
                                @error('application_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Type</label>
                                <select name="business_type" class="form-select @error('business_type') is-invalid @enderror" required>
                                    <option value="SOLE_TRADER" {{ old('business_type', $registration->business_type) == 'SOLE_TRADER' ? 'selected' : '' }}>Sole Trader</option>
                                    <option value="PARTNERSHIP" {{ old('business_type', $registration->business_type) == 'PARTNERSHIP' ? 'selected' : '' }}>Partnership</option>
                                    <option value="PRIVATE_LIMITED" {{ old('business_type', $registration->business_type) == 'PRIVATE_LIMITED' ? 'selected' : '' }}>Private Limited Company</option>
                                    <option value="PUBLIC_LIMITED" {{ old('business_type', $registration->business_type) == 'PUBLIC_LIMITED' ? 'selected' : '' }}>Public Limited Company</option>
                                    <option value="NGO" {{ old('business_type', $registration->business_type) == 'NGO' ? 'selected' : '' }}>NGO</option>
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
                                       value="{{ old('legal_name', $registration->legal_name) }}" required>
                                @error('legal_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Registration Number</label>
                                <input type="text" name="registration_number" class="form-control @error('registration_number') is-invalid @enderror" 
                                       value="{{ old('registration_number', $registration->registration_number) }}">
                                @error('registration_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $registration->email) }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone', $registration->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Physical Address</label>
                                <textarea name="physical_address" class="form-control @error('physical_address') is-invalid @enderror" 
                                          rows="3">{{ old('physical_address', $registration->contactDetails->physical_address ?? '') }}</textarea>
                                @error('physical_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Registration</button>
                            <a href="{{ route('admin.registrations.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection