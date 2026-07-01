<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $host  = $_SERVER['HTTP_HOST'];
    $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header("Location: http://$host$uri/user_login.php");
    exit();
}

require_once 'db.php';
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch user data for header
$header_stmt = $pdo->prepare("SELECT name, profile_picture FROM borrowers WHERE id = ?");
$header_stmt->execute([$_SESSION['user_id']]);
$header_user = $header_stmt->fetch();
$header_profile_pic = !empty($header_user['profile_picture']) ? $header_user['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($header_user['name']) . '&background=0D8ABC&color=fff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - LibraryMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <style>
        .custom-navbar {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm custom-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="user_dashboard.php">
            <i class="bi bi-book-half me-2"></i>LibraryMS Member
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link text-white" href="index.php"><i class="bi bi-house me-1"></i>Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="browse.php"><i class="bi bi-collection me-1"></i>Catalog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active fw-bold text-white" href="user_dashboard.php"><i class="bi bi-person-badge me-1"></i>My Books</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-3 align-items-center">
                <li class="nav-item me-3">
                    <img src="<?php echo htmlspecialchars($header_profile_pic); ?>" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid rgba(255,255,255,0.5);">
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm mt-1 mt-lg-0" href="user_logout.php">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5 pb-5">
