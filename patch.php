<?php
require 'c:/xampp7/htdocs/HMS_M/config/database.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN gender ENUM('Male', 'Female') DEFAULT 'Male'");
    echo "Added gender column\n";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}
