@php
    use Illuminate\Support\Str;
@endphp

@extends('layouts.main')

@section('title', 'Pelanggan - ' . ($pam->name ?? 'PAM Not Found'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pam.index') }}">PAM Management</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pam.show', $pam->id) }}">{{ $pam->name }}</a></li>
    <li class="breadcrumb-item active">Pelanggan</li>
@endsection

@section('content')
    <div class="container-fluid p-0">
        <!-- Include Customer Header -->
        @include('dashboard.pam.partials.customer-header')

        <!-- Include Search and Filters -->
        @include('dashboard.pam.partials.customer-filters')

        <!-- Include Customers Table -->
        <div class="row">
            <div class="col-12">
                @include('dashboard.pam.partials.customer-table')
            </div>
        </div>
    </div>

    <!-- Include Customer Modals -->
    @include('dashboard.pam.partials.customer-modals')
@endsection

<!-- Include Customer Styles -->
@push('styles')
    @include('dashboard.pam.partials.customer-styles')
@endpush

<!-- Include Customer JavaScript -->
@push('scripts')
    <script>
        // Initialize PAM ID and customer management when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize PAM ID first
            if (typeof initializePamId === 'function') {
                initializePamId({{ $pam->id }});
                console.log('PAM ID initialized:', {{ $pam->id }});
            }

            // Initialize customer management if not already initialized
            if (typeof initializeCustomerManagement === 'function') {
                // Check if already initialized to avoid duplicate initialization
                if (!window.customerManagementInitialized) {
                    initializeCustomerManagement();
                    window.customerManagementInitialized = true;
                }
            }
        });
    </script>
    <script src="{{ asset('js/customer-management.js') }}"></script>
@endpush
