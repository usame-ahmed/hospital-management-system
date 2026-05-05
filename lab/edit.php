<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['lab_technician']);
$pageTitle = 'Edit Lab Result';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM lab_tests WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    die('Lab result not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update = $pdo->prepare("UPDATE lab_tests SET result_text=? WHERE id=?");
    $update->execute([trim($_POST['result_text']), $id]);
    flash('success', 'Lab result updated.');
    redirect('/lab/dashboard.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <textarea name="result_text" required><?= e($row['result_text']) ?></textarea>
    <button class="btn btn-primary" type="submit">Update</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
