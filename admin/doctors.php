<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin']);
$pageTitle = 'Doctors';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_doctor'])) {
        try {
            $pdo->beginTransaction();
            $insert = $pdo->prepare(
                "INSERT INTO users (username, password_hash, role, full_name, gender, is_active)
                 VALUES (?, ?, 'doctor', ?, ?, ?)"
            );
            $insert->execute([
                trim($_POST['username'] ?? ''),
                password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
                trim($_POST['full_name'] ?? ''),
                $_POST['gender'] ?? 'Male',
                isset($_POST['is_active']) ? 1 : 0,
            ]);
            $userId = $pdo->lastInsertId();
            
            $specialization = $_POST['specialization'] ?? 'General Doctor';
            $insertDoc = $pdo->prepare("INSERT INTO doctors (user_id, specialization) VALUES (?, ?)");
            $insertDoc->execute([$userId, $specialization]);
            
            $pdo->commit();
            flash('success', 'Doctor created successfully.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'Failed to create doctor. Username may already exist.');
        }
    }

    if (isset($_POST['deactivate_id'])) {
        $deactivateId = (int) $_POST['deactivate_id'];
        if ($deactivateId > 0) {
            $deactivate = $pdo->prepare("UPDATE users SET is_active=0 WHERE id=? AND role='doctor'");
            $deactivate->execute([$deactivateId]);
            flash('success', 'Doctor deactivated successfully.');
        }
    }

    if (isset($_POST['reactivate_id'])) {
        $reactivateId = (int) $_POST['reactivate_id'];
        if ($reactivateId > 0) {
            $reactivate = $pdo->prepare("UPDATE users SET is_active=1 WHERE id=? AND role='doctor'");
            $reactivate->execute([$reactivateId]);
            flash('success', 'Doctor reactivated successfully.');
        }
    }

    if (isset($_POST['delete_id'])) {
        $deleteId = (int) $_POST['delete_id'];
        if ($deleteId > 0) {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE patients SET assigned_doctor_id=NULL WHERE assigned_doctor_id=?")->execute([$deleteId]);
                $pdo->prepare("DELETE FROM lab_tests WHERE doctor_id=?")->execute([$deleteId]);
                $pdo->prepare("DELETE FROM prescriptions WHERE doctor_id=?")->execute([$deleteId]);
                $pdo->prepare("DELETE FROM appointments WHERE doctor_id=?")->execute([$deleteId]);
                $pdo->prepare("DELETE FROM users WHERE id=? AND role='doctor'")->execute([$deleteId]);
                $pdo->commit();
                flash('success', 'Doctor permanently deleted.');
            } catch (Throwable $exception) {
                $pdo->rollBack();
                flash('danger', 'Unable to delete doctor permanently.');
            }
        }
    }
    redirect('/admin/doctors.php');
}

$activeDoctors = $pdo->query("SELECT u.id, u.full_name, u.username, u.is_active, u.created_at, d.specialization FROM users u LEFT JOIN doctors d ON u.id=d.user_id WHERE u.role='doctor' AND u.is_active=1 ORDER BY u.id DESC")->fetchAll();
$deactivatedDoctors = $pdo->query("SELECT u.id, u.full_name, u.username, u.is_active, u.created_at, d.specialization FROM users u LEFT JOIN doctors d ON u.id=d.user_id WHERE u.role='doctor' AND u.is_active=0 ORDER BY u.id DESC")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createDoctorModal">
        <i class="fa-solid fa-plus me-2"></i>Create Doctor
    </button>
</div>

<h5 class="mb-2">Active Doctors</h5>
<table>
    <tr><th>Name</th><th>Department</th><th>Username</th><th>Status</th><th>Created</th><th>Action</th></tr>
    <?php foreach ($activeDoctors as $row): ?>
        <tr>
            <td><?= e($row['full_name']) ?></td>
            <td><span class="badge bg-light text-dark border"><i class="fa-solid fa-stethoscope me-1 text-primary"></i><?= e($row['specialization'] ?? 'General Doctor') ?></span></td>
            <td><?= e($row['username']) ?></td>
            <td><span class="status-badge status-active">Active</span></td>
            <td><?= e($row['created_at']) ?></td>
            <td class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="<?= BASE_URL ?>/admin/edit.php?id=<?= (int) $row['id'] ?>">View</a>
                <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/admin/edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                <?php if ((int) $row['id'] !== (int) current_user()['id']): ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="deactivate_id" value="<?= (int) $row['id'] ?>">
                        <button class="btn btn-outline-warning btn-sm" type="submit">
                            <i class="fa-solid fa-ban me-1"></i>Deactivate
                        </button>
                    </form>
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm btn-delete"
                        data-id="<?= (int) $row['id'] ?>"
                        data-action="<?= BASE_URL ?>/admin/doctors.php"
                        data-message="Permanently delete this doctor and related doctor records?"
                    ><i class="fa-solid fa-trash me-1"></i>Delete</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<h5 class="mb-2 mt-4">Deactivated Doctors</h5>
<table>
    <tr><th>Name</th><th>Department</th><th>Username</th><th>Status</th><th>Created</th><th>Action</th></tr>
    <?php foreach ($deactivatedDoctors as $row): ?>
        <tr>
            <td><?= e($row['full_name']) ?></td>
            <td><span class="badge bg-light text-dark border"><i class="fa-solid fa-stethoscope me-1 text-primary"></i><?= e($row['specialization'] ?? 'General Doctor') ?></span></td>
            <td><?= e($row['username']) ?></td>
            <td><span class="status-badge status-inactive">Deactivated</span></td>
            <td><?= e($row['created_at']) ?></td>
            <td class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="<?= BASE_URL ?>/admin/edit.php?id=<?= (int) $row['id'] ?>">View</a>
                <form method="post" class="d-inline">
                    <input type="hidden" name="reactivate_id" value="<?= (int) $row['id'] ?>">
                    <button class="btn btn-outline-success btn-sm" type="submit">
                        <i class="fa-solid fa-rotate-right me-1"></i>Reactivate
                    </button>
                </form>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm btn-delete"
                    data-id="<?= (int) $row['id'] ?>"
                    data-action="<?= BASE_URL ?>/admin/doctors.php"
                    data-message="Permanently delete this deactivated doctor?"
                ><i class="fa-solid fa-trash me-1"></i>Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<div class="modal fade" id="createDoctorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-user-doctor me-2 text-primary"></i>Create Doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="create_doctor" value="1">
                    <div class="mb-2">
                        <label class="form-label">Full Name</label>
                        <input class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Department / Specialization</label>
                        <select class="form-select" name="specialization" required>
                            <option value="" disabled selected>Select Department...</option>
                            <option value="Surgeon Doctor">Surgeon Doctor</option>
                            <option value="Pediatric Doctor (Children's Doctor)">Pediatric Doctor (Children's Doctor)</option>
                            <option value="Orthopedic Doctor (Bone Doctor)">Orthopedic Doctor (Bone Doctor)</option>
                            <option value="Dermatologist (Skin Doctor)">Dermatologist (Skin Doctor)</option>
                            <option value="General Doctor">General Doctor</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Username</label>
                        <input class="form-control" name="username" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="doctorIsActive" checked>
                        <label class="form-check-label" for="doctorIsActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
