<?php
// register.php
session_start();
require_once 'config/database.php';

// if (isset($_SESSION['user_id'])) {
//     header("Location: index.php");
//     exit();
// }

// Handle Auto-Open Logic (Seamless Multi-Tab Sign Up)
$auto_open_script = '';
if (isset($_GET['signup_success'])) {
    $target_url = 'index.php';
    $auto_open_script = "
    <script>
        setTimeout(function() {
            var win = window.open('$target_url', '_blank');
            // Clean URL
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        }, 500);
    </script>";
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = trim($_POST['phone']);
    $phone = trim($_POST['phone']);
    // Address field removed from form, set to empty/null
    $address = null;

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Please enter a valid 10-digit phone number.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email is already registered.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                $sql = "INSERT INTO customers (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $email, $hashed_password, $phone, $address]);

                // Login immediately
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email; // consistent with login
                header("Location: register.php?signup_success=1");
                exit();
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
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
    <title>Create Account | eCommax</title>
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon-removebg-preview.png" type="image/png">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            /* Allow vertical scroll for long form */
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
            padding: 20px 0;
            /* Add padding for small screens/long content */
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
            transform: scale(1.1);
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
            transition: transform 0.3s;
        }

        .bg-item:hover {
            transform: scale(1.05);
        }

        .bg-item img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.1));
            border-radius: 30px;
            background: white;
            padding: 15px;
        }

        @keyframes moveBackground {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        /* Dark Overlay */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
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
            color: #fff;
            text-decoration: none;
            letter-spacing: -1px;
            z-index: 10;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Glassmorphism Container */
        .login-container {
            width: 100%;
            max-width: 500px;
            /* Slightly wider for registration */
            padding: 40px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            z-index: 50;
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .login-header h1 {
            font-size: 2.2rem;
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
            height: 48px;
            /* Slightly smaller to fit more fields */
            border-radius: 12px;
            border: 2px solid #eee;
            padding: 0 16px;
            font-size: 0.95rem;
            background: #fff;
            transition: all 0.2s;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(66, 0, 255, 0.1);
            border-color: #4200FF;
            background: #fff;
        }

        /* Vibrant Gradient Button */
        .btn-continue {
            background: linear-gradient(90deg, #4200FF 0%, #FF0096 100%);
            color: white;
            font-weight: 700;
            height: 50px;
            border-radius: 12px;
            width: 100%;
            border: none;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(66, 0, 255, 0.3);
            margin-top: 1rem;
        }

        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 0, 150, 0.4);
            filter: brightness(1.1);
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

        .footer-links {
            text-align: center;
            margin-top: 1.5rem;
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

    <!-- Background Animation (Same as Login) -->
    <div class="bg-animation">
        <div class="overlay"></div>
        <div class="bg-wrapper">
            <div class="bg-slide">
                <div class="bg-item"><img src="assets/images/hero_headphones_1770063248097.png" alt="Headphones"></div>
                <div class="bg-item"><img src="assets/images/anim_controller_purple.png" alt="Game Controller"></div>
                <div class="bg-item"><img src="assets/images/prod_laptop_1770068606240.png" alt="Laptop"></div>
                <div class="bg-item"><img src="assets/images/prod_watch_1770063262605.png" alt="Watch"></div>
                <div class="bg-item"><img src="assets/images/prod_smartphone_1770068592202.png" alt="Phone"></div>
                <div class="bg-item"><img src="assets/images/prod_speaker_1770063278098.png" alt="Speaker"></div>
                <div class="bg-item"><img src="assets/images/prod_powerbank_1770068620047.png" alt="Powerbank"></div>
            </div>
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
        <div class="login-header">
            <h1>Create Account</h1>
            <p>Already have an account? <a href="login.php">Log In</a></p>
        </div>

        <!-- Hidden Auto-Open Script -->
        <?= isset($auto_open_script) ? $auto_open_script : '' ?>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center py-2">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="visually-hidden">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Full Name *" required
                    autocomplete="name">
            </div>
            <div class="mb-3">
                <label class="visually-hidden">Phone</label>
                <input type="text" name="phone" class="form-control" placeholder="Phone Number (10 digits)" required
                    pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number">
            </div>

            <div class="mb-3">
                <label class="visually-hidden">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Email Address *" required
                    autocomplete="email">
            </div>

            <!-- Address removed as per request -->

            <div class="mb-3 position-relative">
                <label class="visually-hidden">Password</label>
                <input type="password" name="password" id="reg_password" class="form-control pe-5"
                    placeholder="Password *" required>
                <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-secondary"
                    style="cursor: pointer;" onclick="toggleRegPassword()">
                    <i class="fa fa-eye" id="toggleIcon"></i>
                </span>
            </div>

            <div class="mb-3">
                <label class="visually-hidden">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password *"
                    required>
            </div>

            <button type="submit" class="btn btn-continue">
                Register
            </button>
        </form>

        <div class="footer-links">
            <a href="#">Terms of Use</a>
            <a href="#">Privacy Policy</a>
        </div>
    </div>

    <script>
        function toggleRegPassword() {
            const passwordInput = document.getElementById('reg_password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>