<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$isLoggedIn = is_logged_in();
$dashboardPath = $isLoggedIn ? role_dashboard_path(current_user()['role']) : '/auth/login.php';
$primaryLabel = $isLoggedIn ? 'Go to Dashboard' : 'Login';
$primaryIcon = $isLoggedIn ? 'fa-solid fa-gauge-high' : 'fa-solid fa-right-to-bracket';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME ?> | Smart Hospital Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f7faff; }
        .hero { padding: 90px 0 70px; background: linear-gradient(130deg, #0d6efd 0%, #0b5ed7 50%, #13c0b6 100%); color: #fff; }
        .hero-card, .feature-card { border: 0; border-radius: 16px; box-shadow: 0 12px 28px rgba(15,23,42,.12); }
        .feature-card { transition: .2s ease; }
        .feature-card:hover { transform: translateY(-3px); }
        .section-title { font-weight: 700; margin-bottom: 1rem; color: #0f172a; }
        footer { background: #0f172a; color: #cbd5e1; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="<?= BASE_URL ?>/">
            <i class="fa-solid fa-hospital me-2"></i><?= APP_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto me-3">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>
            <a class="btn btn-primary" href="<?= BASE_URL . $dashboardPath ?>"><i class="<?= e($primaryIcon) ?> me-2"></i><?= e($primaryLabel) ?></a>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h1 class="display-5 fw-bold">Modern Hospital Operations, One Platform</h1>
                <p class="lead">Manage patients, clinical workflows, lab and pharmacy tasks, billing, and admissions from a secure role-based system.</p>
                <a class="btn btn-light btn-lg me-2" href="<?= BASE_URL . $dashboardPath ?>"><?= $isLoggedIn ? 'Open Dashboard' : 'Get Started' ?></a>
                <a class="btn btn-outline-light btn-lg" href="#features">Explore Modules</a>
            </div>
            <div class="col-lg-5">
                <div class="card hero-card">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-dark">Hospital SaaS Highlights</h5>
                        <p class="text-muted mb-2"><i class="fa-solid fa-check text-success me-2"></i>Role-based secure dashboards</p>
                        <p class="text-muted mb-2"><i class="fa-solid fa-check text-success me-2"></i>End-to-end treatment workflow</p>
                        <p class="text-muted mb-0"><i class="fa-solid fa-check text-success me-2"></i>Billing and discharge automation</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-5">
    <div class="container">
        <h2 class="section-title text-center">Core Modules</h2>
        <div class="row g-3">
            <?php
            $features = [
                ['Admin Control', 'fa-shield-halved', 'Manage users, permissions, and full hospital operations.'],
                ['Reception', 'fa-user-check', 'Patient registration, assignment, and admission handling.'],
                ['Doctor', 'fa-user-doctor', 'Diagnosis, lab requests, and prescriptions management.'],
                ['Nurse', 'fa-user-nurse', 'Vitals updates and admitted patient monitoring.'],
                ['Lab', 'fa-flask-vial', 'Track tests and upload diagnostic results.'],
                ['Pharmacy', 'fa-capsules', 'Issue medicines and monitor inventory usage.'],
                ['Billing', 'fa-file-invoice-dollar', 'Generate invoices, track payments, and print bills.'],
                ['Rooms', 'fa-bed-pulse', 'Admission/discharge and room status tracking.'],
            ];
            foreach ($features as [$title, $icon, $desc]): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <div class="text-primary mb-2"><i class="fa-solid <?= $icon ?> fa-xl"></i></div>
                            <h6 class="fw-semibold"><?= $title ?></h6>
                            <p class="small text-muted mb-0"><?= $desc ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container text-center">
        <h2 class="section-title">Ready to Run Your Hospital Smarter?</h2>
        <p class="text-muted mb-3">Launch secure operations with role-based access and integrated patient lifecycle management.</p>
        <a class="btn btn-primary btn-lg" href="<?= BASE_URL . $dashboardPath ?>"><?= $isLoggedIn ? 'Open Dashboard' : 'Login / Get Started' ?></a>
    </div>
</section>

<section id="contact" class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <h2 class="section-title">Contact</h2>
                <p class="text-muted mb-1"><i class="fa-solid fa-envelope text-primary me-2"></i>support@hospital-system.local</p>
                <p class="text-muted mb-1"><i class="fa-solid fa-phone text-primary me-2"></i>+1 (000) 123-4567</p>
                <p class="text-muted mb-0"><i class="fa-solid fa-location-dot text-primary me-2"></i>Main Medical Center</p>
            </div>
            <div class="col-lg-6">
                <form class="card border-0 shadow-sm p-3">
                    <input class="form-control mb-2" placeholder="Your Name">
                    <input class="form-control mb-2" type="email" placeholder="Email">
                    <textarea class="form-control mb-3" rows="3" placeholder="Message"></textarea>
                    <button class="btn btn-outline-primary" type="button">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<footer class="py-3">
    <div class="container d-flex flex-wrap justify-content-between">
        <small>&copy; <?= date('Y') ?> <?= APP_NAME ?>.</small>
        <small><a class="text-decoration-none text-light" href="<?= BASE_URL . $dashboardPath ?>"><?= $isLoggedIn ? 'Dashboard' : 'Login' ?></a></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
