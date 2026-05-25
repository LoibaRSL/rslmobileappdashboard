@extends("shared.base", ["title" => "Digital Services"])

@section("html_attribute")
    data-layout-position="scrollable"
@endsection

@section('content')
<div class="wrapper">
    @include("shared.partials.topbar")
    @include("shared.partials.sidenav")

    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('ds.dashboard') }}">Digital Services</a></li>
                                @yield('breadcrumbs')
                            </ol>
                        </div>
                        <h4 class="page-title">@yield('page-title')</h4>
                    </div>
                </div>
            </div>

            @yield('ds-content')
        </div>

        @include("shared.partials.footer")
    </div>
</div>

@include("shared.partials.customizer")
@include("shared.partials.footer-scripts")
@endsection

@section('styles')
<link href="{{ asset('assets/libs/datatables/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<style>
.ux-skeleton {
    animation: uxPulse 1.2s ease-in-out infinite;
    background: linear-gradient(90deg, #eef2f7 25%, #f8fafc 45%, #eef2f7 65%);
    background-size: 240% 100%;
    border-radius: 6px;
    display: block;
    height: 16px;
    width: min(100%, 140px);
}
.ux-skeleton-row td {
    padding-block: 1rem;
}
.ux-empty-state {
    align-items: center;
    color: #64748b;
    display: flex;
    flex-direction: column;
    gap: 6px;
    justify-content: center;
    min-height: 150px;
    text-align: center;
}
.ux-empty-state h6 {
    color: #334155;
    margin: 4px 0 0;
}
.ux-empty-state p {
    margin: 0;
    max-width: 420px;
}
.ux-empty-icon {
    align-items: center;
    background: #eef6ff;
    border-radius: 50%;
    color: #2563eb;
    display: inline-flex;
    font-size: 28px;
    height: 56px;
    justify-content: center;
    width: 56px;
}
.white-space-pre-line {
    white-space: pre-line;
}
@keyframes uxPulse {
    0% { background-position: 120% 0; }
    100% { background-position: -120% 0; }
}
</style>
@stack('ds-styles')
@endsection

@section('scripts')
<script src="{{ asset('assets/libs/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/libs/datatables/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
window.AppUX = {
    toast(message, icon = 'success') {
        if (!window.Swal) {
            return window.alert(message);
        }

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon,
            title: message,
            showConfirmButton: false,
            timer: icon === 'error' ? 4500 : 2800,
            timerProgressBar: true
        });
    },
    async confirm(message, title = 'Please confirm') {
        if (!window.Swal) {
            return window.confirm(message);
        }

        const result = await Swal.fire({
            title,
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Continue',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        });

        return result.isConfirmed;
    },
    skeletonRows(columns, rows = 5) {
        return Array.from({ length: rows }).map(() => `
            <tr class="ux-skeleton-row">
                ${Array.from({ length: columns }).map(() => '<td><span class="ux-skeleton"></span></td>').join('')}
            </tr>
        `).join('');
    },
    emptyState(columns, icon, title, text) {
        return `
            <tr>
                <td colspan="${columns}" class="py-5">
                    <div class="ux-empty-state">
                        <div class="ux-empty-icon"><i class="mdi ${icon}"></i></div>
                        <h6>${title}</h6>
                        <p>${text}</p>
                    </div>
                </td>
            </tr>
        `;
    }
};
</script>
@stack('ds-scripts')
@endsection
