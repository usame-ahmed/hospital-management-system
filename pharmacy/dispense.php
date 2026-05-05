<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['pharmacist', 'admin']);
$pageTitle = 'Dispense Medicine';

// Ensure inventory extension columns exist for older DBs.
$columns = $pdo->query("SHOW COLUMNS FROM medicines")->fetchAll();
$columnNames = array_map(static fn ($column) => $column['Field'], $columns);
if (!in_array('category', $columnNames, true)) {
    $pdo->exec("ALTER TABLE medicines ADD COLUMN category VARCHAR(100) NULL AFTER name");
}
if (!in_array('unit_name', $columnNames, true)) {
    $pdo->exec("ALTER TABLE medicines ADD COLUMN unit_name VARCHAR(30) NULL AFTER category");
}

// Ensure dispense log table exists for older DBs.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $medicineId = (int) ($_POST['medicine_id'] ?? 0);
    $qty = (int) ($_POST['quantity'] ?? 0);

    if ($patientId <= 0 || $medicineId <= 0 || $qty <= 0) {
        flash('danger', 'Please select valid patient, medicine, and quantity.');
        redirect('/pharmacy/dispense.php');
    }

    $pdo->beginTransaction();
    try {
        $medicineStmt = $pdo->prepare("SELECT id, name, stock_quantity FROM medicines WHERE id=? FOR UPDATE");
        $medicineStmt->execute([$medicineId]);
        $medicine = $medicineStmt->fetch();
        if (!$medicine || (int) $medicine['stock_quantity'] < $qty) {
            throw new RuntimeException('Insufficient stock for selected medicine.');
        }

        $pdo->prepare("UPDATE medicines SET stock_quantity=stock_quantity-? WHERE id=?")->execute([$qty, $medicineId]);

        $dispense = $pdo->prepare(
            "INSERT INTO pharmacy_dispenses (patient_id, medicine_id, quantity, dispensed_by, dispensed_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $dispense->execute([$patientId, $medicineId, $qty, (int) current_user()['id']]);

        $prescription = $pdo->prepare(
            "UPDATE prescriptions
             SET status='issued', pharmacist_id=?, issued_quantity=?, issued_at=NOW()
             WHERE patient_id=? AND medicine_name=? AND status='pending'
             ORDER BY id DESC LIMIT 1"
        );
        $prescription->execute([(int) current_user()['id'], $qty, $patientId, $medicine['name']]);

        $pendingCountStmt = $pdo->prepare("SELECT COUNT(*) total FROM prescriptions WHERE patient_id=? AND status='pending'");
        $pendingCountStmt->execute([$patientId]);
        $pendingCount = (int) ($pendingCountStmt->fetch()['total'] ?? 0);
        if ($pendingCount === 0) {
            $pdo->prepare("UPDATE patients SET workflow_status='billing_pending' WHERE id=?")->execute([$patientId]);
        } else {
            $pdo->prepare("UPDATE patients SET workflow_status='pharmacy_pending' WHERE id=?")->execute([$patientId]);
        }
        $pdo->commit();

        flash('success', 'Medicine dispensed and stock updated.');
        redirect('/pharmacy/dispense.php');
    } catch (Throwable $exception) {
        $pdo->rollBack();
        flash('danger', $exception->getMessage());
        redirect('/pharmacy/dispense.php');
    }
}

$patients = $pdo->query("SELECT id, patient_code, full_name FROM patients ORDER BY id DESC")->fetchAll();
$medicines = $pdo->query("SELECT id, name, COALESCE(unit_name,'unit') unit_name, stock_quantity FROM medicines ORDER BY name")->fetchAll();
$dispenses = $pdo->query(
    "SELECT d.id, p.patient_code, p.full_name, m.name medicine_name, d.quantity, d.dispensed_at
     FROM pharmacy_dispenses d
     JOIN patients p ON d.patient_id=p.id
     JOIN medicines m ON d.medicine_id=m.id
     ORDER BY d.id DESC"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid mb-3">
    <select name="patient_id" required>
        <option value="">Select Patient</option>
        <?php foreach ($patients as $patient): ?>
            <option value="<?= (int) $patient['id'] ?>"><?= e($patient['patient_code']) ?> - <?= e($patient['full_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="medicine_id" id="medicineSelect" required>
        <option value="">Select Medicine</option>
        <?php foreach ($medicines as $medicine): ?>
            <option value="<?= (int) $medicine['id'] ?>" data-stock="<?= (int) $medicine['stock_quantity'] ?>">
                <?= e($medicine['name']) ?> (<?= e($medicine['unit_name']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <input type="text" id="availableStock" value="Available stock: -" disabled>
    <input type="number" min="1" name="quantity" placeholder="Quantity" required>
    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-hand-holding-medical me-2"></i>Dispense</button>
</form>

<table>
    <tr><th>Patient</th><th>Medicine</th><th>Quantity</th><th>Dispensed At</th></tr>
    <?php foreach ($dispenses as $dispense): ?>
        <tr>
            <td><?= e($dispense['patient_code']) ?> - <?= e($dispense['full_name']) ?></td>
            <td><?= e($dispense['medicine_name']) ?></td>
            <td><?= (int) $dispense['quantity'] ?></td>
            <td><?= e($dispense['dispensed_at']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const medicineSelect = document.getElementById('medicineSelect');
    const availableStock = document.getElementById('availableStock');
    if (!medicineSelect || !availableStock) return;
    medicineSelect.addEventListener('change', () => {
        const option = medicineSelect.options[medicineSelect.selectedIndex];
        const stock = option?.dataset?.stock ?? '-';
        availableStock.value = `Available stock: ${stock}`;
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
