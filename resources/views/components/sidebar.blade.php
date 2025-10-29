<!-- Sidebar Component -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <i class="bi bi-water"></i>
            <span>Catat Meter</span>
        </a>
    </div>

    <div class="sidebar-menu">
        <!-- Dashboard Menu -->
        <div class="sidebar-item">
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->is('dashboard*') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Management Menu Group -->
        <div class="sidebar-divider">
            <small class="sidebar-divider-text">MANAGEMENT</small>
        </div>

        <div class="sidebar-item">
            <a href="#" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Pelanggan">
                <i class="bi bi-people"></i>
                <span>Pelanggan</span>
            </a>
        </div>

        <div class="sidebar-item">
            <a href="#" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right"
                title="Pencatatan Meter">
                <i class="bi bi-speedometer"></i>
                <span>Pencatatan Meter</span>
            </a>
        </div>

        <div class="sidebar-item">
            <a href="#" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right"
                title="Pembayaran">
                <i class="bi bi-credit-card"></i>
                <span>Pembayaran</span>
            </a>
        </div>

        <!-- Reports Menu Group -->
        <div class="sidebar-divider">
            <small class="sidebar-divider-text">REPORTS</small>
        </div>

        <div class="sidebar-item">
            <a href="#" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Laporan">
                <i class="bi bi-file-text"></i>
                <span>Laporan</span>
            </a>
        </div>

        <div class="sidebar-item">
            <a href="#" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Analytics">
                <i class="bi bi-graph-up"></i>
                <span>Analytics</span>
            </a>
        </div>

        <!-- Settings Menu Group -->
        <div class="sidebar-divider">
            <small class="sidebar-divider-text">SETTINGS</small>
        </div>

        <div class="sidebar-item">
            <a href="{{ route('import') }}" class="sidebar-link {{ request()->is('import*') ? 'active' : '' }}" data-bs-toggle="tooltip" data-bs-placement="right"
                title="Import Data">
                <i class="bi bi-cloud-upload"></i>
                <span>Import Data</span>
            </a>
        </div>

        <div class="sidebar-item">
            <a href="{{ route('settings') }}" class="sidebar-link {{ request()->is('settings*') ? 'active' : '' }}" data-bs-toggle="tooltip" data-bs-placement="right"
                title="Pengaturan">
                <i class="bi bi-gear"></i>
                <span>Pengaturan</span>
            </a>
        </div>

        <div class="sidebar-item">
            <a href="#" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right"
                title="System Info">
                <i class="bi bi-info-circle"></i>
                <span>System Info</span>
            </a>
        </div>

        <!-- User Menu -->
        <div class="sidebar-divider">
            <small class="sidebar-divider-text">USER</small>
        </div>

        <div class="sidebar-item">
            <a href="#" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Profile">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
        </div>

        <div class="sidebar-item">
            <a href="{{ route('logout') }}" class="sidebar-link"
                onclick="event.preventDefault(); document.getElementById('logoutForm').submit();"
                data-bs-toggle="tooltip" data-bs-placement="right" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="text-center text-white-50 small p-3">
            <div class="mb-2">
                <i class="bi bi-circle-fill text-success" style="font-size: 0.5rem;"></i>
                System Online
            </div>
            <div>v1.0.0</div>
        </div>
    </div>

    <!-- Hidden Logout Form -->
    <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</div>

<style>
    /* Sidebar Specific Styles */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: var(--sidebar-width);
        background: linear-gradient(135deg, var(--sidebar-bg) 0%, #34495e 100%);
        transition: all 0.3s ease;
        z-index: 1000;
        overflow-y: auto;
        overflow-x: hidden;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }

    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar-header {
        background: rgba(0, 0, 0, 0.1);
        padding: 1.2rem;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        height: var(--topbar-height);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sidebar-brand {
        color: white;
        text-decoration: none;
        font-size: 1.3rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }

    .sidebar-brand i {
        font-size: 1.5rem;
        margin-right: 10px;
        min-width: 30px;
    }

    .sidebar.collapsed .sidebar-brand span {
        display: none;
    }

    .sidebar-menu {
        padding: 1rem 0;
        flex: 1;
    }

    .sidebar-divider {
        padding: 1rem 1.2rem 0.5rem;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sidebar.collapsed .sidebar-divider {
        opacity: 0;
        padding: 0.5rem 0;
    }

    .sidebar-divider-text {
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.1rem;
        text-transform: uppercase;
    }

    .sidebar.collapsed .sidebar-divider-text {
        display: none;
    }

    .sidebar-item {
        margin-bottom: 0.3rem;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 0.8rem 1.2rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
        position: relative;
    }

    .sidebar-link:hover {
        background-color: var(--sidebar-hover);
        color: white;
        border-left-color: var(--primary-color);
    }

    .sidebar-link.active {
        background-color: var(--sidebar-hover);
        color: white;
        border-left-color: var(--primary-color);
    }

    .sidebar-link i {
        font-size: 1.1rem;
        margin-right: 12px;
        min-width: 20px;
        text-align: center;
    }

    .sidebar.collapsed .sidebar-link span {
        display: none;
    }

    .sidebar.collapsed .sidebar-link {
        justify-content: center;
        padding: 0.8rem;
    }

    .sidebar.collapsed .sidebar-link i {
        margin-right: 0;
    }

    .sidebar-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        flex-shrink: 0;
    }

    .sidebar.collapsed .sidebar-footer .small {
        display: none;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.mobile-show {
            transform: translateX(0);
        }

        .sidebar.collapsed {
            width: var(--sidebar-width);
        }

        .sidebar-divider {
            opacity: 1;
        }

        .sidebar.collapsed .sidebar-divider {
            opacity: 1;
        }
    }

    /* Scrollbar for sidebar */
    .sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 2px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
</style>
