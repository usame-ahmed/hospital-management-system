<?php
require 'c:/xampp7/htdocs/HMS_M/config/database.php';
try {
    $pdo->exec("UPDATE users SET gender='Male' WHERE username IN ('admin', 'doc1', 'lab1')");
    $pdo->exec("UPDATE users SET gender='Female' WHERE username IN ('recep1', 'pharm1')");
    echo "Updated genders for existing users.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
