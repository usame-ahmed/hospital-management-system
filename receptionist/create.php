<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['receptionist']);
$pageTitle = 'Register Patient';

$doctors = $pdo->query("SELECT id, full_name FROM users WHERE role='doctor' AND is_active=1 ORDER BY full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare(
        'INSERT INTO patients (patient_code, full_name, gender, dob, phone, address, assigned_doctor_id, workflow_status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $patientCode = 'PT-' . time();
    $stmt->execute([
        $patientCode,
        trim($_POST['full_name']),
        $_POST['gender'],
        $_POST['dob'],
        trim($_POST['phone']),
        trim($_POST['address']),
        (int) $_POST['doctor_id'],
        'assigned',
        (int) current_user()['id'],
    ]);
    flash('success', 'Patient registered and doctor assigned.');
    redirect('/receptionist/view.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <input name="full_name" placeholder="Patient name" required>
    <select name="gender" required><option value="Male">Male</option><option value="Female">Female</option></select>
    <input type="date" name="dob" required>
    <input name="phone" placeholder="Phone" required>
    <textarea name="address" placeholder="Address" required></textarea>
    <select name="doctor_id" required>
        <option value="">Assign Doctor</option>
        <?php foreach ($doctors as $doctor): ?>
            <option value="<?= (int) $doctor['id'] ?>"><?= e($doctor['full_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Register Patient</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
