<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin', 'receptionist']);
$pageTitle = 'Billing Dashboard';

$pending = $pdo->query("SELECT COUNT(*) total FROM patients WHERE workflow_status='billing_pending'")->fetch()['total'] ?? 0;
$paid = fetch_count($pdo, 'bills', "payment_status='paid'");
include __DIR__ . '/../includes/header.php';
?>
<section class="cards">
    <div class="card"><h3>Pending Bills</h3><p><?= (int) $pending ?></p></div>
    <div class="card"><h3>Paid Bills</h3><p><?= (int) $paid ?></p></div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
