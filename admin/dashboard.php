<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin']);
$pageTitle = 'Admin Dashboard';

$stats = [
    'total_patients' => fetch_count($pdo, 'patients'),
    'active_doctors' => fetch_count($pdo, 'users', "role='doctor' AND is_active=1"),
    'active_nurses' => fetch_count($pdo, 'nurses', "status='Active'"),
    'pending_lab' => fetch_count($pdo, 'lab_tests', "status='pending'"),
    'pharmacy_orders' => fetch_count($pdo, 'prescriptions', "status='pending'"),
    'total_bills' => fetch_count($pdo, 'bills'),
    'total_rooms' => fetch_count($pdo, 'rooms'),
    'appointments' => fetch_count($pdo, 'appointments'),
    'revenue' => $pdo->query("SELECT COALESCE(SUM(total_amount),0) total FROM bills WHERE payment_status='paid'")->fetch()['total'] ?? 0,
    'discharged' => fetch_count($pdo, 'patients', "workflow_status='discharged' OR workflow_status='completed'"),
];

$monthlyRevenue = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%b') month_name, COALESCE(SUM(total_amount),0) total
     FROM bills
     WHERE YEAR(created_at)=YEAR(CURDATE())
     GROUP BY MONTH(created_at)
     ORDER BY MONTH(created_at)
     LIMIT 6"
)->fetchAll();
$revenueMax = 0.0;
foreach ($monthlyRevenue as $row) {
    $revenueMax = max($revenueMax, (float) $row['total']);
}

$recentPatients = $pdo->query("SELECT patient_code, full_name, created_at FROM patients ORDER BY id DESC LIMIT 5")->fetchAll();
$recentLabs = $pdo->query(
    "SELECT p.patient_code, lt.test_name, lt.status
     FROM lab_tests lt
     JOIN patients p ON lt.patient_id=p.id
     ORDER BY lt.id DESC LIMIT 5"
)->fetchAll();
$recentRx = $pdo->query(
    "SELECT p.patient_code, pr.medicine_name, pr.status
     FROM prescriptions pr
     JOIN patients p ON pr.patient_id=p.id
     ORDER BY pr.id DESC LIMIT 5"
)->fetchAll();
$recentPayments = $pdo->query(
    "SELECT p.patient_code, b.total_amount, b.payment_status
     FROM bills b
     JOIN patients p ON b.patient_id=p.id
     ORDER BY b.id DESC LIMIT 5"
)->fetchAll();

// —— Analytics & insights (additive; core dashboard untouched above) ——
$newPatientsToday = fetch_count($pdo, 'patients', 'DATE(created_at) = CURDATE()');
$admittedNow = fetch_count($pdo, 'admissions', "status = 'admitted'");
$dischargedAdmissionsTotal = fetch_count($pdo, 'admissions', "status = 'discharged'");
$dischargesTodayCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM admissions WHERE status = 'discharged' AND DATE(discharge_date) = CURDATE()"
)->fetchColumn();

$todayPaidRevenue = $pdo->query(
    "SELECT COALESCE(SUM(total_amount), 0) FROM bills WHERE payment_status = 'paid' AND DATE(created_at) = CURDATE()"
)->fetchColumn();
$todayPaidRevenue = (float) $todayPaidRevenue;

$pendingBillsCount = fetch_count($pdo, 'bills', "payment_status = 'unpaid'");
$pendingBillsAmount = (float) $pdo->query(
    "SELECT COALESCE(SUM(total_amount), 0) FROM bills WHERE payment_status = 'unpaid'"
)->fetchColumn();

$billsIssuedThisMonth = fetch_count($pdo, 'bills', 'YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())');
$billingVolumeThisMonth = (float) $pdo->query(
    "SELECT COALESCE(SUM(total_amount), 0) FROM bills WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"
)->fetchColumn();

$completedLabTests = fetch_count($pdo, 'lab_tests', "status = 'completed'");
$pharmacyOrdersTotal = fetch_count($pdo, 'prescriptions');
$completedConsultationsAll = fetch_count($pdo, 'appointments', "status = 'completed'");
$lowStockMedicinesCount = fetch_count($pdo, 'medicines', 'stock_quantity <= reorder_level');

