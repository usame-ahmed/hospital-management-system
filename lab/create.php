<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['lab_technician']);
$pageTitle = 'Upload Lab Result';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

// Ensure fee column exists for older databases.
$columns = $pdo->query("SHOW COLUMNS FROM lab_tests")->fetchAll();
$columnNames = array_map(static fn ($column) => $column['Field'], $columns);
if (!in_array('lab_fee', $columnNames, true)) {
    $pdo->exec("ALTER TABLE lab_tests ADD COLUMN lab_fee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER result_text");
}

$pendingTests = $pdo->query(
    "SELECT lt.id, lt.test_name, p.patient_code, p.full_name
     FROM lab_tests lt
     JOIN patients p ON lt.patient_id=p.id
     WHERE lt.status='pending'
     ORDER BY lt.id DESC"
)->fetchAll();

$test = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM lab_tests WHERE id=?");
    $stmt->execute([$id]);
    $test = $stmt->fetch();
    if (!$test) {
        die('Test request not found.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$test) {
        flash('danger', 'Please select a valid pending test.');
        redirect('/lab/create.php');
    }
    $pdo->beginTransaction();
    $update = $pdo->prepare("UPDATE lab_tests SET result_text=?, lab_fee=?, status='completed', technician_id=?, completed_at=NOW() WHERE id=?");
    $update->execute([trim($_POST['result_text']), (float) ($_POST['lab_fee'] ?? 0), (int) current_user()['id'], $id]);

    $patientUpdate = $pdo->prepare("UPDATE patients SET workflow_status='lab_completed' WHERE id=?");
    $patientUpdate->execute([(int) $test['patient_id']]);
    $pdo->commit();
    flash('success', 'Result uploaded successfully.');
    redirect('/lab/view.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <?php if ($test): ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <input value="<?= e($test['test_name']) ?>" disabled>
    <?php else: ?>
        <select name="id" required>
            <option value="">Select pending test</option>
            <?php foreach ($pendingTests as $pendingTest): ?>
                <option value="<?= (int) $pendingTest['id'] ?>">
                    <?= e($pendingTest['patient_code']) ?> - <?= e($pendingTest['full_name']) ?> (<?= e($pendingTest['test_name']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <textarea name="result_text" placeholder="Lab result details" required></textarea>
    <input name="lab_fee" type="number" min="0" step="0.01" placeholder="Lab fee" required>
    <button class="btn btn-primary" type="submit">Submit Result</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
