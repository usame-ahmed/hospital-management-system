<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin', 'receptionist']);
$pageTitle = 'Discharge Patient';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM admissions WHERE id=?");
$stmt->execute([$id]);
$admission = $stmt->fetch();
if (!$admission) {
    die('Admission record not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE admissions SET status='discharged', discharge_date=NOW() WHERE id=?")->execute([$id]);
    $pdo->prepare("UPDATE rooms SET status='available' WHERE id=?")->execute([(int) $admission['room_id']]);
    $pdo->prepare("UPDATE patients SET workflow_status='billing_pending' WHERE id=?")->execute([(int) $admission['patient_id']]);
    $pdo->commit();
    flash('success', 'Patient discharged. Billing pending.');
    redirect('/admissions/view.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <p>This action discharges the patient and marks workflow as billing pending.</p>
    <button class="btn btn-danger" type="submit">Confirm Discharge</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
