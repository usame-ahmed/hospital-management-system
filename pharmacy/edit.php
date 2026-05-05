<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['pharmacist']);
$pageTitle = 'Update Medicine Stock';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM medicines WHERE id=?");
$stmt->execute([$id]);
$medicine = $stmt->fetch();
if (!$medicine) {
    die('Medicine not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update = $pdo->prepare("UPDATE medicines SET stock_quantity=?, reorder_level=? WHERE id=?");
    $update->execute([(int) $_POST['stock_quantity'], (int) $_POST['reorder_level'], $id]);
    flash('success', 'Stock updated.');
    redirect('/pharmacy/dashboard.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <input value="<?= e($medicine['name']) ?>" disabled>
    <input name="stock_quantity" type="number" min="0" value="<?= (int) $medicine['stock_quantity'] ?>" required>
    <input name="reorder_level" type="number" min="0" value="<?= (int) $medicine['reorder_level'] ?>" required>
    <button class="btn btn-primary" type="submit">Save</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
