<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['doctor']);
$pageTitle = 'Assigned Patients';
$doctorId = (int) current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    if ($deleteId > 0) {
        $del = $pdo->prepare("UPDATE patients SET assigned_doctor_id=NULL, workflow_status='registered' WHERE id=? AND assigned_doctor_id=?");
        $del->execute([$deleteId, $doctorId]);
        flash('success', 'Patient unassigned from your list.');
    }
    redirect('/doctor/view.php');
}

$stmt = $pdo->prepare(
    "SELECT id, patient_code, full_name, workflow_status
     FROM patients
     WHERE assigned_doctor_id=? AND workflow_status NOT IN ('discharged', 'completed')
     ORDER BY id DESC"
);
$stmt->execute([$doctorId]);
$patients = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-outline-primary me-2" href="<?= BASE_URL ?>/doctor/lab_requests.php"><i class="fa-solid fa-vials me-2"></i>Lab Requests</a>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/doctor/create.php"><i class="fa-solid fa-plus me-2"></i>Create Diagnosis</a>
</div>
<table>
    <tr><th>Code</th><th>Name</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($patients as $patient): ?>
        <tr>
            <td><?= e($patient['patient_code']) ?></td>
            <td><?= e($patient['full_name']) ?></td>
            <td><?= e($patient['workflow_status']) ?></td>
            <td class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="<?= BASE_URL ?>/doctor/create.php?patient_id=<?= (int) $patient['id'] ?>">View</a>
                <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/doctor/create.php?patient_id=<?= (int) $patient['id'] ?>">Edit</a>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm btn-delete"
                    data-id="<?= (int) $patient['id'] ?>"
                    data-action="<?= BASE_URL ?>/doctor/view.php"
                    data-message="Remove this patient assignment from your queue?"
                >Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
