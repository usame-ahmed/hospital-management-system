<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin']);
$pageTitle = 'Nurses Management';

// Backward-compatible schema upgrade for existing installations.
$columns = $pdo->query("SHOW COLUMNS FROM nurses")->fetchAll();
$columnNames = array_map(static fn ($column) => $column['Field'], $columns);

if (!in_array('full_name', $columnNames, true)) {
    $pdo->exec("ALTER TABLE nurses ADD COLUMN full_name VARCHAR(150) NULL AFTER id");
}
if (!in_array('phone', $columnNames, true)) {
    $pdo->exec("ALTER TABLE nurses ADD COLUMN phone VARCHAR(30) NULL AFTER full_name");
}
if (!in_array('address', $columnNames, true)) {
    $pdo->exec("ALTER TABLE nurses ADD COLUMN address TEXT NULL AFTER department");
}
if (!in_array('status', $columnNames, true)) {
    $pdo->exec("ALTER TABLE nurses ADD COLUMN status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active' AFTER address");
}

// If this is a legacy schema linked to users.user_id, sync names once.
$columns = $pdo->query("SHOW COLUMNS FROM nurses")->fetchAll();
$columnNames = array_map(static fn ($column) => $column['Field'], $columns);
if (in_array('user_id', $columnNames, true)) {
    $fkStmt = $pdo->query(
        "SELECT CONSTRAINT_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'nurses'
           AND COLUMN_NAME = 'user_id'
           AND REFERENCED_TABLE_NAME = 'users'
         LIMIT 1"
    );
    $fkRow = $fkStmt->fetch();
    if ($fkRow && !empty($fkRow['CONSTRAINT_NAME'])) {
        $constraintName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $fkRow['CONSTRAINT_NAME']);
        if ($constraintName !== '') {
            $pdo->exec("ALTER TABLE nurses DROP FOREIGN KEY `{$constraintName}`");
        }
    }
    $pdo->exec("ALTER TABLE nurses MODIFY COLUMN user_id INT NULL");

    $pdo->exec(
        "UPDATE nurses n
         LEFT JOIN users u ON n.user_id = u.id
         SET n.full_name = COALESCE(n.full_name, u.full_name, CONCAT('Nurse #', n.id)),
             n.phone = COALESCE(n.phone, 'N/A'),
             n.status = COALESCE(n.status, 'Active')
         WHERE n.full_name IS NULL OR n.phone IS NULL"
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_nurse'])) {
        $insert = $pdo->prepare(
            "INSERT INTO nurses (full_name, phone, department, address, status)
             VALUES (?, ?, ?, ?, ?)"
        );
        $insert->execute([
            trim($_POST['full_name'] ?? ''),
            trim($_POST['phone'] ?? ''),
            trim($_POST['department'] ?? ''),
            trim($_POST['address'] ?? ''),
            $_POST['status'] ?? 'Active',
        ]);
        flash('success', 'Nurse created successfully.');
    }

    if (isset($_POST['delete_id'])) {
        $deleteId = (int) $_POST['delete_id'];
        if ($deleteId > 0) {
            $del = $pdo->prepare("DELETE FROM nurses WHERE id=?");
            $del->execute([$deleteId]);
            flash('success', 'Nurse deleted successfully.');
        }
    }
    redirect('/admin/nurses.php');
}

$rows = $pdo->query("SELECT id, full_name, phone, department, status FROM nurses ORDER BY id DESC")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createNurseModal">
        <i class="fa-solid fa-plus me-2"></i>Create Nurse
    </button>
</div>
<table>
    <tr><th>Name</th><th>Phone</th><th>Department</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['full_name']) ?></td>
            <td><?= e($row['phone']) ?></td>
            <td><?= e($row['department']) ?></td>
            <td><?= e($row['status']) ?></td>
            <td>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm btn-delete"
                    data-id="<?= (int) $row['id'] ?>"
                    data-action="<?= BASE_URL ?>/admin/nurses.php"
                    data-message="Delete this nurse record?"
                >Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<div class="modal fade" id="createNurseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-user-nurse me-2 text-primary"></i>Create Nurse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="create_nurse" value="1">
                    <div class="mb-2">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Department</label>
                        <input class="form-control" name="department" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Address (optional)</label>
                        <textarea class="form-control" name="address" rows="2"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Nurse</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
