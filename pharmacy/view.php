<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['pharmacist']);
$pageTitle = 'Prescription Queue';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    if ($deleteId > 0) {
        $del = $pdo->prepare("DELETE FROM prescriptions WHERE id=?");
        $del->execute([$deleteId]);
        flash('success', 'Prescription deleted.');
    }
    redirect('/pharmacy/view.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_given_id'])) {
    $markId = (int) $_POST['mark_given_id'];
    if ($markId > 0) {
        $pdo->beginTransaction();
        try {
            $prescriptionStmt = $pdo->prepare("SELECT id, patient_id, status FROM prescriptions WHERE id=? LIMIT 1");
            $prescriptionStmt->execute([$markId]);
            $prescription = $prescriptionStmt->fetch();

            if ($prescription && $prescription['status'] === 'pending') {
                $update = $pdo->prepare(
                    "UPDATE prescriptions
                     SET status='issued', pharmacist_id=?, issued_quantity=COALESCE(issued_quantity, 1), issued_at=NOW()
                     WHERE id=?"
                );
                $update->execute([(int) current_user()['id'], $markId]);

                $pendingCountStmt = $pdo->prepare("SELECT COUNT(*) total FROM prescriptions WHERE patient_id=? AND status='pending'");
                $pendingCountStmt->execute([(int) $prescription['patient_id']]);
                $pendingCount = (int) ($pendingCountStmt->fetch()['total'] ?? 0);

                if ($pendingCount === 0) {
                    $patientUpdate = $pdo->prepare("UPDATE patients SET workflow_status='billing_pending' WHERE id=?");
                    $patientUpdate->execute([(int) $prescription['patient_id']]);
                } else {
                    $patientUpdate = $pdo->prepare("UPDATE patients SET workflow_status='pharmacy_pending' WHERE id=?");
                    $patientUpdate->execute([(int) $prescription['patient_id']]);
                }
                flash('success', 'Prescription marked as given.');
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            flash('danger', 'Failed to mark as given.');
        }
    }
    redirect('/pharmacy/view.php');
}

$rows = $pdo->query(
    "SELECT pr.id, p.patient_code, p.full_name, pr.medicine_name, pr.dosage, pr.status
     FROM prescriptions pr
     JOIN patients p ON pr.patient_id=p.id
     ORDER BY pr.id DESC"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-outline-primary me-2" href="<?= BASE_URL ?>/pharmacy/medical.php"><i class="fa-solid fa-pills me-2"></i>Medical</a>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/pharmacy/dispense.php"><i class="fa-solid fa-plus me-2"></i>Dispense Medicine</a>
</div>
<table>
    <tr><th>Patient</th><th>Medicine</th><th>Dosage</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['patient_code']) ?> - <?= e($row['full_name']) ?></td>
            <td><?= e($row['medicine_name']) ?></td>
            <td><?= e($row['dosage']) ?></td>
            <td><?= e($row['status']) ?></td>
            <td class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="<?= BASE_URL ?>/pharmacy/dispense.php">View</a>
                <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/pharmacy/edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                <?php if ($row['status'] === 'pending'): ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="mark_given_id" value="<?= (int) $row['id'] ?>">
                        <button class="btn btn-success btn-sm" type="submit">Mark as Given</button>
                    </form>
                <?php endif; ?>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm btn-delete"
                    data-id="<?= (int) $row['id'] ?>"
                    data-action="<?= BASE_URL ?>/pharmacy/view.php"
                    data-message="Delete this prescription?"
                >Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
