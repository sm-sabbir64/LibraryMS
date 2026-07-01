<?php
require_once 'db.php';

try {
    $sql = "ALTER TABLE transactions ADD COLUMN fine_amount DECIMAL(10,2) DEFAULT 0.00;";
    $pdo->exec($sql);
    echo "Successfully added fine_amount column to transactions table.\n";
} catch(PDOException $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}
?>
