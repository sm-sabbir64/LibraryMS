<?php
// Database configuration
$host = 'localhost';
$dbname = 'library_ms'; // We will use this db name
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If connection fails, redirect to setup page if db doesn't exist
    if ($e->getCode() == 1049) {
        // Unknown database, maybe need to run setup
        $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        if (basename($_SERVER['PHP_SELF']) !== 'setup.php') {
             die("<div style='font-family: sans-serif; text-align:center; margin-top:50px;'><h2>Database not found.</h2><p>Please run the <a href='setup.php'>setup script</a> to initialize the database.</p></div>");
        }
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
