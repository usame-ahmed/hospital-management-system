<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/auth/login.php');
    }
}

function require_role(array $roles): void
{
    require_login();
    $user = current_user();
    if (!$user) {
        http_response_code(403);
        die('Forbidden: You do not have permission to access this page.');
    }

    if ($user['role'] === 'admin') {
        return;
    }

    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('Forbidden: You do not have permission to access this page.');
    }
}

function role_dashboard_path(string $role): string
{
    return match ($role) {
        'admin' => '/admin/dashboard.php',
        'receptionist' => '/receptionist/dashboard.php',
        'doctor' => '/doctor/dashboard.php',
        'lab_technician' => '/lab/dashboard.php',
        'pharmacist' => '/pharmacy/dashboard.php',
        default => '/auth/logout.php',
    };
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function get_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
