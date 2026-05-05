<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['pharmacist']);
$pageTitle = 'Pharmacy Dashboard';

$pending = fetch_count($pdo, 'prescriptions', "status='pending'");
$lowStock = fetch_count($pdo, 'medicines', 'stock_quantity < reorder_level');
include __DIR__ . '/../includes/header.php';
?>
<section class="cards">
    <div class="card"><h3>Pending Prescriptions</h3><p><?= (int) $pending ?></p></div>
    <div class="card"><h3>Low Stock Medicines</h3><p><?= (int) $lowStock ?></p></div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