$avgPatientsPerDoctorStmt = $pdo->query(
    'SELECT ROUND(AVG(c), 1) avg_c FROM (
        SELECT COUNT(*) c FROM patients WHERE assigned_doctor_id IS NOT NULL GROUP BY assigned_doctor_id
    ) t'
)->fetch();
$avgPatientsPerDoctor = $avgPatientsPerDoctorStmt && $avgPatientsPerDoctorStmt['avg_c'] !== null
    ? (float) $avgPatientsPerDoctorStmt['avg_c']
    : 0.0;

$topBusyDoctors = $pdo->query(
    "SELECT u.full_name AS doctor_name, COUNT(p.id) AS patient_count
     FROM users u
     INNER JOIN patients p ON p.assigned_doctor_id = u.id
     WHERE u.role = 'doctor' AND u.is_active = 1
     GROUP BY u.id
     ORDER BY patient_count DESC
     LIMIT 5"
)->fetchAll();

$patientsWithDoctor = fetch_count($pdo, 'patients', 'assigned_doctor_id IS NOT NULL');

$billingTotalsRow = $pdo->query(
    'SELECT COALESCE(SUM(consultation_fee),0) cf, COALESCE(SUM(lab_fee),0) lf,
            COALESCE(SUM(pharmacy_fee),0) pf, COALESCE(SUM(room_fee),0) rf
     FROM bills'
)->fetch() ?: [];

$patientGrowthMonthly = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%b') AS month_label,
            YEAR(created_at) AS yr, MONTH(created_at) AS mo, COUNT(*) AS cnt
     FROM patients
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
     GROUP BY YEAR(created_at), MONTH(created_at)
     ORDER BY yr, mo"
)->fetchAll();

$revenueMonthlyChart = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%b') AS month_label,
            YEAR(created_at) AS yr, MONTH(created_at) AS mo,
            COALESCE(SUM(total_amount), 0) AS total
     FROM bills
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
     GROUP BY YEAR(created_at), MONTH(created_at)
     ORDER BY yr, mo"
)->fetchAll();

$chartPatientLabels = array_column($patientGrowthMonthly, 'month_label');
$chartPatientData = array_map(static fn(array $row): int => (int) $row['cnt'], $patientGrowthMonthly);
$chartRevenueLabels = array_column($revenueMonthlyChart, 'month_label');
$chartRevenueData = array_map(static fn(array $row): float => (float) $row['total'], $revenueMonthlyChart);

$billingPieLabels = ['Consultation', 'Laboratory', 'Pharmacy', 'Rooms'];
$billingPieData = [
    (float) ($billingTotalsRow['cf'] ?? 0),
    (float) ($billingTotalsRow['lf'] ?? 0),
    (float) ($billingTotalsRow['pf'] ?? 0),
    (float) ($billingTotalsRow['rf'] ?? 0),
];
$billingPieTotal = array_sum($billingPieData);

$insights = [];
if ($lowStockMedicinesCount > 0) {
    $insights[] = ['warning', 'fa-pills', 'Low medicine stock', (string) $lowStockMedicinesCount . ' SKU(s) at or below reorder level. Review inventory.'];
}
if ($pendingBillsCount >= 8 || $pendingBillsAmount >= 5000) {
    $insights[] = ['danger', 'fa-file-circle-exclamation', 'High pending billing', '$' . number_format($pendingBillsAmount, 2) . ' unpaid across ' . (string) $pendingBillsCount . ' invoice(s).'];
}
if (($stats['pending_lab'] ?? 0) >= 6) {
    $insights[] = ['info', 'fa-flask', 'Lab backlog', (string) (int) $stats['pending_lab'] . ' lab test(s) still pending assignment or results.'];
}
if ($topBusyDoctors && ($topBusyDoctors[0]['patient_count'] ?? 0) >= max(5, ceil($avgPatientsPerDoctor + 3))) {
    $topName = (string) $topBusyDoctors[0]['doctor_name'];
    $topCnt = (int) $topBusyDoctors[0]['patient_count'];
    $insights[] = ['primary', 'fa-user-doctor', 'Busy physician load', $topName . ' is managing ' . (string) $topCnt . ' assigned patient(s).'];
}

