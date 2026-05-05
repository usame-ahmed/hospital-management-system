<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['receptionist', 'admin']);
$pageTitle = 'Patient Registry';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    if ($deleteId > 0) {
        $del = $pdo->prepare("DELETE FROM patients WHERE id=?");
        $del->execute([$deleteId]);
        flash('success', 'Patient deleted successfully.');
    }
    redirect('/receptionist/view.php');
}

$rows = $pdo->query(
    "SELECT p.id, p.patient_code, p.full_name, p.phone, p.workflow_status, u.full_name doctor_name
     FROM patients p
     LEFT JOIN users u ON p.assigned_doctor_id = u.id
     ORDER BY p.id DESC"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary" href="<?= BASE_URL ?>/receptionist/create.php"><i class="fa-solid fa-plus me-2"></i>Create Patient</a>
</div>
<table>
    <tr><th>Code</th><th>Name</th><th>Phone</th><th>Doctor</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['patient_code']) ?></td>
            <td><?= e($row['full_name']) ?></td>
            <td><?= e($row['phone']) ?></td>
            <td><?= e($row['doctor_name']) ?></td>
            <td><?= e($row['workflow_status']) ?></td>
            <td class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="<?= BASE_URL ?>/receptionist/edit.php?id=<?= (int) $row['id'] ?>">View</a>
                <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/receptionist/edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm btn-delete"
                    data-id="<?= (int) $row['id'] ?>"
                    data-action="<?= BASE_URL ?>/receptionist/view.php"
                    data-message="Delete this patient and related records?"
                >Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
