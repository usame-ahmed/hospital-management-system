<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['receptionist']);
$pageTitle = 'Reception Dashboard';

$patientsToday = $pdo->query("SELECT COUNT(*) total FROM patients WHERE DATE(created_at)=CURDATE()")->fetch()['total'] ?? 0;
$pendingAssign = $pdo->query("SELECT COUNT(*) total FROM patients WHERE workflow_status='registered'")->fetch()['total'] ?? 0;

include __DIR__ . '/../includes/header.php';
?>
<section class="cards">
    <div class="card"><h3>Patients Today</h3><p><?= (int) $patientsToday ?></p></div>
    <div class="card"><h3>Awaiting Doctor Assignment</h3><p><?= (int) $pendingAssign ?></p></div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
