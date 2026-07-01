<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: user_dashboard.php");
    exit();
}

require_once 'db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($name) || empty($email) || empty($password)) {
        $message = "Name, Email, and Password are required.";
        $messageType = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "danger";
    } else {
        try {
            $student_id = trim($_POST['student_id'] ?? '');

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO borrowers (name, email, password, phone, address, student_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password, $phone, $address, $student_id]);
            
            $message = "Registration successful! You can now log in.";
            $messageType = "success";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Email already exists. Please use a different email or log in.";
            } else {
                $message = "Database error: " . $e->getMessage();
            }
            $messageType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Registration - LibraryMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        .register-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            width: 100%;
            max-width: 550px;
        }
        .register-header {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            padding: 25px 20px;
            text-align: center;
            color: white;
        }
        .register-header i {
            font-size: 2.5rem;
            margin-bottom: 5px;
        }
        .register-body {
            padding: 25px 30px;
            background: white;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25);
            border-color: #4f46e5;
            background-color: white;
        }
        .btn-register {
            background: #4f46e5;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
            color: white;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="register-card">
        <div class="register-header">
            <i class="bi bi-person-plus"></i>
            <h3 class="fw-bold mb-0">Join the Library</h3>
            <p class="mb-0 text-white-50">Create an account to borrow books</p>
        </div>
        <div class="register-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="off">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted fw-semibold">Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted fw-semibold">Email Address *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted fw-semibold">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted fw-semibold">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted fw-semibold">Student ID</label>
                        <input type="text" name="student_id" class="form-control" placeholder="ID Number">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted fw-semibold">Address</label>
                    <input type="text" name="address" class="form-control" placeholder="Optional">
                </div>
                
                <button type="submit" name="register" class="btn btn-register mt-2">
                    Create Account
                </button>
            </form>
            <div class="text-center mt-4 pt-3 border-top">
                <p class="text-muted mb-0">Already have an account? <a href="user_login.php" class="text-primary fw-bold text-decoration-none">Log in here</a></p>
                <a href="index.php" class="text-muted text-decoration-none d-block mt-2"><i class="bi bi-arrow-left me-1"></i> Back to Home</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
