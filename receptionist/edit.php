<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['receptionist']);
$pageTitle = 'Edit Patient';
$id = (int) ($_GET['id'] ?? 0);

$patientStmt = $pdo->prepare('SELECT * FROM patients WHERE id=?');
$patientStmt->execute([$id]);
$patient = $patientStmt->fetch();
if (!$patient) {
    die('Patient not found.');
}

$doctors = $pdo->query("SELECT id, full_name FROM users WHERE role='doctor' AND is_active=1 ORDER BY full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('UPDATE patients SET full_name=?, phone=?, address=?, assigned_doctor_id=? WHERE id=?');
    $stmt->execute([
        trim($_POST['full_name']),
        trim($_POST['phone']),
        trim($_POST['address']),
        (int) $_POST['doctor_id'],
        $id,
    ]);
    flash('success', 'Patient record updated.');
    redirect('/receptionist/view.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <input name="full_name" value="<?= e($patient['full_name']) ?>" required>
    <input name="phone" value="<?= e($patient['phone']) ?>" required>
    <textarea name="address" required><?= e($patient['address']) ?></textarea>
    <select name="doctor_id" required>
        <?php foreach ($doctors as $doctor): ?>
            <option value="<?= (int) $doctor['id'] ?>" <?= (int) $doctor['id'] === (int) $patient['assigned_doctor_id'] ? 'selected' : '' ?>>
                <?= e($doctor['full_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Save</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
