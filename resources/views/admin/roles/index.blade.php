@extends('layouts.app')

@section('title', 'Role Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Role Management</li>
                    </ol>
                </div>
                <h4 class="page-title">Role Management</h4>
            </div>
        </div>
    </div>

    @can('roles.create')
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="mdi mdi-plus"></i> Create New Role
            </a>
        </div>
    </div>
    @endcan

    <div class="row">
        @foreach($roles as $role)
        <div class="col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title">{{ $role->display_name }}</h5>
                            <p class="text-muted">{{ $role->description }}</p>
                        </div>
                        <span class="badge bg-info">{{ $role->users->count() }} Users</span>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Permissions by Module:</h6>
                        @php
                            $groupedPerms = $role->permissions->groupBy('module');
                        @endphp
                        @foreach($groupedPerms as $module => $perms)
                            <div class="mb-2">
                                <strong class="text-capitalize">{{ $module }}:</strong>
                                <div class="mt-1">
                                    @foreach($perms as $perm)
                                        <span class="badge bg-soft-primary me-1 mb-1">
                                            {{ $perm->display_name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        @can('roles.edit')
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-info">
                            <i class="mdi mdi-pencil"></i> Edit
                        </a>
                        @endcan
                        
                        <a href="{{ route('admin.roles.users', $role) }}" class="btn btn-sm btn-secondary">
                            <i class="mdi mdi-account-group"></i> Users
                        </a>
                        
                        @can('roles.delete')
                        @if($role->name !== 'admin')
                        <button class="btn btn-sm btn-danger" onclick="deleteRole({{ $role->id }})">
                            <i class="mdi mdi-delete"></i> Delete
                        </button>
                        @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function deleteRole(roleId) {
    if (confirm('Are you sure you want to delete this role?')) {
        let form = document.getElementById('delete-form');
        form.action = '/admin/roles/' + roleId;
        form.submit();
    }
}
</script>
@endsection