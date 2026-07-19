<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['check_identifier'])) {
        $identifier = trim($_POST['identifier']);
        if (empty($identifier)) {
            $error = "Please enter your email or username.";
        } else {
            // Check Admin
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$identifier]);
            $admin = $stmt->fetch();

            if ($admin) {
                $_SESSION['reset_id'] = $admin['id'];
                $_SESSION['reset_role'] = 'admin';
                $step = 2;
            } else {
                // Check Customer
                $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
                $stmt->execute([$identifier]);
                $customer = $stmt->fetch();

                if ($customer) {
                    $_SESSION['reset_id'] = $customer['id'];
                    $_SESSION['reset_role'] = 'customer';
                    $step = 2;
                } else {
                    $error = "No account found with that email/username.";
                }
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($new_password) || empty($confirm_password)) {
            $error = "Please enter and confirm your new password.";
            $step = 2;
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
            $step = 2;
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $reset_id = $_SESSION['reset_id'];
            $reset_role = $_SESSION['reset_role'];

            if ($reset_role === 'admin') {
                $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $reset_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE customers SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $reset_id]);
            }

            unset($_SESSION['reset_id']);
            unset($_SESSION['reset_role']);
            $success = "Password reset successfully. You can now log in.";
            $step = 3;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | eCommax</title>
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon-removebg-preview.png" type="image/png">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS Base -->
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
        .bg-item {
            width: 250px;
            height: 250px;
            margin: 30px;
            opacity: 0.8;
            transition: transform 0.3s;
        }
        .bg-item img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));
            border-radius: 30px;
            background: white;
            padding: 15px;
        }
        @keyframes moveBackground {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.85), rgba(40,40,50,0.6));
            z-index: -1;
        }
        .brand-logo {
            position: absolute;
            top: 30px;
            left: 40px;
            z-index: 10;
        }
        .login-container {
            width: 100%;
            max-width: 440px;
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
            margin-bottom: 2rem;
        }
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            background: -webkit-linear-gradient(45deg, #4200FF, #FF0096);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #555;
            font-weight: 500;
            margin-bottom: 0px;
        }
        .form-control {
            height: 52px;
            border-radius: 12px;
            border: 2px solid #eee;
            padding: 0 16px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(66, 0, 255, 0.1);
            border-color: #4200FF;
        }
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
            margin-top: 15px;
        }
        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 0, 150, 0.4);
            filter: brightness(1.1);
            color: white;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 0.95rem;
        }
        .back-link:hover {
            color: #4200FF;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="overlay"></div>
        <div class="bg-wrapper">
            <div class="bg-slide">
                <div class="bg-item"><img src="assets/images/hero_headphones_1770063248097.png" alt="Headphones"></div>
                <div class="bg-item"><img src="assets/images/anim_controller_purple.png" alt="Game Controller"></div>
                <div class="bg-item"><img src="assets/images/prod_laptop_1770068606240.png" alt="Laptop"></div>
            </div>
            <div class="bg-slide clone">
                <div class="bg-item"><img src="assets/images/hero_headphones_1770063248097.png" alt="Headphones"></div>
                <div class="bg-item"><img src="assets/images/anim_controller_purple.png" alt="Game Controller"></div>
                <div class="bg-item"><img src="assets/images/prod_laptop_1770068606240.png" alt="Laptop"></div>
            </div>
        </div>
    </div>

    <a href="index.php" class="brand-logo">
        <img src="assets/images/logo.svg" alt="eCommax" height="80">
    </a>

    <div class="login-container">
        <div class="login-header">
            <h1>Reset Password</h1>
            <?php if($step == 1): ?>
                <p>Enter your email or username to continue.</p>
            <?php elseif($step == 2): ?>
                <p>Create a new strong password.</p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center py-2"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success text-center py-2">
                <?= $success ?>
                <a href="login.php" class="btn btn-continue text-white mt-3" style="text-decoration:none;">Go to Login</a>
            </div>
        <?php else: ?>

            <?php if($step == 1): ?>
                <form method="POST">
                    <input type="hidden" name="check_identifier" value="1">
                    <div class="mb-3">
                        <label for="identifier" class="visually-hidden">Email or Username</label>
                        <input type="text" id="identifier" name="identifier" class="form-control" placeholder="Email or Username" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-continue">Submit</button>
                    <a href="login.php" class="back-link">Back to Log in</a>
                </form>
            <?php elseif($step == 2): ?>
                <form method="POST">
                    <input type="hidden" name="reset_password" value="1">
                    <div class="mb-3">
                        <label for="new_password" class="visually-hidden">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="New Password" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="visually-hidden">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                    </div>
                    <button type="submit" class="btn btn-continue">Reset Password</button>
                    <a href="login.php" class="back-link">Cancel</a>
                </form>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>
