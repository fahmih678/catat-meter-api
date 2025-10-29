<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Catat Meter API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 90%;
            margin: 20px;
        }
        .login-row {
            min-height: 500px;
        }
        .login-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        .login-content {
            position: relative;
            z-index: 1;
        }
        .login-right {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-floating {
            margin-bottom: 1.5rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .alert {
            border-radius: 10px;
            border: none;
            font-weight: 500;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .logo-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        .title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        .feature-list i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        @media (max-width: 768px) {
            .login-left {
                padding: 2rem;
                min-height: auto;
            }
            .login-right {
                padding: 2rem;
            }
            .title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="row g-0 login-row">
                <div class="col-lg-6 login-left">
                    <div class="login-content">
                        <div class="text-center mb-4">
                            <i class="bi bi-water logo-icon"></i>
                            <h1 class="title">Catat Meter</h1>
                            <p class="subtitle">Sistem Manajemen Meter Air</p>
                        </div>

                        <ul class="feature-list">
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Manajemen Data Pelanggan</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Pencatatan Meter Digital</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Laporan Pembayaran</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Dashboard Real-time</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-6 login-right">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-dark">Selamat Datang</h2>
                        <p class="text-muted">Silakan login untuk melanjutkan</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            @foreach ($errors->all() as $error)
                                {{ $error }}<br>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="/login">
                        @csrf

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email"
                                   value="{{ old('email') }}" placeholder="name@example.com" required>
                            <label for="email">
                                <i class="bi bi-envelope me-2"></i>Email Address
                            </label>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Password" required>
                            <label for="password">
                                <i class="bi bi-lock me-2"></i>Password
                            </label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-login btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Login
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="bi bi-shield-check me-1"></i>
                            Login aman dan terenkripsi
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>