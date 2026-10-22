<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ob_start();
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

$database = new Database();
try {
    $db = $database->getConnection();
    $authController = new AuthController($db);
} catch (Exception $e) {
    $error_message = 'Unable to connect to the database. Please verify MySQL is running and the credentials in config/database.php.';
    error_log($e->getMessage());
    $authController = null;
}

// Check if user is already logged in
if ($authController !== null && $authController->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login']) && $authController !== null) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $authController->login($email, $password);

    if ($result['success']) {
        error_log("Login successful - Session verified: " . ($_SESSION['logged_in'] ?? 'false'));
        error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
        header("Location: dashboard.php");
        exit();
    } else {
        $error_message = $result['message'];
        error_log("Login failed: " . $result['message']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &bull; PrintMont Admin Portal</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="favicon.png" type="image/png">
    <link rel="icon" href="favicon.png" type="image/png">

    <!-- Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Core Template CSS -->
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">

    <style>
        :root {
            --pm-primary: #0b53a1;
            --pm-primary-dark: #073b75;
            --pm-primary-light: #e0edff;
            --pm-accent: #0284c7;
            --pm-navy-dark: #031b33;
            --pm-navy-deep: #072d4a;
            --pm-surface: #ffffff;
            --pm-surface-muted: #f8fafc;
            --pm-border: #e2e8f0;
            --pm-text-heading: #0f172a;
            --pm-text-body: #475569;
            --pm-text-muted: #64748b;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            height: 100vh;
            height: 100dvh;
            width: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #03172c;
            color: var(--pm-text-body);
        }

        .auth-split-wrapper {
            height: 100%;
            height: 100vh;
            height: 100dvh;
            width: 100vw;
            display: flex;
            overflow: hidden;
        }

        /* ---------------- Left Branding Showcase (Desktop) ---------------- */
        .auth-hero-pane {
            flex: 1.15;
            height: 100%;
            background: radial-gradient(circle at 15% 20%, #0d4b8f 0%, #062a4f 55%, #02162b 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(1.25rem, 2.8vh, 2.5rem) clamp(2rem, 3.5vw, 3.5rem);
            color: #ffffff;
            overflow: hidden;
        }

        /* Decorative Ambient Glows */
        .auth-hero-pane::before {
            content: "";
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, rgba(14, 165, 233, 0) 70%);
            filter: blur(40px);
            pointer-events: none;
        }

        .auth-hero-pane::after {
            content: "";
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(11, 83, 161, 0.3) 0%, rgba(11, 83, 161, 0) 70%);
            filter: blur(45px);
            pointer-events: none;
        }

        .hero-brand-header {
            position: relative;
            z-index: 2;
        }

        .hero-logo-img {
            max-height: clamp(34px, 4.5vh, 42px);
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.25));
        }

        .hero-portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
            color: #e0f2fe;
            margin-left: 10px;
        }

        .hero-main-content {
            position: relative;
            z-index: 2;
            max-width: 560px;
            margin: auto 0;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #38bdf8;
            margin-bottom: 0.4rem;
        }

        .hero-title {
            font-size: clamp(1.75rem, 3.2vh, 2.4rem);
            font-weight: 800;
            line-height: 1.15;
            color: #ffffff;
            margin-bottom: 0.5rem;
            letter-spacing: -0.03em;
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, #7dd3fc 0%, #38bdf8 50%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description {
            font-size: clamp(0.85rem, 1.4vh, 0.95rem);
            line-height: 1.45;
            color: #cbd5e1;
            margin-bottom: clamp(0.75rem, 1.8vh, 1.25rem);
            font-weight: 400;
        }

        .hero-feature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 0;
        }

        .hero-feature-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 10px 14px;
            transition: all 0.2s ease;
        }

        .hero-feature-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            border-color: rgba(56, 189, 248, 0.35);
        }

        .hero-feature-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(56, 189, 248, 0.18);
            color: #38bdf8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .hero-feature-title {
            font-size: 12.5px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 2px;
        }

        .hero-feature-desc {
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.35;
            margin: 0;
        }

        .hero-footer-status {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 11.5px;
            color: #94a3b8;
        }

        .status-indicator-live {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #34d399;
            font-weight: 600;
        }

        .status-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 2s infinite cubic-bezier(0.4, 0, 0.6, 1);
        }

        @keyframes pulse-green {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* ---------------- Right Authentication Form Pane ---------------- */
        .auth-form-pane {
            flex: 0.85;
            height: 100%;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 2.5vh, 2rem) clamp(1.25rem, 3vw, 2.5rem);
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .auth-form-container {
            width: 100%;
            max-width: 390px;
            margin: auto;
        }

        .auth-mobile-brand {
            display: none;
            text-align: center;
            margin-bottom: clamp(0.5rem, 1.5vh, 1rem);
        }

        .auth-card-header {
            margin-bottom: clamp(0.75rem, 1.8vh, 1.25rem);
        }

        .auth-card-title {
            font-size: clamp(1.35rem, 2.6vh, 1.6rem);
            font-weight: 800;
            color: var(--pm-text-heading);
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .auth-card-subtitle {
            font-size: clamp(0.8rem, 1.3vh, 0.875rem);
            color: var(--pm-text-muted);
            line-height: 1.4;
            margin: 0;
        }

        /* Form Inputs */
        .form-floating-custom {
            margin-bottom: clamp(0.6rem, 1.4vh, 0.95rem);
            position: relative;
        }

        .form-label-custom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 4px;
        }

        .form-control-icon-wrapper {
            position: relative;
        }

        .input-leading-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 4;
        }

        .form-control-custom {
            width: 100%;
            height: clamp(40px, 4.8vh, 45px);
            padding: 8px 38px 8px 38px;
            font-size: 13.5px;
            font-weight: 500;
            color: #1e293b;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.2s ease;
            outline: none;
            font-family: inherit;
        }

        .form-control-custom::placeholder {
            color: #94a3b8;
            font-weight: 400;
            font-size: 13px;
        }

        .form-control-custom:focus {
            background-color: #ffffff;
            border-color: var(--pm-primary);
            box-shadow: 0 0 0 3px rgba(11, 83, 161, 0.12);
        }

        .form-control-custom:focus ~ .input-leading-icon,
        .form-control-icon-wrapper:focus-within .input-leading-icon {
            color: var(--pm-primary);
        }

        /* Password Eye Toggle */
        .btn-password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 14px;
            padding: 5px;
            border-radius: 6px;
            cursor: pointer;
            transition: color 0.2s ease, background-color 0.2s ease;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-password-toggle:hover {
            color: var(--pm-primary);
            background-color: rgba(11, 83, 161, 0.08);
        }

        /* Caps Lock Warning Banner */
        .caps-lock-warning {
            display: none;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: #b45309;
            background-color: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 4px 8px;
            margin-top: 4px;
            animation: fadeIn 0.2s ease;
        }

        /* Checkbox & Options */
        .auth-options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: clamp(0.75rem, 1.6vh, 1.1rem);
            font-size: 12.5px;
        }

        .form-check-custom {
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            user-select: none;
            color: #475569;
            margin: 0;
        }

        .form-check-custom input[type="checkbox"] {
            width: 15px;
            height: 15px;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
            cursor: pointer;
            accent-color: var(--pm-primary);
        }

        .link-forgot-password {
            color: var(--pm-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .link-forgot-password:hover {
            color: var(--pm-primary-dark);
            text-decoration: underline;
        }

        /* Submit Button */
        .btn-auth-submit {
            width: 100%;
            height: clamp(42px, 5vh, 46px);
            background: linear-gradient(135deg, #0b53a1 0%, #084282 100%);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 14.5px;
            font-weight: 700;
            letter-spacing: 0.2px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(11, 83, 161, 0.3);
            transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);
        }

        .btn-auth-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(11, 83, 161, 0.4);
            background: linear-gradient(135deg, #0d5bb0 0%, #0a4c94 100%);
        }

        .btn-auth-submit:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(11, 83, 161, 0.25);
        }

        .btn-auth-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Alerts */
        .auth-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12.5px;
            line-height: 1.4;
            margin-bottom: 0.85rem;
            animation: fadeIn 0.25s ease;
        }

        .auth-alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }

        .auth-alert-success {
            background-color: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #15803d;
        }

        /* Demo Credentials Accordion / Helper */
        .demo-credentials-card {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 8px 10px;
            margin-top: clamp(0.6rem, 1.4vh, 1rem);
            font-size: 11.5px;
        }

        .demo-pill-btn {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 600;
            color: #0b53a1;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .demo-pill-btn:hover {
            background-color: #eff6ff;
            border-color: #bfdbfe;
        }

        /* Security Badges Footer */
        .auth-security-footer {
            margin-top: clamp(0.6rem, 1.4vh, 1rem);
            padding-top: 0.5rem;
            border-top: 1px solid #f1f5f9;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Breakpoints - Mobile Screen Fit */
        @media (max-width: 991.98px) {
            html, body {
                overflow-y: auto;
                background-color: #ffffff;
            }
            .auth-split-wrapper {
                height: auto;
                min-height: 100vh;
                min-height: 100dvh;
            }
            .auth-hero-pane {
                display: none;
            }
            .auth-form-pane {
                flex: 1;
                height: auto;
                min-height: 100vh;
                min-height: 100dvh;
                padding: clamp(1rem, 3vh, 2rem) 1.25rem;
                background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            }
            .auth-mobile-brand {
                display: block;
            }
            .auth-card-title {
                font-size: 1.45rem;
            }
        }
    </style>
</head>
<body>

    <div class="auth-split-wrapper">
        
        <!-- Left Hero Branding Pane (Desktop >= 992px) -->
        <aside class="auth-hero-pane" aria-label="Printmont System Highlights">
            <!-- Brand Header -->
            <div class="hero-brand-header d-flex align-items-center">
                <img src="img/PrintwhiteLogo.png" alt="Printmont Enterprise Logo" class="hero-logo-img" onerror="this.src='favicon.png'">
                <span class="hero-portal-badge">
                    <i class="fa-solid fa-cube text-info"></i> Admin Suite v3.5
                </span>
            </div>

            <!-- Main Marketing / System Showcase -->
            <div class="hero-main-content">
                <div class="hero-eyebrow">
                    <i class="fa-solid fa-bolt"></i> Enterprise Print Commerce OS
                </div>
                <h1 class="hero-title">
                    Unified Control for <span class="gradient-text">Printmont.</span>
                </h1>
                <p class="hero-description">
                    Orchestrate catalogs, configure multi-tier navbar layouts, process custom merchandise orders, and oversee real-time fulfillment through a secure administrative cockpit.
                </p>

                <!-- Feature Grid -->
                <div class="hero-feature-grid">
                    <div class="hero-feature-card">
                        <div class="hero-feature-icon">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div class="hero-feature-title">Header Category Studio</div>
                        <p class="hero-feature-desc">Dual desktop & mobile navigation designer with live top icon management.</p>
                    </div>

                    <div class="hero-feature-card">
                        <div class="hero-feature-icon">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div class="hero-feature-title">Enterprise Security</div>
                        <p class="hero-feature-desc">Encrypted sessions, brute-force throttling, and role-based access rules.</p>
                    </div>

                    <div class="hero-feature-card">
                        <div class="hero-feature-icon">
                            <i class="fa-solid fa-truck-fast"></i>
                        </div>
                        <div class="hero-feature-title">Order Processing</div>
                        <p class="hero-feature-desc">End-to-end dispatch tracking, invoice generation, and customer alerts.</p>
                    </div>

                    <div class="hero-feature-card">
                        <div class="hero-feature-icon">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="hero-feature-title">Live Metrics & Inventory</div>
                        <p class="hero-feature-desc">Real-time revenue monitoring and automated stock replenishment alerts.</p>
                    </div>
                </div>
            </div>

            <!-- Footer Status Indicator -->
            <div class="hero-footer-status">
                <div class="status-indicator-live">
                    <span class="status-pulse-dot"></span>
                    <span>Core Services Operational &bull; 99.98% SLA</span>
                </div>
                <span>&copy; <?php echo date('Y'); ?> Printmont Media Inc.</span>
            </div>
        </aside>

        <!-- Right Form Pane -->
        <main class="auth-form-pane">
            <div class="auth-form-container">

                <!-- Mobile Brand Header (Visible only on < 992px) -->
                <div class="auth-mobile-brand">
                    <img src="img/PrintLogo.png" alt="Printmont Logo" height="42" onerror="this.src='favicon.png'">
                    <div class="mt-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fw-semibold" style="font-size: 11px;">
                            Enterprise Admin Suite
                        </span>
                    </div>
                </div>

                <!-- Form Card Header -->
                <div class="auth-card-header">
                    <h2 class="auth-card-title">
                        Welcome Back <span style="font-size: 1.4rem;">👋</span>
                    </h2>
                    <p class="auth-card-subtitle">
                        Sign in with your verified administrative credentials to access your store control tools.
                    </p>
                </div>

                <!-- Error Alert Banner -->
                <?php if (!empty($error_message)): ?>
                    <div class="auth-alert auth-alert-danger" role="alert">
                        <i class="fa-solid fa-circle-exclamation mt-1"></i>
                        <div>
                            <strong>Sign In Failed:</strong>
                            <div><?php echo htmlspecialchars($error_message); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Success Alert Banner -->
                <?php if (!empty($success_message)): ?>
                    <div class="auth-alert auth-alert-success" role="alert">
                        <i class="fa-solid fa-circle-check mt-1"></i>
                        <div>
                            <strong>Notice:</strong>
                            <div><?php echo htmlspecialchars($success_message); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Authentication Form -->
                <form method="POST" action="index.php" id="loginForm" autocomplete="on" novalidate>
                    <input type="hidden" name="login" value="1">

                    <!-- Email Field -->
                    <div class="form-floating-custom">
                        <label for="email" class="form-label-custom">
                            <span>Email Address <span class="text-danger">*</span></span>
                        </label>
                        <div class="form-control-icon-wrapper">
                            <i class="fa-regular fa-envelope input-leading-icon"></i>
                            <input 
                                type="email" 
                                class="form-control-custom" 
                                id="email" 
                                name="email" 
                                placeholder="name@printmont.com"
                                autocomplete="username"
                                spellcheck="false"
                                inputmode="email"
                                required
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-floating-custom">
                        <label for="password" class="form-label-custom">
                            <span>Password <span class="text-danger">*</span></span>
                        </label>
                        <div class="form-control-icon-wrapper">
                            <i class="fa-solid fa-lock input-leading-icon"></i>
                            <input 
                                type="password" 
                                class="form-control-custom" 
                                id="password" 
                                name="password" 
                                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                                autocomplete="current-password"
                                required
                                minlength="6"
                            >
                            <button 
                                type="button" 
                                class="btn-password-toggle" 
                                id="togglePasswordBtn" 
                                title="Show or hide password"
                                aria-label="Toggle password visibility"
                            >
                                <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>

                        <!-- Caps Lock Warning -->
                        <div class="caps-lock-warning" id="capsLockWarning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>Caps Lock is ON</span>
                        </div>
                    </div>

                    <!-- Options Row: Remember Me & Forgot Password -->
                    <div class="auth-options-row">
                        <label class="form-check-custom" for="rememberMe">
                            <input type="checkbox" id="rememberMe" name="remember_me" value="1" checked>
                            <span>Keep me signed in</span>
                        </label>

                        <a href="javascript:void(0)" class="link-forgot-password" onclick="openForgotModal()">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-auth-submit" id="loginBtn">
                        <span id="btnText">Sign In to Portal</span>
                        <i class="fa-solid fa-arrow-right" id="btnIcon"></i>
                    </button>
                </form>

                <!-- Dev Quick Fill Helper (Convenience for testing) -->
                <div class="demo-credentials-card">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted fw-semibold" style="font-size: 11.5px;">
                            <i class="fa-solid fa-key text-warning me-1"></i> Quick Admin Access:
                        </span>
                        <span class="badge bg-light text-muted border" style="font-size: 10px;">Dev Shortcut</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="button" class="demo-pill-btn" onclick="fillCredentials('superadmin@printmont.com')">
                            <i class="fa-solid fa-user-shield"></i> superadmin@printmont.com
                        </button>
                    </div>
                </div>

                <!-- Security Footer -->
                <div class="auth-security-footer">
                    <i class="fa-solid fa-shield-halved text-success"></i>
                    <span>Protected by Printmont Shield &bull; 256-bit SSL Session</span>
                </div>

            </div>
        </main>

    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotModal" tabindex="-1" aria-labelledby="forgotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header border-0 bg-light p-4 pb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #e0edff; color: #0b53a1; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark m-0" id="forgotModalLabel">Reset Credentials</h5>
                            <small class="text-muted">Administrator Account Recovery</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-3">
                    <p class="text-secondary small mb-3">
                        For enterprise security reasons, administrative credentials cannot be self-reset through automated public forms without high-privilege token verification.
                    </p>
                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <div class="fw-bold text-dark small mb-1">
                            <i class="fa-solid fa-headset text-primary me-1"></i> Standard Protocol:
                        </div>
                        <div class="text-muted small">
                            Please contact your <strong>Super Administrator</strong> or system DevOps to request an authorized credential reset.
                        </div>
                    </div>
                    <div class="small text-muted">
                        Need CLI recovery? Authorized admins can reset credentials via the backend server command line or database seed script.
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-primary w-100 py-2 rounded-3 fw-bold" data-bs-dismiss="modal">
                        Understood
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="js/app.js"></script>

    <script>
        // Password Visibility Toggle
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        toggleBtn.addEventListener('click', function () {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye-axe');
                toggleIcon.classList.add('fa-eye');
            }
            passwordInput.focus();
        });

        // Caps Lock Indicator
        const capsLockWarning = document.getElementById('capsLockWarning');
        passwordInput.addEventListener('keyup', function (e) {
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                capsLockWarning.style.display = 'flex';
            } else {
                capsLockWarning.style.display = 'none';
            }
        });
        passwordInput.addEventListener('keydown', function (e) {
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                capsLockWarning.style.display = 'flex';
            } else {
                capsLockWarning.style.display = 'none';
            }
        });

        // Form Submit Loading Feedback
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const btnText = document.getElementById('btnText');
        const btnIcon = document.getElementById('btnIcon');

        loginForm.addEventListener('submit', function (e) {
            if (!loginForm.checkValidity()) {
                return;
            }
            loginBtn.disabled = true;
            btnText.textContent = 'Authenticating...';
            btnIcon.className = 'fa-solid fa-circle-notch fa-spin ms-2';

            // Safety timeout to re-enable button after 5 seconds in case of network issue
            setTimeout(() => {
                loginBtn.disabled = false;
                btnText.textContent = 'Sign In to Portal';
                btnIcon.className = 'fa-solid fa-arrow-right ms-2';
            }, 5000);
        });

        // Autofocus on Email if empty, else Password
        const emailInput = document.getElementById('email');
        if (!emailInput.value.trim()) {
            emailInput.focus();
        } else {
            passwordInput.focus();
        }

        // Quick-Fill Helper Function
        function fillCredentials(email) {
            emailInput.value = email;
            passwordInput.focus();
        }

        // Forgot Password Modal
        function openForgotModal() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = new bootstrap.Modal(document.getElementById('forgotModal'));
                modal.show();
            } else {
                alert('For security reasons, please contact the Super Administrator to reset your admin account credentials.');
            }
        }
    </script>
</body>
</html>