<?php
// Special connection without DB name to create the DB first
$host = 'localhost';
$username = 'root';
$password = '';

$message = '';
$messageType = '';

try {
    $pdo_setup = new PDO("mysql:host=$host", $username, $password);
    $pdo_setup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read the SQL file
    $sql = file_get_contents('schema.sql');

    if ($sql) {
        // Execute the multi-statement SQL
        $pdo_setup->exec($sql);
        $message = "Database and tables created successfully! Sample data inserted.";
        $messageType = "success";
    } else {
        $message = "Could not read schema.sql file.";
        $messageType = "danger";
    }

} catch(PDOException $e) {
    $message = "Setup failed: " . $e->getMessage();
    $messageType = "danger";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fc; }
        .setup-container { max-width: 600px; margin: 100px auto; }
    </style>
</head>
<body>
    <div class="container setup-container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Library Management System - Setup</h4>
            </div>
            <div class="card-body text-center p-5">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                        <?php echo $message; ?>
                    </div>
                    <?php if ($messageType === 'success'): ?>
                        <a href="index.php" class="btn btn-success mt-3">Go to Dashboard</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="lead">Click the button below to initialize the database and install sample data.</p>
                    <form method="post">
                        <button type="submit" name="run_setup" class="btn btn-primary btn-lg">Run Setup</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