include __DIR__ . '/../includes/header.php';
?>
<section class="dashboard-cards mb-4">
    <article class="dashboard-stat-card grad-blue">
        <div><p class="label">Total Patients</p><h3><?= (int) $stats['total_patients'] ?></h3><small>All registered patients</small></div>
        <span class="icon-wrap"><i class="fa-solid fa-user-injured icon"></i></span>
    </article>
    <article class="dashboard-stat-card grad-cyan">
        <div><p class="label">Active Doctors</p><h3><?= (int) $stats['active_doctors'] ?></h3><small>Available for treatment</small></div>
        <span class="icon-wrap"><i class="fa-solid fa-user-md icon"></i></span>
    </article>
    <article class="dashboard-stat-card grad-indigo">
        <div><p class="label">Active Nurses</p><h3><?= (int) $stats['active_nurses'] ?></h3><small>Admin managed nurses</small></div>
        <span class="icon-wrap"><i class="fa-solid fa-user-nurse icon"></i></span>
    </article>
    <article class="dashboard-stat-card grad-orange">
        <div><p class="label">Pending Lab Tests</p><h3><?= (int) $stats['pending_lab'] ?></h3><small>Awaiting lab completion</small></div>
        <span class="icon-wrap"><i class="fa-solid fa-flask icon"></i></span>
    </article>
    <article class="dashboard-stat-card grad-purple">
        <div><p class="label">Pharmacy Orders</p><h3><?= (int) $stats['pharmacy_orders'] ?></h3><small>Pending prescriptions</small></div>
        <span class="icon-wrap"><i class="fa-solid fa-pills icon"></i></span>
    </article>
    <article class="dashboard-stat-card grad-slate">
        <div><p class="label">Bills</p><h3><?= (int) $stats['total_bills'] ?></h3><small>Total invoices generated</small></div>
        <span class="icon-wrap"><i class="fa-solid fa-file-invoice-dollar icon"></i></span>
    </article>
    <article class="dashboard-stat-card grad-teal">
        <div><p class="label">Rooms</p><h3><?= (int) $stats['total_rooms'] ?></h3><small>Hospital room inventory</small></div>
        <span class="icon-wrap"><i class="fa-solid fa-hospital icon"></i></span>
    </article>
    <article class="dashboard-stat-card grad-violet">
        <div><p class="label">Appointments</p><h3><?= (int) $stats['appointments'] ?></h3><small>Total clinical consultations</small></div>
        <span class="icon-wrap"><i class="fa-solid fa-calendar-check icon"></i></span>
    </article>
    <article class="dashboard-stat-card grad-green">
        <div><p class="label">Total Revenue</p><h3>$<?= number_format((float) $stats['revenue'], 2) ?></h3><small>Paid invoices only</small></div>
        <span class="icon-wrap"><i class="fa-solid fa-chart-line icon"></i></span>
    </article>
    <article class="dashboard-stat-card grad-red">
        <div><p class="label">Discharged Patients</p><h3><?= (int) $stats['discharged'] ?></h3><small>Completed treatment cycle</small></div>
        <span class="icon-wrap"><i class="fa-solid fa-circle-check icon"></i></span>
    </article>
</section>

