@extends('layouts.app')

@section('title', 'Manage User')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </div>
            <h4 class="page-title">Manage User</h4>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">SSO Profile</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">{{ $user->name }}</dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $user->email }}</dd>
                    <dt class="col-sm-4">Username</dt>
                    <dd class="col-sm-8">{{ $user->wso2_username ?? $user->username ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">Department</dt>
                    <dd class="col-sm-8">{{ $user->department ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">WSO2 ID</dt>
                    <dd class="col-sm-8 text-break">{{ $user->wso2_id ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Assign Roles</h5>
                <form method="POST" action="{{ route('admin.users.assign-roles', $user) }}">
                    @csrf
                    <div class="row">
                        @foreach($roles as $role)
                            <div class="col-md-6 mb-2">
                                <label class="form-check border rounded p-2 d-block">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $userRoles))>
                                    <span class="fw-semibold">{{ $role->display_name }}</span>
                                    <small class="text-muted d-block ps-4">{{ $role->description }}</small>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Save Roles</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Account Access</h5>
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <input type="hidden" name="email" value="{{ $user->email }}">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked($user->is_active)>
                            <label class="form-check-label" for="is_active">User can sign in</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-primary">Update Access</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
