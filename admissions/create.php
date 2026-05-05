<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin', 'receptionist']);
$pageTitle = 'Admit Patient';

$patients = $pdo->query("SELECT id, patient_code, full_name FROM patients WHERE workflow_status IN ('assigned','diagnosed','lab_pending','pharmacy_pending') ORDER BY id DESC")->fetchAll();
$rooms = $pdo->query("SELECT id, room_number FROM rooms WHERE status='available' ORDER BY room_number")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int) $_POST['patient_id'];
    $roomId = (int) $_POST['room_id'];

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO admissions (patient_id, room_id, admitted_by, admission_date, status) VALUES (?, ?, ?, NOW(), 'admitted')");
    $stmt->execute([$patientId, $roomId, (int) current_user()['id']]);

    $pdo->prepare("UPDATE rooms SET status='occupied' WHERE id=?")->execute([$roomId]);
    $pdo->prepare("UPDATE patients SET workflow_status='diagnosed' WHERE id=?")->execute([$patientId]);
    $pdo->commit();

    flash('success', 'Patient admitted successfully.');
    redirect('/admissions/view.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <select name="patient_id" required>
        <option value="">Select patient</option>
        <?php foreach ($patients as $patient): ?>
            <option value="<?= (int) $patient['id'] ?>"><?= e($patient['patient_code']) ?> - <?= e($patient['full_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="room_id" required>
        <option value="">Select room</option>
        <?php foreach ($rooms as $room): ?>
            <option value="<?= (int) $room['id'] ?>"><?= e($room['room_number']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Admit Patient</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