<section class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
        <div class="panel-card">
            <h5><i class="fa-solid fa-chart-line text-primary me-2"></i>Revenue Overview</h5>
            <?php if ($monthlyRevenue): ?>
                <?php foreach ($monthlyRevenue as $row): ?>
                    <?php $percent = $revenueMax > 0 ? (int) round(((float) $row['total'] / $revenueMax) * 100) : 0; ?>
                    <div class="chart-row">
                        <span><?= e($row['month_name']) ?></span>
                        <div class="bar"><div style="width: <?= $percent ?>%"></div></div>
                        <strong>$<?= number_format((float) $row['total'], 0) ?></strong>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted mb-0">No revenue data yet.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="panel-card">
            <h5><i class="fa-solid fa-chart-pie text-primary me-2"></i>Lab Activity</h5>
            <div class="kpi-stack">
                <div><span>Pending</span><strong><?= (int) $stats['pending_lab'] ?></strong></div>
                <div><span>Completed</span><strong><?= fetch_count($pdo, 'lab_tests', "status='completed'") ?></strong></div>
                <div><span>Pharmacy Queue</span><strong><?= (int) $stats['pharmacy_orders'] ?></strong></div>
            </div>
        </div>
    </div>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-3 col-md-6">
        <div class="panel-card">
            <h6>Recent Patients</h6>
            <?php foreach ($recentPatients as $row): ?>
                <p class="activity-item"><?= e($row['patient_code']) ?> - <?= e($row['full_name']) ?></p>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-12 col-xl-3 col-md-6">
        <div class="panel-card">
            <h6>Latest Lab Results</h6>
            <?php foreach ($recentLabs as $row): ?>
                <p class="activity-item"><?= e($row['patient_code']) ?> | <?= e($row['test_name']) ?> <span class="text-muted">(<?= e($row['status']) ?>)</span></p>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-12 col-xl-3 col-md-6">
        <div class="panel-card">
            <h6>Recent Prescriptions</h6>
            <?php foreach ($recentRx as $row): ?>
                <p class="activity-item"><?= e($row['patient_code']) ?> | <?= e($row['medicine_name']) ?> <span class="text-muted">(<?= e($row['status']) ?>)</span></p>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-12 col-xl-3 col-md-6">
        <div class="panel-card">
            <h6>Latest Payments</h6>
            <?php foreach ($recentPayments as $row): ?>
                <p class="activity-item"><?= e($row['patient_code']) ?> | $<?= number_format((float) $row['total_amount'], 2) ?> <span class="text-muted">(<?= e($row['payment_status']) ?>)</span></p>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="analytics-hub border-top pt-4 mt-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h4 class="mb-0 fw-bold d-flex align-items-center gap-2">
            <span class="analytics-hub-icon d-inline-flex align-items-center justify-content-center rounded-3 text-white"><i class="fa-solid fa-chart-line"></i></span>
            Analytics &amp; Insights
        </h4>
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2">Live metrics · last 12 months where noted</span>
    </div>

    <div class="row g-2 mb-4">
        <?php if ($insights): ?>
            <?php foreach ($insights as $insight): ?>
                <?php
                $tone = $insight[0];
                $icon = $insight[1];
                $insTitle = $insight[2];
                $insBody = $insight[3];
                ?>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="alert alert-<?= e($tone) ?> analytics-insight border-0 shadow-sm mb-0 h-100 d-flex flex-column gap-1" role="status">
                        <div class="d-flex align-items-center gap-2 fw-semibold">
                            <i class="fa-solid <?= e($icon) ?>"></i> <?= e($insTitle) ?>
                        </div>
                        <small class="d-block opacity-90"><?= e($insBody) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-success border-0 shadow-sm analytics-insight mb-0 d-flex align-items-center gap-2" role="status">
                    <i class="fa-solid fa-circle-check fa-lg"></i>
                    <div><strong>Operational baseline green.</strong> No priority alerts surfaced from live analytics rules.</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <h6 class="analytics-section-label"><i class="fa-solid fa-hospital-user me-2 text-primary"></i>Patient analytics</h6>
    <div class="row g-3 mb-4">
        <?php
        $patientMetrics = [
            ['total', 'Total patients', (string) (int) $stats['total_patients'], 'Registered patients', 'fa-users', 'grad-analytics-teal'],
            ['newToday', 'New today', (string) (int) $newPatientsToday, 'Adds in last 24h', 'fa-user-plus', 'grad-analytics-sky'],
            ['admitted', 'Admitted', (string) (int) $admittedNow, 'Active admissions', 'fa-bed', 'grad-analytics-indigo'],
            ['discharged', 'Discharged', (string) (int) $dischargedAdmissionsTotal, 'Completed stays', 'fa-person-walking-arrow-right', 'grad-analytics-amber'],
        ];
        foreach ($patientMetrics as $pm): ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="analytics-mini-card <?= e($pm[5]) ?>">
                    <div class="analytics-mini-meta">
                        <span class="analytics-mini-icon"><i class="fa-solid <?= e($pm[4]) ?>"></i></span>
                        <span class="analytics-mini-label"><?= e($pm[1]) ?></span>
                    </div>
                    <h3 class="analytics-mini-value mb-1"><?= e($pm[2]) ?></h3>
                    <small class="analytics-mini-caption"><?= e($pm[3]) ?></small>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($dischargesTodayCount > 0): ?>
        <p class="small text-muted mb-4 analytics-footnote"><i class="fa-solid fa-arrow-trend-down me-1"></i><?= (int) $dischargesTodayCount ?> discharge(s) recorded today.</p>
    <?php endif; ?>

    <h6 class="analytics-section-label"><i class="fa-solid fa-sack-dollar me-2 text-primary"></i>Financial analytics</h6>
    <div class="row g-3 mb-4">
        <?php
        $financeMetrics = [
            ['rev', 'Total revenue', '$' . number_format((float) $stats['revenue'], 2), 'Paid invoices (all time)', 'fa-circle-dollar-to-slot', 'grad-analytics-emerald'],
            ['today', 'Today’s payments', '$' . number_format($todayPaidRevenue, 2), 'Paid bills opened today', 'fa-calendar-day', 'grad-analytics-mint'],
            ['pending', 'Pending bills', (string) (int) $pendingBillsCount . ' · $' . number_format($pendingBillsAmount, 2), 'Open balances', 'fa-file-invoice', 'grad-analytics-rose'],
            ['monthSum', 'Monthly billing', '$' . number_format($billingVolumeThisMonth, 2), (string) (int) $billsIssuedThisMonth . ' invoices this month', 'fa-chart-simple', 'grad-analytics-blue'],
        ];
        foreach ($financeMetrics as $fm): ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="analytics-mini-card <?= e($fm[5]) ?>">
                    <div class="analytics-mini-meta">
                        <span class="analytics-mini-icon"><i class="fa-solid <?= e($fm[4]) ?>"></i></span>
                        <span class="analytics-mini-label"><?= e($fm[1]) ?></span>
                    </div>
                    <h3 class="analytics-mini-value mb-1"><?= e($fm[2]) ?></h3>
                    <small class="analytics-mini-caption"><?= e($fm[3]) ?></small>
                </article>
            </div>
        <?php endforeach; ?>
    </div>

    <h6 class="analytics-section-label"><i class="fa-solid fa-microscope me-2 text-primary"></i>Medical analytics</h6>
    <div class="row g-3 mb-4">
        <?php
        $medicalMetrics = [
            ['pendingLab', 'Pending lab tests', (string) (int) $stats['pending_lab'], 'Awaiting processing', 'fa-hourglass-half', 'grad-analytics-orange'],
            ['doneLab', 'Completed lab tests', (string) (int) $completedLabTests, 'Results delivered', 'fa-circle-check', 'grad-analytics-teal'],
            ['rx', 'Pharmacy orders', (string) (int) $pharmacyOrdersTotal, 'All prescription orders (lifecycle)', 'fa-prescription-bottle-medical', 'grad-analytics-violet'],
            ['stock', 'Low stock alerts', (string) (int) $lowStockMedicinesCount, 'At or below reorder level', 'fa-triangle-exclamation', 'grad-analytics-coral'],
        ];
        foreach ($medicalMetrics as $mm): ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="analytics-mini-card <?= e($mm[5]) ?>">
                    <div class="analytics-mini-meta">
                        <span class="analytics-mini-icon"><i class="fa-solid <?= e($mm[4]) ?>"></i></span>
                        <span class="analytics-mini-label"><?= e($mm[1]) ?></span>
                    </div>
                    <h3 class="analytics-mini-value mb-1"><?= e($mm[2]) ?></h3>
                    <small class="analytics-mini-caption"><?= e($mm[3]) ?></small>
                </article>
            </div>
        <?php endforeach; ?>
    </div>

    <h6 class="analytics-section-label"><i class="fa-solid fa-user-doctor me-2 text-primary"></i>Doctor performance</h6>
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="row g-3">
                <?php
                $doctorMetrics = [
                    ['act', 'Active doctors', (string) (int) $stats['active_doctors'], 'Licensed users', 'fa-user-doctor', 'grad-analytics-indigo'],
                    ['avg', 'Patients / doctor', $avgPatientsPerDoctor > 0 ? (string) $avgPatientsPerDoctor : '—', 'Average panel size', 'fa-people-group', 'grad-analytics-sky'],
                    ['cons', 'Consultations', (string) (int) $completedConsultationsAll, 'Completed appointments', 'fa-notes-medical', 'grad-analytics-blue'],
                    ['assign', 'Patient assignments', (string) (int) $patientsWithDoctor, 'Under physician care', 'fa-link', 'grad-analytics-mint'],
                ];
                foreach ($doctorMetrics as $dm): ?>
                    <div class="col-12 col-sm-6">
                        <article class="analytics-mini-card <?= e($dm[5]) ?>">
                            <div class="analytics-mini-meta">
                                <span class="analytics-mini-icon"><i class="fa-solid <?= e($dm[4]) ?>"></i></span>
                                <span class="analytics-mini-label"><?= e($dm[1]) ?></span>
                            </div>
                            <h3 class="analytics-mini-value mb-1"><?= e($dm[2]) ?></h3>
                            <small class="analytics-mini-caption"><?= e($dm[3]) ?></small>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="analytics-leaderboard h-100">
                <h6 class="fw-semibold mb-3"><i class="fa-solid fa-ranking-star me-2 text-warning"></i>Busiest physicians</h6>
                <?php if ($topBusyDoctors): ?>
                    <ul class="list-unstyled mb-0 analytics-leader-list">
                        <?php foreach ($topBusyDoctors as $doc): ?>
                            <li class="d-flex justify-content-between align-items-center analytics-leader-row">
                                <span class="text-truncate me-2"><?= e($doc['doctor_name']) ?></span>
                                <span class="badge rounded-pill bg-light text-dark"><?= (int) $doc['patient_count'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small mb-0">No assigned patient panels detected yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h6 class="analytics-section-label"><i class="fa-solid fa-chart-area me-2 text-primary"></i>Visual analytics</h6>
    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="analytics-chart-card h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <h6 class="mb-0 fw-semibold">Patient growth trend</h6>
                        <small class="text-muted">Monthly new registrations</small>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary">Line</span>
                </div>
                <div class="analytics-chart-frame">
                    <canvas id="analyticsPatientLineChart" aria-label="Patient growth chart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="analytics-chart-card h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <h6 class="mb-0 fw-semibold">Billing composition</h6>
                        <small class="text-muted">Category mix (all invoices)</small>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary">Pie</span>
                </div>
                <div class="analytics-chart-frame analytics-chart-frame--pie">
                    <canvas id="analyticsBillingPieChart" aria-label="Billing pie chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="analytics-chart-card">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <h6 class="mb-0 fw-semibold">Monthly revenue trajectory</h6>
                        <small class="text-muted">Totals per month (posted bills)</small>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary">Bar</span>
                </div>
                <div class="analytics-chart-frame">
                    <canvas id="analyticsRevenueBarChart" aria-label="Monthly revenue bar chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <h6 class="analytics-section-label"><i class="fa-solid fa-bolt me-2 text-primary"></i>Recent activity · Analytics view</h6>
    <p class="text-muted small mb-3">Operational feeds mirror your core dashboard lists with analytics styling for quick auditing.</p>
    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="analytics-feed-panel h-100">
                <h6><i class="fa-solid fa-user-injured me-2"></i>Recent patients</h6>
                <?php foreach ($recentPatients as $row): ?>
                    <p class="analytics-feed-row"><?= e($row['patient_code']) ?> — <?= e($row['full_name']) ?></p>
                <?php endforeach; ?>
                <?php if (!$recentPatients): ?><p class="text-muted small mb-0">No patient records.</p><?php endif; ?>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="analytics-feed-panel h-100">
                <h6><i class="fa-solid fa-file-invoice-dollar me-2"></i>Latest bills</h6>
                <?php foreach ($recentPayments as $row): ?>
                    <p class="analytics-feed-row"><?= e($row['patient_code']) ?> — $<?= number_format((float) $row['total_amount'], 2) ?> <span class="text-muted">(<?= e($row['payment_status']) ?>)</span></p>
                <?php endforeach; ?>
                <?php if (!$recentPayments): ?><p class="text-muted small mb-0">No billing activity.</p><?php endif; ?>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="analytics-feed-panel h-100">
                <h6><i class="fa-solid fa-prescription-bottle-medical me-2"></i>Recent prescriptions</h6>
                <?php foreach ($recentRx as $row): ?>
                    <p class="analytics-feed-row"><?= e($row['patient_code']) ?> — <?= e($row['medicine_name']) ?> <span class="text-muted">(<?= e($row['status']) ?>)</span></p>
                <?php endforeach; ?>
                <?php if (!$recentRx): ?><p class="text-muted small mb-0">No prescriptions.</p><?php endif; ?>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="analytics-feed-panel h-100">
                <h6><i class="fa-solid fa-flask me-2"></i>Latest lab reports</h6>
                <?php foreach ($recentLabs as $row): ?>
                    <p class="analytics-feed-row"><?= e($row['patient_code']) ?> — <?= e($row['test_name']) ?> <span class="text-muted">(<?= e($row['status']) ?>)</span></p>
                <?php endforeach; ?>
                <?php if (!$recentLabs): ?><p class="text-muted small mb-0">No lab records.</p><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$chartJsonOpts = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;

    const brand = '#0d9488';
    const accent = '#0ea5e9';
    const softGrid = 'rgba(148, 163, 184, .25)';

    const patientLabels = <?= json_encode($chartPatientLabels, $chartJsonOpts) ?>;
    const patientData = <?= json_encode($chartPatientData, $chartJsonOpts) ?>;
    const revenueLabels = <?= json_encode($chartRevenueLabels, $chartJsonOpts) ?>;
    const revenueData = <?= json_encode($chartRevenueData, $chartJsonOpts) ?>;

    let billingPieLabels = <?= json_encode($billingPieLabels, $chartJsonOpts) ?>;
    let billingPieValues = <?= json_encode($billingPieData, $chartJsonOpts) ?>;
    const billingTotal = billingPieValues.reduce((a, b) => a + Number(b || 0), 0);

    const lineCtx = document.getElementById('analyticsPatientLineChart');
    const barCtx = document.getElementById('analyticsRevenueBarChart');
    const pieCtx = document.getElementById('analyticsBillingPieChart');

    if (lineCtx) {
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: patientLabels.length ? patientLabels : ['Sample'],
                datasets: [{
                    label: 'New patients',
                    data: patientLabels.length ? patientData : [0],
                    tension: .35,
                    fill: true,
                    borderWidth: 2,
                    borderColor: accent,
                    backgroundColor: 'rgba(14, 165, 233, .14)',
                    pointBackgroundColor: '#fff',
                    pointBorderColor: accent,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, labels: { usePointStyle: true, boxWidth: 8 } }
                },
                scales: {
                    x: { grid: { color: softGrid }, ticks: { color: '#64748b' } },
                    y: { beginAtZero: true, grid: { color: softGrid }, ticks: { color: '#64748b' } }
                }
            }
        });
    }

    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: revenueLabels.length ? revenueLabels : ['Sample'],
                datasets: [{
                    label: 'Revenue',
                    data: revenueLabels.length ? revenueData : [0],
                    borderRadius: 10,
                    backgroundColor: 'rgba(13, 148, 136, .55)',
                    borderColor: brand,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, labels: { usePointStyle: true, boxWidth: 8 } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: { beginAtZero: true, grid: { color: softGrid }, ticks: { color: '#64748b' } }
                }
            }
        });
    }

    if (pieCtx) {
        const palette = ['#0d9488', '#0369a1', '#6366f1', '#f59e0b'];
        let pieLabels = billingPieLabels;
        let pieValues = billingPieValues;
        if (!billingTotal) {
            pieLabels = ['Awaiting invoicing'];
            pieValues = [1];
        }

        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieValues,
                    backgroundColor: billingTotal ? palette : ['#cbd5f5'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true }
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const v = Number(ctx.raw || 0);
                                const pct = billingTotal ? Math.round((v / billingTotal) * 100) : 0;
                                if (!billingTotal) return ' Post bills to visualize mix';
                                return ` $${v.toLocaleString(undefined, { maximumFractionDigits: 0 })} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
