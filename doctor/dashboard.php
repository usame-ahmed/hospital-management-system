<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['doctor']);
$pageTitle = 'Doctor Dashboard';
$doctorId = (int) current_user()['id'];

$assigned = $pdo->prepare("SELECT COUNT(*) total FROM patients WHERE assigned_doctor_id=? AND workflow_status NOT IN ('discharged', 'completed')");
$assigned->execute([$doctorId]);
$assignedTotal = $assigned->fetch()['total'] ?? 0;

$pendingLab = $pdo->prepare("SELECT COUNT(*) total FROM lab_tests lt JOIN patients p ON lt.patient_id=p.id WHERE p.assigned_doctor_id=? AND lt.status='pending'");
$pendingLab->execute([$doctorId]);
$labTotal = $pendingLab->fetch()['total'] ?? 0;

include __DIR__ . '/../includes/header.php';
?>
<section class="cards">
    <div class="card"><h3>Assigned Patients</h3><p><?= (int) $assignedTotal ?></p></div>
    <div class="card"><h3>Pending Lab Results</h3><p><?= (int) $labTotal ?></p></div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
