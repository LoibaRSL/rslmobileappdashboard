@extends("shared.base", ["title" => "Rejected Registrations"])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('ds.dashboard') }}">Digital Services</a></li>
                        <li class="breadcrumb-item active">Rejected Registrations</li>
                    </ol>
                </div>
                <h4 class="page-title">Rejected TIN Registrations</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0" id="rejected-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Reference</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Rejected Date</th>
                                    <th>Remarks</th>
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

<script>
$(document).ready(function() {
    $('#rejected-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("ds.api.registrations.rejected") }}',
            dataSrc: 'data.data'
        },
        columns: [
            { data: 'id' },
            { data: 'ref' },
            { data: 'full_name' },
            { data: 'email' },
            { data: 'submitted_at' },
            { data: 'remarks', defaultContent: 'N/A' },
            {
                data: null,
                render: function(data) {
                    return `<button class="btn btn-sm btn-info view-btn" data-id="${data.id}">
                                <i class="mdi mdi-eye"></i> View
                            </button>`;
                }
            }
        ],
        order: [[4, 'desc']],
        pageLength: 25
    });
    
    $(document).on('click', '.view-btn', function() {
        let id = $(this).data('id');
        window.location.href = `{{ url('ds/registrations') }}/${id}`;
    });
});
</script>
@endsection