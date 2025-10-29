@extends('layouts.main')

@section('title', 'Settings')

@section('breadcrumb')
    <li class="breadcrumb-item active">Settings</li>
@endsection

@section('content')
<div class="container-fluid p-0">
    <!-- Settings Header -->
    <div class="row mb-4 fade-in-up">
        <div class="col-12">
            <div class="dashboard-card">
                <h2 class="mb-2">
                    <i class="bi bi-gear me-2 text-primary"></i>
                    Application Settings
                </h2>
                <p class="text-muted mb-0">Manage system preferences and configurations</p>
            </div>
        </div>
    </div>

    <!-- Settings Tabs -->
    <div class="row mb-4">
        <div class="col-12 fade-in-up" style="animation-delay: 0.1s;">
            <div class="dashboard-card">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                            <i class="bi bi-gear me-2"></i>General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                            <i class="bi bi-shield-check me-2"></i>Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                            <i class="bi bi-bell me-2"></i>Notifications
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab">
                            <i class="bi bi-palette me-2"></i>Appearance
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">
                            <i class="bi bi-cloud-download me-2"></i>Backup
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="settingsTabContent">
                    <!-- General Settings -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <form class="mt-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Application Name</label>
                                    <input type="text" class="form-control" value="Catat Meter API">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Default Language</label>
                                    <select class="form-select">
                                        <option>Indonesian</option>
                                        <option>English</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Timezone</label>
                                    <select class="form-select">
                                        <option>Asia/Jakarta (WIB)</option>
                                        <option>Asia/Makassar (WITA)</option>
                                        <option>Asia/Jayapura (WIT)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date Format</label>
                                    <select class="form-select">
                                        <option>DD/MM/YYYY</option>
                                        <option>MM/DD/YYYY</option>
                                        <option>YYYY-MM-DD</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="maintenance" checked>
                                    <label class="form-check-label" for="maintenance">
                                        Enable Maintenance Mode
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>Save Settings
                            </button>
                        </form>
                    </div>

                    <!-- Security Settings -->
                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <div class="mt-4">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <h6>Password Requirements</h6>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="minLength" checked>
                                            <label class="form-check-label" for="minLength">
                                                Minimum 8 characters
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="uppercase" checked>
                                            <label class="form-check-label" for="uppercase">
                                                Uppercase letters
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="numbers" checked>
                                            <label class="form-check-label" for="numbers">
                                                Numbers
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <h6>Session Settings</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Session Timeout (minutes)</label>
                                        <input type="number" class="form-control" value="30">
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="rememberMe" checked>
                                        <label class="form-check-label" for="rememberMe">
                                            Enable "Remember Me"
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Security settings will be applied to all users on next login.
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Settings -->
                    <div class="tab-pane fade" id="notifications" role="tabpanel">
                        <div class="mt-4">
                            <h6 class="mb-3">Email Notifications</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="emailLogin" checked>
                                        <label class="form-check-label" for="emailLogin">
                                            Login notifications
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="emailPayment" checked>
                                        <label class="form-check-label" for="emailPayment">
                                            Payment notifications
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="emailSystem">
                                        <label class="form-check-label" for="emailSystem">
                                            System notifications
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="emailBackup" checked>
                                        <label class="form-check-label" for="emailBackup">
                                            Backup notifications
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="emailMaintenance" checked>
                                        <label class="form-check-label" for="emailMaintenance">
                                            Maintenance notifications
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Appearance Settings -->
                    <div class="tab-pane fade" id="appearance" role="tabpanel">
                        <div class="mt-4">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <h6>Theme</h6>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="theme" id="light" checked>
                                            <label class="form-check-label" for="light">
                                                Light Theme
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="theme" id="dark">
                                            <label class="form-check-label" for="dark">
                                                Dark Theme
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="theme" id="auto">
                                            <label class="form-check-label" for="auto">
                                                Auto (System)
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <h6>Accent Color</h6>
                                    <div class="d-flex gap-2 mb-3">
                                        <button type="button" class="btn btn-primary" style="background: #667eea;">Primary</button>
                                        <button type="button" class="btn btn-success" style="background: #28a745;">Success</button>
                                        <button type="button" class="btn btn-info" style="background: #17a2b8;">Info</button>
                                        <button type="button" class="btn btn-warning" style="background: #ffc107;">Warning</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Settings -->
                    <div class="tab-pane fade" id="backup" role="tabpanel">
                        <div class="mt-4">
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <h6>Automatic Backup</h6>
                                    <p class="text-muted">Configure automatic database backups</p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="autoBackup" checked>
                                        <label class="form-check-label" for="autoBackup">
                                            Enable
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Backup Frequency</label>
                                    <select class="form-select">
                                        <option>Daily</option>
                                        <option>Weekly</option>
                                        <option>Monthly</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Retention Period</label>
                                    <select class="form-select">
                                        <option>7 days</option>
                                        <option>30 days</option>
                                        <option>90 days</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-primary">
                                    <i class="bi bi-cloud-download me-2"></i>Backup Now
                                </button>
                                <button class="btn btn-outline-secondary">
                                    <i class="bi bi-clock-history me-2"></i>View History
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection