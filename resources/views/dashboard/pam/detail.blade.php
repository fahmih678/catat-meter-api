@extends('layouts.pam')

@section('title', 'PAM Detail - ' . ($pam->name ?? 'PAM Not Found'))

@section('breadcrumb')
    <li class="breadcrumb-item active"><i class="bi bi-building me-1"></i>{{ $pam->name ?? 'PAM Not Found' }}</li>
@endsection

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <!-- PAM Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="d-flex align-items-center mb-2">
                                <div
                                    class="avatar avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <h2 class="mb-1">{{ $pam->name }}</h2>
                                    <p class="text-muted mb-0">{{ $pam->code }} | {{ $pam->address }}</p>
                                    @if ($pam->email || $pam->phone)
                                        <p class="text-muted mb-0 small">
                                            @if ($pam->email)
                                                {{ $pam->email }}
                                            @endif
                                            @if ($pam->email && $pam->phone)
                                                |
                                            @endif
                                            @if ($pam->phone)
                                                {{ $pam->phone }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Management Tabs -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs nav-tabs-custom" id="pamTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab"
                                data-bs-target="#overview" type="button" role="tab">
                                <i class="bi bi-grid me-2"></i>Overview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="areas-tab" data-bs-toggle="tab" data-bs-target="#areas"
                                type="button" role="tab">
                                <i class="bi bi-geo-alt me-2"></i>Areas
                                <span class="badge bg-primary ms-1">{{ $areas->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tariffs-tab" data-bs-toggle="tab" data-bs-target="#tariffs"
                                type="button" role="tab">
                                <i class="bi bi-tags me-2"></i>Tariff Groups
                                <span class="badge bg-warning ms-1">{{ $tariffGroups->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tiers-tab" data-bs-toggle="tab" data-bs-target="#tiers"
                                type="button" role="tab">
                                <i class="bi bi-layers me-2"></i>Tariff Tiers
                                <span class="badge bg-info ms-1">{{ $tariffTiers->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees"
                                type="button" role="tab">
                                <i class="bi bi-cash-stack me-2"></i>Fixed Fees
                                <span class="badge bg-success ms-1">{{ $fixedFees->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content p-4" id="pamTabsContent">
                        <!-- Overview Tab -->
                        @include('dashboard.pam.partials.detail-overview')

                        <!-- Areas Tab -->
                        @include('dashboard.pam.partials.detail-area')

                        <!-- Tariff Groups Tab -->
                        @include('dashboard.pam.partials.detail-tariff-group')

                        <!-- Tariff Tiers Tab -->
                        @include('dashboard.pam.partials.detail-tariff-tier')

                        <!-- Fixed Fees Tab -->
                        @include('dashboard.pam.partials.detail-fixed-fee')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tariff Group Modals -->
    @include('dashboard.pam.partials.detail-modal.tariff-group')

    <!-- Area Modals -->
    @include('dashboard.pam.partials.detail-modal.area')

    <!-- Fixed Fee Modals -->
    @include('dashboard.pam.partials.detail-modal.fixed-fee')

    <!-- Tariff Tier Modals -->
    @include('dashboard.pam.partials.detail-modal.tariff-tier')
@endsection

@push('styles')
    <style>
        .nav-tabs-custom {
            border-bottom: 2px solid #e9ecef;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #6c757d;
            padding: 1rem 1.5rem;
            border-radius: 10px 10px 0 0;
            margin-right: 0.25rem;
            transition: all 0.3s ease;
        }

        .nav-tabs-custom .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(0, 123, 255, 0.05);
        }

        .nav-tabs-custom .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(0, 123, 255, 0.1);
            border-bottom: 3px solid var(--primary-color);
            font-weight: 600;
        }

        .stat-card-success,
        .stat-card-warning,
        .stat-card-info,
        .stat-card-primary {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card-success:hover,
        .stat-card-warning:hover,
        .stat-card-info:hover,
        .stat-card-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@push('scripts')
    <!-- Load JavaScript files in correct order -->
    <script src="{{ asset('js/pam-utils.js') }}"></script>
    <script src="{{ asset('js/pam-modals.js') }}"></script>
    <script src="{{ asset('js/pam-tabs.js') }}"></script>
    <script src="{{ asset('js/pam-areas.js') }}"></script>
    <script src="{{ asset('js/pam-tariff-groups.js') }}"></script>
    <script src="{{ asset('js/pam-fixed-fees.js') }}"></script>
    <script src="{{ asset('js/pam-tariff-tiers.js') }}"></script>

    <!-- Make PAM ID available to JavaScript -->
    <script>
        window.pamId = {{ $pam->id }};
    </script>

    <!-- Initialize main page controller -->
    <script src="{{ asset('js/pam-detail.js') }}"></script>
@endpush
