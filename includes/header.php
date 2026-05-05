<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
$user = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
$flashMessages = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body data-bs-theme="light">
<div class="app-layout app-layout-dashboard" id="appShell">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content flex-grow-1">
        <header class="topbar app-topbar card border-0 shadow-sm mb-4">
            <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center gap-3 justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-outline-primary d-lg-none mobile-menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <button class="btn btn-light d-none d-lg-flex align-items-center justify-content-center sidebar-collapse-btn" style="width: 38px; height: 38px; padding: 0;" type="button" id="sidebarCollapseToggle" aria-expanded="true" aria-label="Collapse sidebar">
                            <i class="fa-solid fa-bars sidebar-collapse-icon" aria-hidden="true"></i>
                        </button>
                    </div>
                    <h1 class="h5 mb-0 fw-semibold"><?= e($pageTitle) ?></h1>
                </div>
                <div class="d-flex align-items-center gap-2 flex-grow-1 justify-content-end">
                    <div class="input-group top-search d-none d-md-flex">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="search" class="form-control" placeholder="Search modules, records...">
                    </div>
                    <button class="btn btn-light position-relative top-icon-btn" type="button">
                        <i class="fa-regular fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                    </button>
                    <button class="btn btn-light top-icon-btn" id="themeToggle" type="button" aria-label="Toggle Dark Mode">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle profile-btn" data-bs-toggle="dropdown" type="button">
                            <i class="fa-regular fa-circle-user me-1"></i><?= e($user['full_name'] ?? 'Guest') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text text-muted small"><?= e($user['role'] ?? '-') ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer">
            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="toast align-items-center text-bg-<?= e($type) === 'danger' ? 'danger' : 'success' ?> border-0 show mb-2" role="alert">
                        <div class="d-flex">
                            <div class="toast-body"><?= e($message) ?></div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <div class="content-panel">
