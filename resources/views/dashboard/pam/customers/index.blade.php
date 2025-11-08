@php
    use Illuminate\Support\Str;
@endphp

@extends('layouts.pam')

@section('title', 'Pelanggan - ' . ($pam->name ?? 'PAM Not Found'))

<!-- Breadcrumb -->
@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('pam.show', $pam->id) }}" class="text-decoration-none">
            <i class="bi bi-building me-1"></i>{{ $pam->name }}
        </a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">
        <i class="bi bi-people me-1"></i>Daftar Pelanggan
    </li>
@endsection
@section('content')
    <div class="container-fluid p-0">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">
                                <i class="bi bi-people text-primary me-2"></i>
                                Daftar Pelanggan
                            </h3>
                            <p class="text-muted mb-0">PAM: {{ $pam->name }} ({{ $pam->code }})</p>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('pam.show', $pam->id) }}" class="btn btn-primary">
                                <i class="bi bi-building me-1"></i>Detail PAM
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

{{-- Customer JavaScript is loaded from the included partials --}}
