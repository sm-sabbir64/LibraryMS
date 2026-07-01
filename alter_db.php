<?php
require_once 'db.php';
try {
    // Drop document_url if it exists (may throw error if it doesn't, we can ignore or check)
    try {
        $pdo->exec("ALTER TABLE borrowers DROP COLUMN document_url");
        echo "Dropped document_url column.<br>";
    } catch (PDOException $e) {
        // Ignored
    }
    
    // Add student_id column
    $pdo->exec("ALTER TABLE borrowers ADD COLUMN student_id VARCHAR(100) NULL DEFAULT NULL");
    echo "Successfully added student_id column.<br>";
} catch(PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "student_id column already exists.<br>";
    } else {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}
?>
