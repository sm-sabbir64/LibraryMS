<?php
session_start();

// If already logged in, redirect to user dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: user_dashboard.php");
    exit();
}

require_once 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, password FROM borrowers WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header("Location: user_dashboard.php");
                exit();
            } else {
                $message = "Invalid email or password.";
            }
        } catch(PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Login - LibraryMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .login-header i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .login-body {
            padding: 40px 30px;
            background: white;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
            border-color: #10b981;
            background-color: white;
        }
        .btn-login {
            background: #10b981;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-person-badge"></i>
            <h3 class="fw-bold mb-0">Member Login</h3>
            <p class="mb-0 text-white-50">Log in to view your borrowed books</p>
        </div>
        <div class="login-body">
            <?php if ($message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="off">
                <div class="mb-4">
                    <label class="form-label text-muted fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="jane.smith@example.com" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                        <input type="password" id="userPassword" name="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary bg-light border-start-0 border text-muted" type="button" id="toggleUserPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-success btn-login mt-2">
                    Sign In <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>
            <div class="text-center mt-4 pt-3 border-top">
                <p class="text-muted mb-0">Don't have an account? <a href="user_register.php" class="text-success fw-bold text-decoration-none">Sign up here</a></p>
                <a href="index.php" class="text-muted text-decoration-none d-block mt-2"><i class="bi bi-arrow-left me-1"></i> Back to Home</a>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('toggleUserPassword').addEventListener('click', function (e) {
    const password = document.getElementById('userPassword');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.querySelector('i').classList.toggle('bi-eye');
    this.querySelector('i').classList.toggle('bi-eye-slash');
});
</script>
</body>
</html>
