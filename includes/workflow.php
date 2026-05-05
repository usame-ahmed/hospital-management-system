<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function fetch_count(PDO $pdo, string $table, string $where = '1=1'): int
{
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM {$table} WHERE {$where}");
    $row = $stmt->fetch();
    return (int) ($row['total'] ?? 0);
}

function workflow_statuses(): array
{
    return [
        'registered',
        'assigned',
        'diagnosed',
        'lab_pending',
        'lab_completed',
        'pharmacy_pending',
        'pharmacy_completed',
        'billing_pending',
        'discharged',
    ];
}
