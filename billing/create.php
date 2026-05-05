<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin', 'receptionist']);
$pageTitle = 'Generate Bill';

$patients = $pdo->query("SELECT id, patient_code, full_name FROM patients WHERE workflow_status='billing_pending' ORDER BY id DESC")->fetchAll();

const CONSULTATION_FEE_DEFAULT = 50.00;

function calculateBillBreakdown(PDO $pdo, int $patientId): array
{
    $consultationCountStmt = $pdo->prepare("SELECT COUNT(*) total FROM appointments WHERE patient_id=?");
    $consultationCountStmt->execute([$patientId]);
    $consultationCount = (int) ($consultationCountStmt->fetch()['total'] ?? 0);
    $consultation = $consultationCount > 0 ? $consultationCount * CONSULTATION_FEE_DEFAULT : CONSULTATION_FEE_DEFAULT;

    // Ensure lab fee column exists for older databases.
    $labColumns = $pdo->query("SHOW COLUMNS FROM lab_tests")->fetchAll();
    $labColumnNames = array_map(static fn ($column) => $column['Field'], $labColumns);
    if (!in_array('lab_fee', $labColumnNames, true)) {
        $pdo->exec("ALTER TABLE lab_tests ADD COLUMN lab_fee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER result_text");
    }
    $labSumStmt = $pdo->prepare("SELECT COALESCE(SUM(lab_fee),0) total FROM lab_tests WHERE patient_id=? AND status='completed'");
    $labSumStmt->execute([$patientId]);
    $lab = (float) ($labSumStmt->fetch()['total'] ?? 0);

    $pharmacyStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(d.quantity * m.unit_price),0) total
         FROM pharmacy_dispenses d
         JOIN medicines m ON d.medicine_id=m.id
         WHERE d.patient_id=?"
    );
    $pharmacyStmt->execute([$patientId]);
    $pharmacy = (float) ($pharmacyStmt->fetch()['total'] ?? 0);

    $roomStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(
            (DATEDIFF(COALESCE(a.discharge_date, NOW()), a.admission_date) + 1) * r.daily_charge
        ),0) total
         FROM admissions a
         JOIN rooms r ON a.room_id=r.id
         WHERE a.patient_id=?"
    );
    $roomStmt->execute([$patientId]);
    $room = (float) ($roomStmt->fetch()['total'] ?? 0);

    $total = $consultation + $lab + $pharmacy + $room;
    return [
        'consultation_fee' => $consultation,
        'lab_fee' => $lab,
        'pharmacy_fee' => $pharmacy,
        'room_fee' => $room,
        'total_amount' => $total,
    ];
}

// Ensure dispense table exists for environments that have not migrated.
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS pharmacy_dispenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        medicine_id INT NOT NULL,
        quantity INT NOT NULL,
        dispensed_by INT NOT NULL,
        dispensed_at DATETIME NOT NULL,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (medicine_id) REFERENCES medicines(id),
        FOREIGN KEY (dispensed_by) REFERENCES users(id)
    )"
);

// Ensure payment method column exists for older DBs.
$billColumns = $pdo->query("SHOW COLUMNS FROM bills")->fetchAll();
$billColumnNames = array_map(static fn ($column) => $column['Field'], $billColumns);
if (!in_array('payment_method', $billColumnNames, true)) {
    $pdo->exec("ALTER TABLE bills ADD COLUMN payment_method VARCHAR(30) NOT NULL DEFAULT 'cash' AFTER payment_status");
}

$selectedPatientId = (int) ($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$selectedPaymentMethod = $_POST['payment_method'] ?? 'cash';
$preview = null;
if ($selectedPatientId > 0) {
    $preview = calculateBillBreakdown($pdo, $selectedPatientId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int) $_POST['patient_id'];
    $paymentMethod = trim($_POST['payment_method'] ?? 'cash');
    $allowedMethods = ['cash', 'card', 'online', 'insurance'];
    if (!in_array($paymentMethod, $allowedMethods, true)) {
        $paymentMethod = 'cash';
    }
    $breakdown = calculateBillBreakdown($pdo, $patientId);

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "INSERT INTO bills (patient_id, consultation_fee, lab_fee, pharmacy_fee, room_fee, total_amount, payment_status, payment_method, created_by)
         VALUES (?, ?, ?, ?, ?, ?, 'unpaid', ?, ?)"
    );
    $stmt->execute([
        $patientId,
        $breakdown['consultation_fee'],
        $breakdown['lab_fee'],
        $breakdown['pharmacy_fee'],
        $breakdown['room_fee'],
        $breakdown['total_amount'],
        $paymentMethod,
        (int) current_user()['id'],
    ]);

    $update = $pdo->prepare("UPDATE patients SET workflow_status='billing_pending' WHERE id=?");
    $update->execute([$patientId]);
    $pdo->commit();
    flash('success', 'Bill generated automatically. Mark as paid to discharge patient.');
    redirect('/billing/view.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <select name="patient_id" required onchange="window.location='<?= BASE_URL ?>/billing/create.php?patient_id='+this.value">
        <option value="">Select patient</option>
        <?php foreach ($patients as $patient): ?>
            <option value="<?= (int) $patient['id'] ?>" <?= (int) $patient['id'] === $selectedPatientId ? 'selected' : '' ?>>
                <?= e($patient['patient_code']) ?> - <?= e($patient['full_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input value="Consultation: $<?= number_format((float) ($preview['consultation_fee'] ?? 0), 2) ?>" disabled>
    <input value="Lab: $<?= number_format((float) ($preview['lab_fee'] ?? 0), 2) ?>" disabled>
    <input value="Pharmacy: $<?= number_format((float) ($preview['pharmacy_fee'] ?? 0), 2) ?>" disabled>
    <input value="Room: $<?= number_format((float) ($preview['room_fee'] ?? 0), 2) ?>" disabled>
    <input value="Total: $<?= number_format((float) ($preview['total_amount'] ?? 0), 2) ?>" disabled>
    <select name="payment_method" required>
        <option value="cash" <?= $selectedPaymentMethod === 'cash' ? 'selected' : '' ?>>Cash</option>
        <option value="card" <?= $selectedPaymentMethod === 'card' ? 'selected' : '' ?>>Card</option>
        <option value="online" <?= $selectedPaymentMethod === 'online' ? 'selected' : '' ?>>Online Transfer</option>
        <option value="insurance" <?= $selectedPaymentMethod === 'insurance' ? 'selected' : '' ?>>Insurance</option>
    </select>
    <button class="btn btn-primary" type="submit">Generate Invoice</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
