<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['doctor']);
$pageTitle = 'Edit Diagnosis Note';

$id = (int) ($_GET['id'] ?? 0);
$doctorId = (int) current_user()['id'];
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id=? AND doctor_id=?");
$stmt->execute([$id, $doctorId]);
$row = $stmt->fetch();
if (!$row) {
    die('Record not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update = $pdo->prepare("UPDATE appointments SET diagnosis=?, notes=? WHERE id=? AND doctor_id=?");
    $update->execute([trim($_POST['diagnosis']), trim($_POST['notes']), $id, $doctorId]);
    flash('success', 'Diagnosis updated.');
    redirect('/doctor/dashboard.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <textarea name="diagnosis" required><?= e($row['diagnosis']) ?></textarea>
    <textarea name="notes"><?= e($row['notes']) ?></textarea>
    <button class="btn btn-primary" type="submit">Update</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
