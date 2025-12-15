<?php
session_start(); // Add this at the very top
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

// Debug: Check session status
error_log("Login page - Session status: " . session_status());
error_log("Login page - Session logged_in: " . ($_SESSION['logged_in'] ?? 'not set'));

// Check if user is already logged in - but only redirect if properly logged in
if (
    isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
    isset($_SESSION['user_id']) && isset($_SESSION['email'])
) {
    error_log("User already logged in, redirecting to dashboard");
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $authController->login($email, $password);

    if ($result['success']) {
        // Debug: Verify session was set
        error_log("Login successful - Session verified: " . ($_SESSION['logged_in'] ?? 'false'));
        error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));

        header("Location: " . BASE_URL . "dashboard.php");
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
    <title>Login - PrintMont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <!-- Choose your prefered color scheme -->
    <!-- <link href="css/light.css" rel="stylesheet"> -->
    <!-- <link href="css/dark.css" rel="stylesheet"> -->

    <!-- BEGIN SETTINGS -->
    <!-- Remove this after purchasing -->
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body {
            opacity: 0;
        }
         .password-toggle {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 14px;
        }

        /* .security-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }

        .security-info i {
            color: #28a745;
            margin-right: 5px;
        } */

        /* @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }
            
            .login-body {
                padding: 20px;
            }
        } */
    </style>
    <!-- END SETTINGS -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-120946860-10"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'UA-120946860-10', {
            'anonymize_ip': true
        });
    </script>
</head>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <main class="d-flex w-100 h-100">
        <div class="container d-flex flex-column">
            <div class="row vh-100">
                <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 mx-auto d-table h-100">
                    <div class="d-table-cell align-middle">

                        <div class="text-center mt-4">
                            <h1 class="h2">Welcome back!</h1>
                            <p class="lead">
                                Sign in to your account to continue
                            </p>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="m-sm-3">
                                    <div class="d-grid gap-2 mb-3">
                                        <a class='btn btn-google btn-lg' href='index.html'><i class="fab fa-fw fa-google"></i> Sign in with Google</a>
                                        <a class='btn btn-facebook btn-lg' href='index.html'><i class="fab fa-fw fa-facebook-f"></i> Sign in with Facebook</a>
                                        <a class='btn btn-microsoft btn-lg' href='index.html'><i class="fab fa-fw fa-microsoft"></i> Sign in with Microsoft</a>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <hr>
                                        </div>
                                        <div class="col-auto text-uppercase d-flex align-items-center">Or</div>
                                        <div class="col">
                                            <hr>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <?php if ($error_message): ?>
                                            <div class="alert alert-error">
                                                <?php echo htmlspecialchars($error_message); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($success_message): ?>
                                            <div class="alert alert-success">
                                                <?php echo htmlspecialchars($success_message); ?>
                                            </div>
                                        <?php endif; ?>

                                        <form method="POST" action="" id="loginForm">
                                            <input type="hidden" name="login" value="1">

                                            <div class="form-group">
                                                <label for="email" class="mb-2">Email Address</label>
                                                <input type="email" class="form-control mb-2" id="email" name="email"
                                                    placeholder="Enter your email" required
                                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                            </div>

                                            <div class="form-group">
                                                <label for="password" class="mb-2">Password</label>
                                                <div class="password-toggle">
                                                    <input type="password" class="form-control mb-2" id="password" name="password"
                                                        placeholder="Enter your password" required minlength="6">
                                                    <button type="button" class="toggle-password" onclick="togglePassword()">
                                                        👁️
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="d-grid gap-2 mt-3">

                                                <button type="submit" class="btn btn-primary" id="loginBtn">
                                                    Sign In
                                                </button>
                                            </div>
                                        </form>

                                        <div class="login-footer">
                                            <a href="forgot-password.php">Forgot Password?</a>
                                        </div>

                                        <div class="security-info">
                                            <i>🔒</i> Secure login protected against brute force attacks
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </main>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Form submission handler
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = 'Signing In...';

            // Re-enable button after 3 seconds in case of error
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = 'Sign In';
            }, 3000);
        });

        // Focus on email field on page load
        document.getElementById('email').focus();

        // Add input validation
        document.getElementById('loginForm').addEventListener('input', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const btn = document.getElementById('loginBtn');

            btn.disabled = !email || !password || password.length < 6;
        });
    </script>
</body>

</html>