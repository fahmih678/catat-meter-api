<!-- Topbar Component -->
<div class="topbar">
    <div class="topbar-left">
        <!-- Sidebar Toggle -->
        <button class="sidebar-toggle" id="sidebarToggle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Toggle Sidebar">
            <i class="bi bi-list"></i>
        </button>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">
                        <i class="bi bi-house-door me-1"></i>Home
                    </a>
                </li>
                @yield('breadcrumb')
            </ol>
        </nav>
    </div>

    <div class="topbar-right">
        <!-- Search Bar (Desktop) -->
        <div class="search-box d-none d-lg-flex">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Search..." id="globalSearch">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>

        <!-- Notifications -->
        <div class="dropdown">
            <button class="notification-btn" data-bs-toggle="dropdown" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notification-badge">3</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                <li class="dropdown-header">Notifications</li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item notification-item" href="#">
                        <div class="d-flex align-items-center">
                            <div class="notification-icon bg-primary">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <h6 class="mb-1">New Customer Added</h6>
                                <small class="text-muted">2 minutes ago</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item notification-item" href="#">
                        <div class="d-flex align-items-center">
                            <div class="notification-icon bg-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <h6 class="mb-1">Payment Received</h6>
                                <small class="text-muted">1 hour ago</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item notification-item" href="#">
                        <div class="d-flex align-items-center">
                            <div class="notification-icon bg-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <h6 class="mb-1">System Update Available</h6>
                                <small class="text-muted">3 hours ago</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-center" href="#">
                        <small>View all notifications</small>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Settings (Mobile) -->
        <button class="mobile-settings-btn d-lg-none" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Settings">
            <i class="bi bi-gear"></i>
        </button>

        <!-- User Menu -->
        <div class="user-menu dropdown-toggle" data-bs-toggle="dropdown">
            <div class="user-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="user-info d-none d-md-block">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-email">{{ auth()->user()->email }}</div>
            </div>
            <i class="bi bi-chevron-down dropdown-arrow"></i>
        </div>

        <!-- User Dropdown Menu -->
        <ul class="dropdown-menu dropdown-menu-end user-dropdown">
            <li class="dropdown-header">
                <div class="d-flex align-items-center">
                    <div class="user-avatar me-3">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="fw-semibold">{{ auth()->user()->name }}</div>
                        <small class="text-muted">{{ auth()->user()->email }}</small>
                    </div>
                </div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="#">
                    <i class="bi bi-person me-2"></i>
                    <span>Profile</span>
                    <small class="text-muted float-end">Ctrl+P</small>
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="#">
                    <i class="bi bi-gear me-2"></i>
                    <span>Settings</span>
                    <small class="text-muted float-end">Ctrl+S</small>
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="#">
                    <i class="bi bi-shield-check me-2"></i>
                    <span>Security</span>
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="#">
                    <i class="bi bi-question-circle me-2"></i>
                    <span>Help & Support</span>
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="#">
                    <i class="bi bi-moon me-2"></i>
                    <span>Dark Mode</span>
                    <div class="form-check form-switch ms-auto">
                        <input class="form-check-input" type="checkbox" id="darkModeToggle">
                    </div>
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form id="topbarLogoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
                <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); document.getElementById('topbarLogoutForm').submit();">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
/* Topbar Specific Styles */
.topbar {
    background: white;
    height: var(--topbar-height);
    padding: 0 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: sticky;
    top: 0;
    z-index: 999;
    border-bottom: 1px solid #e9ecef;
}

.topbar-left {
    display: flex;
    align-items: center;
    flex: 1;
}

.sidebar-toggle {
    background: none;
    border: none;
    font-size: 1.3rem;
    color: var(--sidebar-bg);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    margin-right: 1rem;
}

.sidebar-toggle:hover {
    background-color: #f8f9fa;
    color: var(--primary-color);
}

.breadcrumb {
    margin: 0;
    background: transparent;
    padding: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: ">";
    color: #6c757d;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Search Box */
.search-box .input-group {
    width: 300px;
}

.search-box .form-control {
    border-radius: 20px 0 0 20px;
    border-right: none;
}

.search-box .btn {
    border-radius: 0 20px 20px 0;
    border-left: none;
}

/* Notifications */
.notification-btn {
    position: relative;
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #6c757d;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.notification-btn:hover {
    background-color: #f8f9fa;
    color: var(--primary-color);
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    font-size: 0.7rem;
    padding: 2px 5px;
    min-width: 18px;
    text-align: center;
}

.notification-dropdown {
    width: 350px;
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f8f9fa;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-icon {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
}

/* Mobile Settings Button */
.mobile-settings-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #6c757d;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.mobile-settings-btn:hover {
    background-color: #f8f9fa;
    color: var(--primary-color);
}

/* User Menu */
.user-menu {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.5rem 1rem;
    background: #f8f9fa;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.user-menu:hover {
    background: #e9ecef;
    border-color: var(--primary-color);
}

.user-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.user-info {
    line-height: 1.2;
}

.user-name {
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.user-email {
    color: #6c757d;
    font-size: 0.8rem;
}

.dropdown-arrow {
    font-size: 0.8rem;
    color: #6c757d;
    transition: transform 0.3s ease;
}

.user-menu:hover .dropdown-arrow {
    transform: rotate(180deg);
}

.user-dropdown {
    width: 280px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .topbar {
        padding: 0 1rem;
    }

    .search-box {
        display: none;
    }

    .notification-btn,
    .mobile-settings-btn {
        font-size: 1.1rem;
    }

    .user-info {
        display: none;
    }

    .user-dropdown {
        width: 250px;
    }
}

@media (max-width: 576px) {
    .topbar {
        padding: 0 0.5rem;
    }

    .sidebar-toggle {
        margin-right: 0.5rem;
        padding: 0.3rem;
    }

    .user-menu {
        padding: 0.5rem 0.8rem;
    }
}
</style>

<script>
// Topbar specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Global search functionality
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                // Implement search functionality
                console.log('Searching for:', this.value);
                // Placeholder for search implementation
            }
        });
    }

    // Mark notifications as read
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            // Mark as read logic here
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 0) {
                    badge.textContent = currentCount - 1;
                    if (currentCount - 1 === 0) {
                        badge.style.display = 'none';
                    }
                }
            }
        });
    });

    // Dark mode toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });

        // Check for saved dark mode preference
        const savedDarkMode = localStorage.getItem('darkMode');
        if (savedDarkMode === 'enabled') {
            darkModeToggle.checked = true;
            document.body.classList.add('dark-mode');
        }
    }
});
</script>