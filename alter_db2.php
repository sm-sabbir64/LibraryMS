<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE books CHANGE COLUMN isbn subject_code VARCHAR(50) UNIQUE");
    echo "Successfully renamed isbn to subject_code.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
