<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin']);
$pageTitle = 'Create User';
$selectedRole = $_GET['role'] ?? 'admin';
$allowedRoles = ['admin', 'receptionist', 'doctor', 'lab_technician', 'pharmacist'];
if (!in_array($selectedRole, $allowedRoles, true)) {
    $selectedRole = 'admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, full_name, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([
        trim($_POST['username']),
        password_hash($_POST['password'], PASSWORD_DEFAULT),
        $_POST['role'],
        trim($_POST['full_name']),
    ]);
    flash('success', 'User created.');
    redirect('/admin/users.php');
}

include __DIR__ . '/../includes/header.php';
?>
<form method="post" class="form-grid">
    <input name="full_name" placeholder="Full name" required>
    <input name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <select name="role" required>
        <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Admin</option>
        <option value="receptionist" <?= $selectedRole === 'receptionist' ? 'selected' : '' ?>>Receptionist</option>
        <option value="doctor" <?= $selectedRole === 'doctor' ? 'selected' : '' ?>>Doctor</option>
        <option value="lab_technician" <?= $selectedRole === 'lab_technician' ? 'selected' : '' ?>>Lab Technician</option>
        <option value="pharmacist" <?= $selectedRole === 'pharmacist' ? 'selected' : '' ?>>Pharmacist</option>
    </select>
    <button class="btn btn-primary" type="submit">Create User</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
