<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin']);
$pageTitle = 'Edit User';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$userRow = $stmt->fetch();
if (!$userRow) {
    die('User not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update = $pdo->prepare("UPDATE users SET full_name=?, role=?, is_active=? WHERE id=?");
    $update->execute([trim($_POST['full_name']), $_POST['role'], (int) $_POST['is_active'], $id]);
    flash('success', 'User updated.');
    redirect('/admin/view.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <input name="full_name" value="<?= e($userRow['full_name']) ?>" required>
    <select name="role" required>
        <?php foreach (['admin','receptionist','doctor','lab_technician','pharmacist'] as $role): ?>
            <option value="<?= $role ?>" <?= $userRow['role'] === $role ? 'selected' : '' ?>><?= e($role) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="is_active">
        <option value="1" <?= (int) $userRow['is_active'] === 1 ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= (int) $userRow['is_active'] === 0 ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button class="btn btn-primary" type="submit">Save</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
