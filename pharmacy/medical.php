<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['pharmacist', 'admin']);
$pageTitle = 'Medical (Medicines)';

// Backward-compatible schema adjustments.
$columns = $pdo->query("SHOW COLUMNS FROM medicines")->fetchAll();
$columnNames = array_map(static fn ($column) => $column['Field'], $columns);
if (!in_array('category', $columnNames, true)) {
    $pdo->exec("ALTER TABLE medicines ADD COLUMN category VARCHAR(100) NULL AFTER name");
}
if (!in_array('unit_name', $columnNames, true)) {
    $pdo->exec("ALTER TABLE medicines ADD COLUMN unit_name VARCHAR(30) NULL AFTER category");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_medical'])) {
        $insert = $pdo->prepare(
            "INSERT INTO medicines (name, category, unit_name, stock_quantity, unit_price, reorder_level)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insert->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['category'] ?? ''),
            trim($_POST['unit_name'] ?? ''),
            (int) ($_POST['stock_quantity'] ?? 0),
            (float) ($_POST['unit_price'] ?? 0),
            max(1, (int) ($_POST['reorder_level'] ?? 10)),
        ]);
        flash('success', 'Medicine created successfully.');
    }

    if (isset($_POST['edit_medical'])) {
        $update = $pdo->prepare(
            "UPDATE medicines
             SET name=?, category=?, unit_name=?, stock_quantity=?, unit_price=?, reorder_level=?
             WHERE id=?"
        );
        $update->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['category'] ?? ''),
            trim($_POST['unit_name'] ?? ''),
            (int) ($_POST['stock_quantity'] ?? 0),
            (float) ($_POST['unit_price'] ?? 0),
            max(1, (int) ($_POST['reorder_level'] ?? 10)),
            (int) ($_POST['id'] ?? 0),
        ]);
        flash('success', 'Medicine updated successfully.');
    }

    if (isset($_POST['delete_id'])) {
        $deleteId = (int) $_POST['delete_id'];
        if ($deleteId > 0) {
            $delete = $pdo->prepare("DELETE FROM medicines WHERE id=?");
            $delete->execute([$deleteId]);
            flash('success', 'Medicine deleted successfully.');
        }
    }
    redirect('/pharmacy/medical.php');
}

$rows = $pdo->query(
    "SELECT id, name, COALESCE(category,'General') category, COALESCE(unit_name,'unit') unit_name, stock_quantity, unit_price
     FROM medicines
     ORDER BY id DESC"
)->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createMedicalModal">
        <i class="fa-solid fa-plus me-2"></i>Create Medical
    </button>
</div>
<table>
    <tr><th>Name</th><th>Category</th><th>Unit</th><th>Stock</th><th>Price</th><th>Action</th></tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['name']) ?></td>
            <td><?= e($row['category']) ?></td>
            <td><?= e($row['unit_name']) ?></td>
            <td><?= (int) $row['stock_quantity'] ?></td>
            <td>$<?= number_format((float) $row['unit_price'], 2) ?></td>
            <td class="d-flex gap-2">
                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm btn-edit-medical"
                    data-id="<?= (int) $row['id'] ?>"
                    data-name="<?= e($row['name']) ?>"
                    data-category="<?= e($row['category']) ?>"
                    data-unit="<?= e($row['unit_name']) ?>"
                    data-stock="<?= (int) $row['stock_quantity'] ?>"
                    data-price="<?= (float) $row['unit_price'] ?>"
                >Edit</button>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm btn-delete"
                    data-id="<?= (int) $row['id'] ?>"
                    data-action="<?= BASE_URL ?>/pharmacy/medical.php"
                    data-message="Delete this medicine from inventory?"
                >Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<div class="modal fade" id="createMedicalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-pills me-2 text-primary"></i>Create Medical</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="create_medical" value="1">
                    <input class="form-control mb-2" name="name" placeholder="Medicine Name" required>
                    <input class="form-control mb-2" name="category" placeholder="Category" required>
                    <input class="form-control mb-2" name="unit_name" placeholder="Unit (tablets, ml, etc.)" required>
                    <input class="form-control mb-2" type="number" min="0" name="stock_quantity" placeholder="Stock Quantity" required>
                    <input class="form-control mb-2" type="number" min="0" step="0.01" name="unit_price" placeholder="Price" required>
                    <input class="form-control" type="number" min="1" name="reorder_level" value="10" placeholder="Reorder Level" required>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editMedicalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Edit Medical</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="edit_medical" value="1">
                    <input type="hidden" name="id" id="editMedicalId">
                    <input class="form-control mb-2" name="name" id="editMedicalName" required>
                    <input class="form-control mb-2" name="category" id="editMedicalCategory" required>
                    <input class="form-control mb-2" name="unit_name" id="editMedicalUnit" required>
                    <input class="form-control mb-2" type="number" min="0" name="stock_quantity" id="editMedicalStock" required>
                    <input class="form-control mb-2" type="number" min="0" step="0.01" name="unit_price" id="editMedicalPrice" required>
                    <input class="form-control" type="number" min="1" name="reorder_level" value="10" required>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
