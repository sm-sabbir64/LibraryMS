<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE borrowers ADD COLUMN profile_picture VARCHAR(255) NULL DEFAULT NULL");
    echo "Successfully added profile_picture to borrowers.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
