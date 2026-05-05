<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin', 'receptionist']);
$pageTitle = 'Invoices';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    if ($deleteId > 0) {
        $del = $pdo->prepare("DELETE FROM bills WHERE id=?");
        $del->execute([$deleteId]);
        flash('success', 'Invoice deleted.');
    }
    redirect('/billing/view.php');
}

$rows = $pdo->query(
    "SELECT b.id, p.patient_code, p.full_name, b.total_amount, b.payment_status, b.created_at
     FROM bills b JOIN patients p ON b.patient_id=p.id ORDER BY b.id DESC"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary" href="<?= BASE_URL ?>/billing/create.php"><i class="fa-solid fa-plus me-2"></i>Create Invoice</a>
</div>
<table>
    <tr><th>Invoice #</th><th>Patient</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td>INV-<?= (int) $row['id'] ?></td>
            <td><?= e($row['patient_code']) ?> - <?= e($row['full_name']) ?></td>
            <td>$<?= number_format((float) $row['total_amount'], 2) ?></td>
            <td><?= e($row['payment_status']) ?></td>
            <td><?= e($row['created_at']) ?></td>
            <td class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="<?= BASE_URL ?>/billing/edit.php?id=<?= (int) $row['id'] ?>">View</a>
                <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/billing/edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm btn-delete"
                    data-id="<?= (int) $row['id'] ?>"
                    data-action="<?= BASE_URL ?>/billing/view.php"
                    data-message="Delete this invoice permanently?"
                >Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
