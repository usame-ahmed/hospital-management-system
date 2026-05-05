<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/workflow.php';
require_role(['admin', 'receptionist']);
$pageTitle = 'Admissions Dashboard';

$availableRooms = fetch_count($pdo, 'rooms', "status='available'");
$occupiedRooms = fetch_count($pdo, 'rooms', "status='occupied'");
include __DIR__ . '/../includes/header.php';
?>
<section class="cards">
    <div class="card"><h3>Available Rooms</h3><p><?= (int) $availableRooms ?></p></div>
    <div class="card"><h3>Occupied Rooms</h3><p><?= (int) $occupiedRooms ?></p></div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
