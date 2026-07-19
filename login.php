<?php
// login.php
session_start();
require_once 'config/database.php';

// If already logged in, redirect based on role (OPTIONAL: Disable this to allow dual login)
// if (isset($_SESSION['admin_id'])) {
//     header("Location: admin/index.php");
//     exit();
// }
// if (isset($_SESSION['user_id'])) {
//     header("Location: index.php");
//     exit();
// }

// 1. Handle Auto-Open Logic (Seamless Multi-Tab Login)
$auto_open_script = '';
if (isset($_GET['login_role'])) {
    $role = $_GET['login_role'];
    $target_url = '';

    if ($role === 'admin') {
        $target_url = 'admin/index.php';
    } elseif ($role === 'customer') {
        $target_url = 'index.php';
    }

    if ($target_url) {
        // Generate JS to open tab. Using setTimeout to ensure page renders first.
        $auto_open_script = "
        <script>
            setTimeout(function() {
                var win = window.open('$target_url', '_blank');
                if (!win || win.closed || typeof win.closed == 'undefined') {
                    // Popup blocked
                    var successMsg = document.getElementById('login-success-msg');
                    if(successMsg) {
                        successMsg.innerHTML = '<strong>Login Successful!</strong> <a href=\"$target_url\" target=\"_blank\" class=\"btn btn-sm btn-primary ms-2\">Open Dashboard</a>';
                        successMsg.classList.remove('d-none');
                    }
                }
                // Clean URL to prevent re-trigger on refresh
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.pathname);
                }
            }, 500);
        </script>";
    }
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($identifier) || empty($password)) {
        $error = "Please enter your email/username and password.";
    } else {
        // 1. Check if ADMIN first
        $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->execute([$identifier]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // It's an Admin
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];

            // Redirect to self with param to trigger JS open
            header("Location: login.php?login_role=admin");
            exit();
        }

        // 2. Check if CUSTOMER second
        if (!$admin) {
            $stmt = $pdo->prepare("SELECT id, name, email, password FROM customers WHERE email = ?");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // It's a Customer
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                // Update 'Saved Accounts' cookie
                $cookie_name = 'ecommax_saved_accounts';
                $saved_accounts = isset($_COOKIE[$cookie_name]) ? json_decode($_COOKIE[$cookie_name], true) : [];
                if (!is_array($saved_accounts))
                    $saved_accounts = [];

                $saved_accounts = array_filter($saved_accounts, function ($acc) use ($user) {
                    return isset($acc['email']) && $acc['email'] !== $user['email'];
                });

                $new_account = [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'avatar' => 'assets/images/admin_avatar.png'
                ];

                array_unshift($saved_accounts, $new_account);
                setcookie($cookie_name, json_encode($saved_accounts), time() + (86400 * 30), "/");

                // Redirect to self with param to trigger JS open
                header("Location: login.php?login_role=customer");
                exit();

            } else {
                $error = "Invalid credentials.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in | eCommax</title>
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon-removebg-preview.png" type="image/png">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            margin: 0;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            overflow: hidden;
            background: #fff;
        }

        .bg-wrapper {
            display: flex;
            width: 200%;
            height: 100%;
            filter: blur(8px);
            /* Blur effect */
            transform: scale(1.1);
            /* Scale up to hide blurred edges */
        }

        .bg-slide {
            display: flex;
            flex-wrap: wrap;
            width: 50%;
            justify-content: space-around;
            align-content: space-around;
            animation: moveBackground 35s linear infinite;
        }

        /* Clone for seamless loop */
        .bg-slide.clone {
            animation: moveBackground 35s linear infinite;
        }

        .bg-item {
            width: 250px;
            height: 250px;
            margin: 30px;
            opacity: 0.8;
            /* Increased opacity for visibility */
            transition: transform 0.3s;
        }

        .bg-item:hover {
            transform: scale(1.05);
            /* Subtle interaction */
        }

        .bg-item img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.1));
            /* Add depth to products */
            border-radius: 30px;
            /* Rounded edges */
            background: white;
            /* Optional: giving them a small card look */
            padding: 15px;
            /* Spacing */
        }

        @keyframes moveBackground {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }

            /* Adjusted for smoother loop */
        }

        /* Dark Overlay */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Dark Light Gradient */
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.85), rgba(40, 40, 50, 0.6));
            z-index: -1;
        }

        /* Top Left Branding */
        .brand-logo {
            position: absolute;
            top: 30px;
            left: 40px;
            font-size: 2rem;
            font-weight: 800;
            /* color: #fff; Removed to allow text-gradient */
            text-decoration: none;
            letter-spacing: -1px;
            z-index: 10;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Glassmorphism Login Container */
        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.9);
            /* Semi-transparent white */
            backdrop-filter: blur(20px);
            /* Heavy blur */
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            z-index: 50;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: -webkit-linear-gradient(45deg, #4200FF, #FF0096);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #555;
            font-weight: 500;
        }

        .login-header a {
            color: #4200FF;
            text-decoration: none;
            font-weight: 600;
        }

        .form-control {
            height: 52px;
            border-radius: 12px;
            border: 2px solid #eee;
            padding: 0 16px;
            font-size: 1rem;
            background: #fff;
            transition: all 0.2s;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(66, 0, 255, 0.1);
            border-color: #4200FF;
            background: #fff;
        }

        .forgot-link {
            display: block;
            text-align: left;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 8px;
            margin-bottom: 24px;
        }

        .forgot-link:hover {
            color: #1a1a1a;
            text-decoration: underline;
        }

        /* Vibrant Gradient Button */
        .btn-continue {
            background: linear-gradient(90deg, #4200FF 0%, #FF0096 100%);
            color: white;
            font-weight: 700;
            height: 52px;
            border-radius: 12px;
            width: 100%;
            border: none;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(66, 0, 255, 0.3);
        }

        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 0, 150, 0.4);
            filter: brightness(1.1);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
            color: #888;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #eee;
        }

        .divider span {
            padding: 0 12px;
        }

        /* Social Buttons */
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 48px;
            margin-bottom: 12px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            background: white;
            color: #444;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .social-btn:hover {
            background-color: #fff;
            border-color: #4200FF;
            color: #4200FF;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .social-btn i {
            margin-right: 12px;
            font-size: 1.25rem;
        }

        .footer-links {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.85rem;
            color: #999;
        }

        .footer-links a {
            color: #666;
            text-decoration: none;
            margin: 0 10px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <!-- Background Animation -->
    <div class="bg-animation">
        <div class="overlay"></div>
        <div class="bg-wrapper">
            <!-- Slide 1 -->
            <div class="bg-slide">
                <div class="bg-item"><img src="assets/images/hero_headphones_1770063248097.png" alt="Headphones"></div>
                <div class="bg-item"><img src="assets/images/anim_controller_purple.png" alt="Game Controller"></div>
                <div class="bg-item"><img src="assets/images/prod_laptop_1770068606240.png" alt="Laptop"></div>
                <div class="bg-item"><img src="assets/images/prod_watch_1770063262605.png" alt="Watch"></div>
                <div class="bg-item"><img src="assets/images/prod_smartphone_1770068592202.png" alt="Phone"></div>
                <div class="bg-item"><img src="assets/images/prod_speaker_1770063278098.png" alt="Speaker"></div>
                <div class="bg-item"><img src="assets/images/prod_powerbank_1770068620047.png" alt="Powerbank"></div>
            </div>
            <!-- Slide 2 (Clone for infinite loop effect) -->
            <div class="bg-slide clone">
                <div class="bg-item"><img src="assets/images/hero_headphones_1770063248097.png" alt="Headphones"></div>
                <div class="bg-item"><img src="assets/images/anim_controller_purple.png" alt="Game Controller"></div>
                <div class="bg-item"><img src="assets/images/prod_laptop_1770068606240.png" alt="Laptop"></div>
                <div class="bg-item"><img src="assets/images/prod_watch_1770063262605.png" alt="Watch"></div>
                <div class="bg-item"><img src="assets/images/prod_smartphone_1770068592202.png" alt="Phone"></div>
                <div class="bg-item"><img src="assets/images/prod_speaker_1770063278098.png" alt="Speaker"></div>
                <div class="bg-item"><img src="assets/images/prod_powerbank_1770068620047.png" alt="Powerbank"></div>
            </div>
        </div>
    </div>

    <!-- Top Left Brand -->
    <a href="index.php" class="brand-logo">
        <img src="assets/images/logo.svg" alt="eCommax" height="80">
    </a>

    <div class="login-container">
        <?php
        $is_switch_mode = isset($_GET['switch']) && isset($_GET['email']);
        $switch_user_name = '';
        if ($is_switch_mode) {
            $saved_accounts = isset($_COOKIE['ecommax_saved_accounts']) ? json_decode($_COOKIE['ecommax_saved_accounts'], true) : [];
            foreach ($saved_accounts as $acc) {
                if ($acc['email'] === $_GET['email']) {
                    $switch_user_name = $acc['name'];
                    break;
                }
            }
        }
        ?>

        <div class="login-header">
            <?php if ($is_switch_mode): ?>
                <h1>Welcome Back</h1>
                <p class="fw-bold mb-1">
                    <?= htmlspecialchars($switch_user_name) ?>
                </p>
                <p class="small text-muted mb-0">
                    <?= htmlspecialchars($_GET['email']) ?>
                </p>
                <a href="login.php" class="small text-primary text-decoration-none">Not you?</a>
            <?php else: ?>
                <h1>Log in</h1>
                <p>Don't have an account? <a href="register.php">Sign Up</a></p>
            <?php endif; ?>
        </div>

        <!-- Hidden Auto-Open Script -->
        <?= isset($auto_open_script) ? $auto_open_script : '' ?>

        <!-- Success Message Area (Hidden by default, shown if popup blocked) -->
        <div id="login-success-msg" class="alert alert-success d-none py-2 text-center"></div>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center py-2">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="username" class="visually-hidden">Email or Username</label>
                <?php if ($is_switch_mode): ?>
                    <input type="hidden" name="username" value="<?= htmlspecialchars($_GET['email']) ?>">
                <?php else: ?>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Email or Username"
                        value="<?= isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '' ?>" required
                        autocomplete="username">
                <?php endif; ?>
            </div>
            <div class="mb-2">
                <label for="password" class="visually-hidden">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Password"
                    required autocomplete="current-password">
            </div>

            <a href="forgot_password.php" class="forgot-link">Forgot Email or Password?</a>

            <button type="submit" class="btn btn-continue">
                Continue
            </button>
        </form>

        <script>
            window.onload = function () {
                const passwordInput = document.getElementById('password');
                if (passwordInput) {
                    passwordInput.focus();
                }
            }
        </script>

        <div class="divider">
            <span>or</span>
        </div>

        <a href="#" class="social-btn">
            <i class="fab fa-google" style="color: #DB4437;"></i> Continue with Google
        </a>
        <a href="#" class="social-btn">
            <i class="fab fa-facebook" style="color: #4267B2;"></i> Continue with Facebook
        </a>
        <a href="#" class="social-btn">
            <i class="fab fa-apple" style="color: #000;"></i> Continue with Apple
        </a>

        <div class="footer-links">
            <a href="#">Terms of Use</a>
            <a href="#">Privacy Policy</a>
        </div>
    </div>

</body>

</html>