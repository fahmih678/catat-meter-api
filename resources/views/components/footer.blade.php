<!-- Footer Component -->
@if (isset($showFooter) && $showFooter)
<footer class="main-footer">
    <div class="footer-content">
        <div class="row">
            <div class="col-md-6">
                <div class="footer-info">
                    <h6 class="footer-title">Catat Meter API</h6>
                    <p class="footer-description">
                        Sistem Manajemen Meter Air yang modern dan terintegrasi untuk memudahkan pencatatan dan monitoring penggunaan air.
                    </p>
                    <div class="footer-version">
                        <small class="text-muted">Version 1.0.0</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="footer-links">
                    <h6 class="footer-title">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Support</a></li>
                        <li><a href="#">API Reference</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-3">
                <div class="footer-contact">
                    <h6 class="footer-title">System Info</h6>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-circle-fill text-success me-2"></i>System Online</li>
                        <li><i class="bi bi-clock me-2"></i>Server Time: {{ now()->format('H:i:s') }}</li>
                        <li><i class="bi bi-calendar me-2"></i>{{ now()->format('d M Y') }}</li>
                        <li><i class="bi bi-speedometer2 me-2"></i>Response: 12ms</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="copyright">
                    <small class="text-muted">
                        © {{ date('Y') }} Catat Meter API. All rights reserved.
                    </small>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="footer-actions">
                    <button class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <button class="btn btn-sm btn-outline-secondary me-2" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleFullscreen()">
                        <i class="bi bi-fullscreen me-1"></i>Fullscreen
                    </button>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* Footer Specific Styles */
.main-footer {
    background: white;
    border-top: 1px solid #e9ecef;
    margin-top: auto;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
}

.footer-content {
    padding: 2rem 2rem 1rem;
}

.footer-bottom {
    padding: 1rem 2rem;
    border-top: 1px solid #f8f9fa;
    background: #f8f9fa;
}

.footer-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
}

.footer-description {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.footer-version {
    margin-top: 1rem;
}

.footer-links ul,
.footer-contact ul {
    margin: 0;
    padding: 0;
}

.footer-links li,
.footer-contact li {
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}

.footer-links a {
    color: #6c757d;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: var(--primary-color);
}

.footer-contact li {
    color: #6c757d;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
}

.copyright {
    font-size: 0.8rem;
}

.footer-actions .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .footer-content {
        padding: 1.5rem 1rem 0.75rem;
    }

    .footer-bottom {
        padding: 0.75rem 1rem;
    }

    .footer-title {
        margin-bottom: 0.75rem;
    }

    .footer-links,
    .footer-contact {
        margin-bottom: 1.5rem;
    }

    .footer-actions {
        margin-top: 1rem;
        text-align: center !important;
    }

    .footer-actions .btn {
        margin: 0.25rem;
    }
}

@media (max-width: 576px) {
    .footer-content {
        padding: 1rem 0.5rem 0.5rem;
    }

    .footer-bottom {
        padding: 0.5rem;
    }

    .footer-actions .btn {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
}
</style>

<script>
// Footer specific functionality
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

// Update server time in footer
function updateFooterTime() {
    const timeElement = document.querySelector('.footer-contact .bi-clock').parentElement;
    if (timeElement) {
        const parts = timeElement.innerHTML.split(': ');
        if (parts.length === 2) {
            parts[1] = new Date().toLocaleTimeString();
            timeElement.innerHTML = parts.join(': ');
        }
    }
}

// Update time every second
setInterval(updateFooterTime, 1000);

// System status monitoring
function checkSystemStatus() {
    // Simulate system status check
    const statusIndicator = document.querySelector('.footer-contact .bi-circle-fill');
    if (statusIndicator) {
        // Randomly change status for demo (in real app, this would check actual system status)
        const isOnline = Math.random() > 0.05; // 95% uptime
        statusIndicator.className = isOnline ?
            'bi bi-circle-fill text-success me-2' :
            'bi bi-circle-fill text-danger me-2';
    }
}

// Check status every 30 seconds
setInterval(checkSystemStatus, 30000);
</script>
@endif