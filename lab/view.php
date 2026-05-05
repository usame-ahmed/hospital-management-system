<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['lab_technician']);
$pageTitle = 'Lab Test Requests';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    if ($deleteId > 0) {
        $del = $pdo->prepare("DELETE FROM lab_tests WHERE id=?");
        $del->execute([$deleteId]);
        flash('success', 'Lab request deleted.');
    }
    redirect('/lab/view.php');
}

$rows = $pdo->query(
    "SELECT lt.id, p.patient_code, p.full_name, lt.test_name, lt.status
     FROM lab_tests lt
     JOIN patients p ON lt.patient_id=p.id
     ORDER BY lt.id DESC"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary" href="<?= BASE_URL ?>/lab/create.php"><i class="fa-solid fa-plus me-2"></i>Create Lab Result</a>
</div>
<table>
    <tr><th>Patient</th><th>Test</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['patient_code']) ?> - <?= e($row['full_name']) ?></td>
            <td><?= e($row['test_name']) ?></td>
            <td><?= e($row['status']) ?></td>
            <td class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="<?= BASE_URL ?>/lab/create.php?id=<?= (int) $row['id'] ?>">View</a>
                <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/lab/edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm btn-delete"
                    data-id="<?= (int) $row['id'] ?>"
                    data-action="<?= BASE_URL ?>/lab/view.php"
                    data-message="Delete this lab test request?"
                >Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
