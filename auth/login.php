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

    $stmt = $pdo->prepare('SELECT id, username, password_hash, role, full_name FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
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
    <title>Login | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-body auth-body-login">
<main class="login-shell login-shell-vertical">
    <section class="login-mini-brand text-center mb-2">
        <div class="login-logo mx-auto mb-2">
            <i class="fa-solid fa-hospital"></i>
        </div>
        <h1 class="h6 mb-0 fw-bold"><?= e(APP_NAME) ?></h1>
    </section>

    <section class="login-panel login-panel-vertical">
        <div class="login-card login-card-minimal">
            <div class="text-center mb-3">
                <p class="small text-muted mb-0">Sign in to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm py-2 d-flex align-items-start gap-2" role="alert">
                    <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="post" id="loginForm" class="mt-3" autocomplete="on">
                <div class="login-float-wrap mb-3">
                    <i class="fa-regular fa-user login-field-icon" aria-hidden="true"></i>
                    <div class="form-floating">
                        <input class="form-control login-field-control" id="loginUsername" type="text" name="username" required autofocus placeholder="Username or email">
                        <label for="loginUsername">Username / Email</label>
                    </div>
                </div>

                <div class="login-float-wrap login-float-password mb-2">
                    <i class="fa-solid fa-key login-field-icon" aria-hidden="true"></i>
                    <div class="form-floating">
                        <input class="form-control login-field-control pe-5" id="loginPassword" type="password" name="password" required placeholder="Password">
                        <label for="loginPassword">Password</label>
                    </div>
                    <button class="btn btn-link login-pass-toggle" type="button" id="togglePassword" aria-label="Show password">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="rememberMe">
                        <label class="form-check-label text-muted" for="rememberMe">Remember me</label>
                    </div>
                    <a class="small text-decoration-none login-forgot" href="#" role="button">Forgot password?</a>
                </div>

                <button class="btn btn-primary btn-lg w-100 login-submit" type="submit" id="loginSubmit">
                    <span class="login-submit-label"><i class="fa-solid fa-right-to-bracket me-2"></i>Login</span>
                    <span class="login-submit-spinner spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                </button>

                <div class="login-trust-strip mt-3">
                    <div><i class="fa-solid fa-shield-check me-1"></i> Secure login</div>
                    <div><i class="fa-solid fa-lock me-1"></i> Encrypted system access</div>
                </div>
            </form>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const pass = document.getElementById('loginPassword');
    const toggle = document.getElementById('togglePassword');
    const form = document.getElementById('loginForm');
    const submit = document.getElementById('loginSubmit');
    const spinner = document.querySelector('.login-submit-spinner');
    const label = document.querySelector('.login-submit-label');

    if (toggle && pass) {
        toggle.addEventListener('click', () => {
            const isHidden = pass.getAttribute('type') === 'password';
            pass.setAttribute('type', isHidden ? 'text' : 'password');
            toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            toggle.innerHTML = isHidden ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
        });
    }

    if (form && submit && spinner && label) {
        form.addEventListener('submit', () => {
            submit.disabled = true;
            spinner.classList.remove('d-none');
            label.classList.add('opacity-75');
        });
    }
});
</script>
</body>
</html>
