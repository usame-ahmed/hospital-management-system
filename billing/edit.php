<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin', 'receptionist']);
$pageTitle = 'Invoice Detail';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

// Backward-compatible schema update.
$billColumns = $pdo->query("SHOW COLUMNS FROM bills")->fetchAll();
$billColumnNames = array_map(static fn ($column) => $column['Field'], $billColumns);
if (!in_array('payment_method', $billColumnNames, true)) {
    $pdo->exec("ALTER TABLE bills ADD COLUMN payment_method VARCHAR(30) NOT NULL DEFAULT 'cash' AFTER payment_status");
}

$stmt = $pdo->prepare("SELECT b.*, p.patient_code, p.full_name FROM bills b JOIN patients p ON b.patient_id=p.id WHERE b.id=?");
$stmt->execute([$id]);
$bill = $stmt->fetch();
if (!$bill) {
    die('Invoice not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentStatus = $_POST['payment_status'];
    $pdo->beginTransaction();
    $update = $pdo->prepare("UPDATE bills SET payment_status=? WHERE id=?");
    $update->execute([$paymentStatus, $id]);

    if ($paymentStatus === 'paid') {
        $patientUpdate = $pdo->prepare("UPDATE patients SET workflow_status='discharged', assigned_doctor_id=NULL WHERE id=?");
        $patientUpdate->execute([(int) $bill['patient_id']]);
    }
    $pdo->commit();
    flash('success', 'Payment status updated.');
    redirect('/billing/view.php');
}

include __DIR__ . '/../includes/header.php';
?>
<div class="invoice">
    <h3>Invoice INV-<?= (int) $bill['id'] ?></h3>
    <p>Patient: <?= e($bill['patient_code']) ?> - <?= e($bill['full_name']) ?></p>
    <p>Consultation: $<?= number_format((float) $bill['consultation_fee'], 2) ?></p>
    <p>Lab: $<?= number_format((float) $bill['lab_fee'], 2) ?></p>
    <p>Pharmacy: $<?= number_format((float) $bill['pharmacy_fee'], 2) ?></p>
    <p>Room: $<?= number_format((float) $bill['room_fee'], 2) ?></p>
    <p>Payment Method: <?= e($bill['payment_method'] ?? 'cash') ?></p>
    <p><strong>Total: $<?= number_format((float) $bill['total_amount'], 2) ?></strong></p>
    <button class="btn btn-light" onclick="window.print()">Print Invoice</button>
</div>
<form method="post" class="form-grid">
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <select name="payment_status">
        <option value="unpaid" <?= $bill['payment_status'] === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
        <option value="paid" <?= $bill['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
    </select>
    <button class="btn btn-primary" type="submit">Update Payment</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
