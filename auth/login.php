<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    if ((current_user()['role'] ?? '') === 'nurse') {
        redirect('/auth/logout.php');
    }
    redirect(role_dashboard_path(current_user()['role']));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, username, password_hash, role, full_name, gender FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['role'] === 'nurse') {
            $error = 'Nurse direct login is disabled. Nurses are managed by Admin only.';
        } else {
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        redirect(role_dashboard_path($user['role']));
        }
    } else {
        $error = 'Invalid credentials.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Hospital Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1rem;
            background-color: #f8fafc;
        }
        .bg-slideshow {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -2;
            background-color: #1e3c72;
        }
        .bg-slideshow div {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            animation: fadeSlide 24s infinite;
        }
        .bg-slideshow div:nth-child(1) { background-image: url('../assets/img/reception.jpg'); animation-delay: 0s; }
        .bg-slideshow div:nth-child(2) { background-image: url('../assets/img/doctor.jpg'); animation-delay: 6s; }
        .bg-slideshow div:nth-child(3) { background-image: url('../assets/img/pharmacy.jpg'); animation-delay: 12s; }
        .bg-slideshow div:nth-child(4) { background-image: url('../assets/img/lab.jpg'); animation-delay: 18s; }
        @keyframes fadeSlide {
            0% { opacity: 0; }
            10% { opacity: 1; }
            25% { opacity: 1; }
            35% { opacity: 0; }
            100% { opacity: 0; }
        }
        .bg-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.85) 0%, rgba(42, 82, 152, 0.85) 50%, rgba(19, 192, 182, 0.85) 100%);
        }
        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 400px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            padding: 2.5rem 2rem;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #1e3c72, #13c0b6);
        }
        .medical-icon-wrapper {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #e0f7fa, #b2ebf2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .medical-icon-wrapper i {
            font-size: 2rem;
            color: #00838f;
        }
        .login-title {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.4rem;
            margin-bottom: 0.25rem;
        }
        .login-subtitle {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 2rem;
        }
        .form-floating {
            margin-bottom: 1.25rem;
            position: relative;
        }
        .form-floating > .form-control {
            border-radius: 12px;
            padding-left: 2.8rem !important;
            border: 1.5px solid #e2e8f0;
            height: 3.2rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .form-floating > .form-control:focus {
            border-color: #13c0b6;
            box-shadow: 0 0 0 0.25rem rgba(19, 192, 182, 0.15);
        }
        .form-floating > label {
            padding-left: 2.8rem !important;
            color: #95a5a6;
        }
        .field-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: #00838f;
            z-index: 5;
            font-size: 1.1rem;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            color: #95a5a6;
            cursor: pointer;
            z-index: 5;
            transition: color 0.3s ease;
            background: none;
            border: none;
            padding: 0;
        }
        .toggle-password:hover {
            color: #2c3e50;
        }
        .btn-login {
            background: linear-gradient(135deg, #1e3c72, #13c0b6);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(19, 192, 182, 0.3);
            color: white;
        }
        .alert {
            font-size: 0.9rem;
            border-radius: 10px;
            padding: 0.75rem;
            text-align: left;
        }
    </style>
</head>
<body>

<div class="bg-slideshow">
    <div></div>
    <div></div>
    <div></div>
    <div></div>
</div>
<div class="bg-overlay"></div>

<div class="login-card">
    <div class="medical-icon-wrapper">
        <i class="fa-solid fa-hospital-user"></i>
    </div>
    <h1 class="login-title">Hospital Management System</h1>
    <p class="login-subtitle">Welcome back! Please login to continue.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" id="loginForm">
        <div class="form-floating">
            <i class="fa-solid fa-user field-icon"></i>
            <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email" required autofocus>
            <label for="username">Username / Email</label>
        </div>
        
        <div class="form-floating position-relative">
            <i class="fa-solid fa-lock field-icon"></i>
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
            <label for="password">Password</label>
            <button type="button" class="toggle-password" id="togglePasswordBtn" aria-label="Toggle password visibility">
                <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
            </button>
        </div>

        <button type="submit" class="btn btn-login">
            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Login
        </button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        toggleBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });
    });
</script>

</body>
</html>
