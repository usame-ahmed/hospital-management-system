<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['lab_technician']);
$pageTitle = 'Lab Dashboard';

$pending = fetch_count($pdo, 'lab_tests', "status='pending'");
$completed = fetch_count($pdo, 'lab_tests', "status='completed'");
include __DIR__ . '/../includes/header.php';
?>
<section class="cards">
    <div class="card"><h3>Pending Tests</h3><p><?= (int) $pending ?></p></div>
    <div class="card"><h3>Completed Tests</h3><p><?= (int) $completed ?></p></div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
