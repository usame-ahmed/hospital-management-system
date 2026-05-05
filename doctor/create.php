<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['doctor']);
$pageTitle = 'Doctor Diagnosis';
$doctorId = (int) current_user()['id'];

$patientId = (int) ($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$assignedPatientsStmt = $pdo->prepare(
    "SELECT id, full_name, patient_code
     FROM patients
     WHERE assigned_doctor_id=? AND workflow_status NOT IN ('discharged', 'completed')
     ORDER BY id DESC"
);
$assignedPatientsStmt->execute([$doctorId]);
$assignedPatients = $assignedPatientsStmt->fetchAll();

$patient = null;
if ($patientId > 0) {
    $patientStmt = $pdo->prepare("SELECT * FROM patients WHERE id=? AND assigned_doctor_id=?");
    $patientStmt->execute([$patientId, $doctorId]);
    $patient = $patientStmt->fetch();
    if (!$patient) {
        die('Patient not assigned to you.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$patient) {
        flash('danger', 'Please select a valid patient.');
        redirect('/doctor/create.php');
    }
    if (empty(trim($_POST['lab_test_name'] ?? ''))) {
        flash('danger', 'Lab test request is required before pharmacy step.');
        redirect('/doctor/create.php?patient_id=' . $patientId);
    }
    $pdo->beginTransaction();
    try {
        $diag = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, diagnosis, notes, status) VALUES (?, ?, ?, ?, ?)");
        $diag->execute([$patientId, $doctorId, trim($_POST['diagnosis']), trim($_POST['notes']), 'completed']);

        $lab = $pdo->prepare("INSERT INTO lab_tests (patient_id, doctor_id, test_name, status) VALUES (?, ?, ?, 'pending')");
        $lab->execute([$patientId, $doctorId, trim($_POST['lab_test_name'])]);
        $workflow = 'lab_pending';

        $update = $pdo->prepare("UPDATE patients SET workflow_status=? WHERE id=?");
        $update->execute([$workflow, $patientId]);
        $pdo->commit();
        flash('success', 'Diagnosis saved and workflow advanced.');
        redirect('/doctor/view.php');
    } catch (Throwable $exception) {
        $pdo->rollBack();
        flash('danger', 'Failed to save diagnosis: ' . $exception->getMessage());
    }
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <?php if ($patient): ?>
        <input type="hidden" name="patient_id" value="<?= (int) $patientId ?>">
        <input value="<?= e($patient['full_name']) ?>" disabled>
    <?php else: ?>
        <select name="patient_id" required>
            <option value="">Select assigned patient</option>
            <?php foreach ($assignedPatients as $assignedPatient): ?>
                <option value="<?= (int) $assignedPatient['id'] ?>">
                    <?= e($assignedPatient['patient_code']) ?> - <?= e($assignedPatient['full_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <textarea name="diagnosis" placeholder="Diagnosis" required></textarea>
    <textarea name="notes" placeholder="Clinical notes"></textarea>
    <input name="lab_test_name" placeholder="Lab test request (required)" required>
    <button class="btn btn-primary" type="submit">Submit Diagnosis & Send to Lab</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
