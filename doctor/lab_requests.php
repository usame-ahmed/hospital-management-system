<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['doctor']);
$pageTitle = 'Lab Requests';
$doctorId = (int) current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_to_pharmacy_id'])) {
    $labTestId = (int) $_POST['send_to_pharmacy_id'];
    $medicineName = trim($_POST['medicine_name'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $durationDays = max(1, (int) ($_POST['duration_days'] ?? 5));

    $labStmt = $pdo->prepare("SELECT id, patient_id, status FROM lab_tests WHERE id=? AND doctor_id=? LIMIT 1");
    $labStmt->execute([$labTestId, $doctorId]);
    $lab = $labStmt->fetch();

    if (!$lab || $lab['status'] !== 'completed') {
        flash('danger', 'Lab result must be completed before sending to pharmacy.');
        redirect('/doctor/lab_requests.php');
    }
    if ($medicineName === '' || $dosage === '') {
        flash('danger', 'Medicine name and dosage are required.');
        redirect('/doctor/lab_requests.php');
    }

    $existsStmt = $pdo->prepare(
        "SELECT id FROM prescriptions
         WHERE patient_id=? AND doctor_id=? AND status='pending'
         ORDER BY id DESC LIMIT 1"
    );
    $existsStmt->execute([(int) $lab['patient_id'], $doctorId]);
    $existing = $existsStmt->fetch();

    if (!$existing) {
        $insert = $pdo->prepare(
            "INSERT INTO prescriptions (patient_id, doctor_id, medicine_name, dosage, duration_days, status)
             VALUES (?, ?, ?, ?, ?, 'pending')"
        );
        $insert->execute([(int) $lab['patient_id'], $doctorId, $medicineName, $dosage, $durationDays]);
    }

    $patientUpdate = $pdo->prepare("UPDATE patients SET workflow_status='pharmacy_pending' WHERE id=?");
    $patientUpdate->execute([(int) $lab['patient_id']]);

    flash('success', 'Patient sent to pharmacy successfully.');
    redirect('/doctor/lab_requests.php');
}

$rowsStmt = $pdo->prepare(
    "SELECT lt.id, lt.patient_id, p.patient_code, p.full_name, lt.test_name, lt.status, lt.result_text, lt.completed_at,
            EXISTS(
                SELECT 1 FROM prescriptions pr
                WHERE pr.patient_id=lt.patient_id AND pr.doctor_id=lt.doctor_id AND pr.status IN ('pending','issued')
            ) AS has_prescription
     FROM lab_tests lt
     JOIN patients p ON lt.patient_id=p.id
     WHERE lt.doctor_id=?
     ORDER BY lt.id DESC"
);
$rowsStmt->execute([$doctorId]);
$rows = $rowsStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<table>
    <tr><th>Patient</th><th>Test</th><th>Status</th><th>Result</th><th>Action</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['patient_code']) ?> - <?= e($row['full_name']) ?></td>
            <td><?= e($row['test_name']) ?></td>
            <td><?= e($row['status']) ?></td>
            <td><?= $row['status'] === 'completed' ? e($row['result_text']) : 'Pending lab upload' ?></td>
            <td>
                <?php if ($row['status'] === 'completed' && (int) $row['has_prescription'] === 0): ?>
                    <form method="post" class="d-grid gap-2" style="min-width: 260px;">
                        <input type="hidden" name="send_to_pharmacy_id" value="<?= (int) $row['id'] ?>">
                        <input name="medicine_name" placeholder="Medicine name" required>
                        <input name="dosage" placeholder="Dosage" required>
                        <input name="duration_days" type="number" min="1" value="5" required>
                        <button class="btn btn-success btn-sm" type="submit">Send to Pharmacy</button>
                    </form>
                <?php elseif ((int) $row['has_prescription'] === 1): ?>
                    <span class="text-success">Sent to pharmacy</span>
                <?php else: ?>
                    <button class="btn btn-secondary btn-sm" type="button" disabled>Awaiting Lab Result</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
