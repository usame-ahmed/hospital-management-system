<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin']);
$pageTitle = 'All Users';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    if ($deleteId > 0) {
        $del = $pdo->prepare("UPDATE users SET is_active=0 WHERE id=?");
        $del->execute([$deleteId]);
        flash('success', 'User deactivated successfully.');
    }
    redirect('/admin/users.php');
}

$rows = $pdo->query("SELECT id, full_name, username, role, is_active FROM users ORDER BY id DESC")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary" href="<?= BASE_URL ?>/admin/create.php"><i class="fa-solid fa-plus me-2"></i>Create User</a>
</div>
<table>
    <tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['full_name']) ?></td>
            <td><?= e($row['username']) ?></td>
            <td><?= e($row['role']) ?></td>
            <td><?= (int) $row['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
            <td class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="<?= BASE_URL ?>/admin/edit.php?id=<?= (int) $row['id'] ?>">View</a>
                <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/admin/edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                <?php if ((int) $row['id'] !== (int) current_user()['id']): ?>
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm btn-delete"
                        data-id="<?= (int) $row['id'] ?>"
                        data-action="<?= BASE_URL ?>/admin/users.php"
                        data-message="Deactivate this user account?"
                    >Delete</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
