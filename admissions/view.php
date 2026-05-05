<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin', 'receptionist']);
$pageTitle = 'Admissions List';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    if ($deleteId > 0) {
        $stmt = $pdo->prepare("SELECT room_id, status FROM admissions WHERE id=? LIMIT 1");
        $stmt->execute([$deleteId]);
        $admission = $stmt->fetch();
        if ($admission) {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM admissions WHERE id=?")->execute([$deleteId]);
            if (($admission['status'] ?? '') === 'admitted') {
                $pdo->prepare("UPDATE rooms SET status='available' WHERE id=?")->execute([(int) $admission['room_id']]);
            }
            $pdo->commit();
            flash('success', 'Admission record deleted.');
        }
    }
    redirect('/admissions/view.php');
}

$rows = $pdo->query(
    "SELECT a.id, p.patient_code, p.full_name, r.room_number, a.admission_date, a.discharge_date, a.status
     FROM admissions a
     JOIN patients p ON a.patient_id=p.id
     JOIN rooms r ON a.room_id=r.id
     ORDER BY a.id DESC"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary" href="<?= BASE_URL ?>/admissions/create.php">
        <i class="fa-solid fa-plus me-2"></i>Create Admission
    </a>
</div>
<table>
    <tr><th>Patient</th><th>Room</th><th>Admitted</th><th>Discharged</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['patient_code']) ?> - <?= e($row['full_name']) ?></td>
            <td><?= e($row['room_number']) ?></td>
            <td><?= e($row['admission_date']) ?></td>
            <td><?= e($row['discharge_date']) ?></td>
            <td><?= e($row['status']) ?></td>
            <td class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="<?= BASE_URL ?>/admissions/edit.php?id=<?= (int) $row['id'] ?>">View</a>
                <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/admissions/edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm btn-delete"
                    data-id="<?= (int) $row['id'] ?>"
                    data-action="<?= BASE_URL ?>/admissions/view.php"
                    data-message="Delete this admission record?"
                >Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
