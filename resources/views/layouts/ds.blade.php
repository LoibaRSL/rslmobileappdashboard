{{-- resources/views/layouts/ds.blade.php --}}
@extends('layouts.scrollable') {{-- Your Ubold layout --}}

@section('content')
<div class="container-fluid">
    <!-- Page Title -->
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

    <!-- Main Content -->
    @yield('ds-content')
</div>
@endsection

@push('styles')
<link href="{{ asset('assets/libs/datatables/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@stack('ds-styles')
@endpush

@push('scripts')
<script src="{{ asset('assets/libs/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/libs/datatables/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
@stack('ds-scripts')
@endpush