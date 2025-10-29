@extends('layouts.main')

@section('title', 'Profile')

@section('breadcrumb')
    <li class="breadcrumb-item active">Profile</li>
@endsection

@section('content')
<div class="container-fluid p-0">
    <!-- Profile Header -->
    <div class="row mb-4 fade-in-up">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="profile-avatar-lg">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-1">{{ auth()->user()->name }}</h2>
                        <p class="text-muted mb-0">{{ auth()->user()->email }}</p>
                        <div class="mt-2">
                            <span class="badge bg-primary me-2">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</span>
                            <span class="badge bg-success">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                Active
                            </span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary">
                            <i class="bi bi-pencil-square me-2"></i>Edit Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4 fade-in-up" style="animation-delay: 0.1s;">
            <div class="dashboard-card">
                <h5 class="card-title mb-4">
                    <i class="bi bi-person me-2 text-primary"></i>
                    Profile Information
                </h5>

                <form>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" value="{{ auth()->user()->name }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" value="">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" value="{{ auth()->user()->email }}">
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" placeholder="+62 812-3456-7890">
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" rows="3" placeholder="Tell us about yourself..."></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                        <button type="button" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-2"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4 mb-4 fade-in-up" style="animation-delay: 0.2s;">
            <div class="dashboard-card">
                <h5 class="card-title mb-4">
                    <i class="bi bi-shield-check me-2 text-success"></i>
                    Security Settings
                </h5>

                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary text-start">
                        <i class="bi bi-key me-2"></i>
                        Change Password
                    </button>
                    <button class="btn btn-outline-primary text-start">
                        <i class="bi bi-phone me-2"></i>
                        Two-Factor Authentication
                    </button>
                    <button class="btn btn-outline-primary text-start">
                        <i class="bi bi-laptop me-2"></i>
                        Active Sessions
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Statistics -->
    <div class="row mb-4">
        <div class="col-12 fade-in-up" style="animation-delay: 0.3s;">
            <div class="dashboard-card">
                <h5 class="card-title mb-4">
                    <i class="bi bi-graph-up me-2 text-info"></i>
                    Activity Statistics
                </h5>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-primary mb-1">156</h3>
                            <small class="text-muted">Total Login</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-success mb-1">12</h3>
                            <small class="text-muted">This Month</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-info mb-1">3</h3>
                            <small class="text-muted">This Week</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-warning mb-1">1</h3>
                            <small class="text-muted">Today</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.profile-avatar-lg {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 2rem;
}
</style>
@endpush