<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibraryMS - Read. Learn. Grow.</title>
    <!-- Tai    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- AOS Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Public Custom CSS -->
    <link rel="stylesheet" href="public_style.css?v=2.1">
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top glass-navbar transition-all" id="mainNav">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <div class="brand-icon d-inline-block align-middle me-2">
                <i class="bi bi-book-half"></i>
            </div>
            Digital Library | KPI
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-toggle="navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'browse.php' ? 'active' : ''; ?>" href="browse.php">Catalog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="about.php">About Us</a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php
                        require_once 'db.php';
                        $header_stmt = $pdo->prepare("SELECT name, profile_picture FROM borrowers WHERE id = ?");
                        $header_stmt->execute([$_SESSION['user_id']]);
                        $header_user = $header_stmt->fetch();
                        // 1st picture design: User initials in a circle (handled by ui-avatars if no picture)
                        $header_profile_pic = !empty($header_user['profile_picture']) ? $header_user['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($header_user['name']) . '&background=0D8ABC&color=fff';
                    ?>
                    <a href="user_dashboard.php" class="d-inline-block rounded-circle shadow-sm" style="width: 45px; height: 45px; overflow: hidden; border: 2px solid rgba(13, 138, 188, 0.5); transition: transform 0.2s;" title="My Dashboard" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <img src="<?php echo htmlspecialchars($header_profile_pic); ?>" alt="Dashboard" style="width: 100%; height: 100%; object-fit: cover;">
                    </a>
                <?php else: ?>
                    <a href="user_login.php" class="btn btn-login-member">Log In</a>
                    <a href="user_register.php" class="btn btn-primary rounded-pill px-4 shadow-sm" style="font-weight: 500;">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="page-wrapper">
