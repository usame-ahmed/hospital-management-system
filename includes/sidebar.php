<?php
declare(strict_types=1);
$role = current_user()['role'] ?? '';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$appTitleWords = preg_split('/\s+/', trim(APP_NAME), -1, PREG_SPLIT_NO_EMPTY) ?: [APP_NAME];
$appTitleInitials = implode('', array_map(static function (string $w): string {
    return $w !== '' ? strtoupper($w[0]) : '';
}, $appTitleWords));
if ($appTitleInitials === '') {
    $appTitleInitials = 'HMS';
}
$links = [
    'admin' => [
        ['Dashboard', '/admin/dashboard.php', 'fa-solid fa-house'],
        ['Users', '/admin/users.php', 'fa-solid fa-users-gear'],
        ['Patients', '/receptionist/view.php', 'fa-solid fa-user-injured'],
        ['Doctors', '/admin/doctors.php', 'fa-solid fa-user-doctor'],
        ['Nurses', '/admin/nurses.php', 'fa-solid fa-user-nurse'],
        ['Lab', '/lab/view.php', 'fa-solid fa-flask'],
        ['Pharmacy', '/pharmacy/view.php', 'fa-solid fa-pills'],
        ['Rooms', '/admissions/view.php', 'fa-solid fa-bed'],
        ['Bills', '/billing/view.php', 'fa-solid fa-file-invoice-dollar'],
        ['Settings', '/admin/create.php', 'fa-solid fa-cog'],
    ],
    'receptionist' => [
        ['Dashboard', '/receptionist/dashboard.php', 'fa-solid fa-house'],
        ['Patients', '/receptionist/view.php', 'fa-solid fa-user-injured'],
        ['Admissions', '/admissions/view.php', 'fa-solid fa-bed-pulse'],
        ['Billing', '/billing/view.php', 'fa-solid fa-file-invoice-dollar'],
    ],
    'doctor' => [
        ['Dashboard', '/doctor/dashboard.php', 'fa-solid fa-house'],
        ['Assigned Patients', '/doctor/view.php', 'fa-solid fa-user-injured'],
        ['Diagnose', '/doctor/view.php', 'fa-solid fa-notes-medical'],
        ['Lab Requests', '/doctor/lab_requests.php', 'fa-solid fa-flask'],
        ['Prescriptions', '/pharmacy/view.php', 'fa-solid fa-pills'],
    ],
    'lab_technician' => [
        ['Dashboard', '/lab/dashboard.php', 'fa-solid fa-house'],
        ['Pending Tests', '/lab/view.php', 'fa-solid fa-hourglass-half'],
        ['Upload Result', '/lab/view.php', 'fa-solid fa-file-medical'],
    ],
    'pharmacist' => [
        ['Dashboard', '/pharmacy/dashboard.php', 'fa-solid fa-house'],
        ['Medical', '/pharmacy/medical.php', 'fa-solid fa-pills'],
        ['Prescriptions', '/pharmacy/view.php', 'fa-solid fa-prescription-bottle-medical'],
        ['Dispense', '/pharmacy/dispense.php', 'fa-solid fa-hand-holding-medical'],
    ],
];
?>
<aside class="sidebar offcanvas-lg offcanvas-start border-0" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header d-lg-none border-bottom sidebar-offcanvas-header position-relative justify-content-center py-3 px-2">
        <h5 class="offcanvas-title fw-bold sidebar-offcanvas-title mb-0 text-center" id="appSidebarLabel">
            <span class="sidebar-offcanvas-wordmark d-flex flex-column align-items-center gap-1">
                <span class="sidebar-offcanvas-icon text-primary" aria-hidden="true"><i class="fa-solid fa-hospital"></i></span>
                <span class="sidebar-brand-short"><?= e($appTitleInitials) ?></span>
                <span class="visually-hidden"><?= e(APP_NAME) ?></span>
            </span>
        </h5>
        <button type="button" class="btn-close position-absolute top-50 end-0 translate-middle-y me-2" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0 sidebar-body">
        <div class="sidebar-brand d-none d-lg-flex flex-row align-items-center px-3 pt-4 pb-2 gap-2" aria-label="<?= e(APP_NAME) ?>">
            <span class="brand-icon brand-icon-wordmark" aria-hidden="true"><i class="fa-solid fa-hospital"></i></span>
            <div class="sidebar-brand-text text-start">
                <div class="sidebar-brand-wordmark fw-bold text-truncate" style="font-size: 0.95rem; letter-spacing: 0.5px;">
                    <?= e($appTitleInitials) ?>
                </div>
                <span class="sidebar-brand-meta text-muted d-block" style="font-size: 0.65rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 1px;">SaaS Platform</span>
            </div>
        </div>
        <nav class="sidebar-nav-scroll nav nav-pills flex-column gap-1 px-3 flex-grow-1" aria-label="Main navigation">
            <?php foreach ($links[$role] ?? [] as [$label, $path, $icon]): ?>
                <?php $isActive = str_contains($currentPath, $path); ?>
                <a class="nav-link sidebar-nav-link d-flex flex-nowrap align-items-center gap-3 <?= $isActive ? 'active' : '' ?>"
                   href="<?= BASE_URL . $path ?>"
                   title="<?= e($label) ?>">
                    <span class="nav-icon flex-shrink-0" aria-hidden="true"><i class="<?= e($icon) ?>"></i></span>
                    <span class="sidebar-link-text flex-grow-1 min-w-0 text-truncate"><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

    </div>
</aside>
