@extends("shared.base", ["title" => "Unassigned Registrations"])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                        <li class="breadcrumb-item active">Unassigned Registrations</li>
                    </ol>
                </div>
                <h4 class="page-title">Unassigned Registrations</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0" id="unassigned-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Reference</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Submitted Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
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

<script>
$(document).ready(function() {
    var table = $('#unassigned-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("ds.api.registrations.unassigned") }}',
            dataSrc: 'data.data'
        },
        columns: [
            { data: 'id' },
            { data: 'ref' },
            { data: 'full_name' },
            { data: 'email' },
            { data: 'submitted_at' },
            {
                data: null,
                render: function(data) {
                    return `<button class="btn btn-sm btn-primary assign-btn" data-id="${data.id}" data-type="${data.registration_kind}">
                                <i class="mdi mdi-account-plus"></i> Assign to Me
                            </button>`;
                }
            }
        ],
        order: [[4, 'asc']],
        pageLength: 25
    });
    
    $(document).on('click', '.assign-btn', function() {
        var id = $(this).data('id');
        var type = $(this).data('type') || 'individual';
        var btn = $(this);
        var baseUrl = type === 'business' ? '{{ url('api/ds/business-registrations') }}' : '{{ url('api/ds/registrations') }}';
        
        if (confirm('Assign this registration to yourself?')) {
            btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Assigning...');
            
            $.post(`${baseUrl}/${id}/assign-to-self`)
                .done(function(response) {
                    alert(response.message || 'Registration assigned successfully');
                    table.ajax.reload();
                })
                .fail(function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Failed to assign'));
                    btn.prop('disabled', false).html('<i class="mdi mdi-account-plus"></i> Assign to Me');
                });
        }
    });
});
</script>
@endsection
