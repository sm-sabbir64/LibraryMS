<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_page = basename($_SERVER['PHP_SELF']);
require_once 'public_header.php';
?>

<!-- About Hero Section -->
<div class="hero-section text-center position-relative" style="padding: 120px 0 80px; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); overflow: hidden;">
    <div class="hero-shape" style="top: -50%; left: -10%; opacity: 0.5;"></div>
    <div class="container position-relative z-1">
        <h1 class="fw-bold mb-3" style="font-size: 3.5rem; color: var(--dark);">About <span class="brand-icon">library</span></h1>
        <p class="text-muted mx-auto" style="max-width: 600px; font-size: 1.2rem;">
            We believe in the power of reading. Our mission is to provide seamless access to knowledge for everyone, anywhere, anytime.
        </p>
    </div>
</div>

<!-- Our Story Section -->
<div class="container py-5 mt-4">
    <div class="row align-items-center mb-5 pb-4">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <img src="uploads/Kurigram Polytechnic Institute.jpg" alt="Library Interior" class="img-fluid rounded-4 shadow-lg" style="object-fit: cover; height: 400px; width: 100%;">
        </div>
        <div class="col-lg-6 ps-lg-5">
            <h6 class="text-primary fw-bold text-uppercase mb-2">Our Story</h6>
            <h2 class="fw-bold mb-4">A Modern Approach to Traditional Reading</h2>
            <p class="text-muted mb-3" style="line-height: 1.8;">
                Founded with a passion for literature, LibraryMS was created to bridge the gap between physical books and digital convenience. We noticed that readers often struggled with keeping track of their borrowed books and discovering new reads.
            </p>
            <p class="text-muted mb-4" style="line-height: 1.8;">
                Today, our system offers a smart dashboard, real-time inventory tracking, and a curated catalog of the world's most beloved books. Whether you are a student, a professional, or a casual reader, we have something for you.
            </p>
            <div class="d-flex gap-4">
                <div>
                    <h3 class="fw-bold text-dark mb-0">25+</h3>
                    <span class="text-muted small">Curated Books</span>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0">24/7</h3>
                    <span class="text-muted small">Digital Access</span>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0">100%</h3>
                    <span class="text-muted small">Free for Members</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Core Values Section -->
<div class="bg-white py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase mb-2">Core Values</h6>
            <h2 class="fw-bold">What Drives Us Forward</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px; transition: transform 0.3s; cursor: pointer;" onmouseover="this.style.transform='translateY(-10px)'" onmouseout="this.style.transform='none'">
                    <div class="card-body p-4 text-center">
                        <div class="mb-4 d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" style="width: 70px; height: 70px; border-radius: 20px;">
                            <i class="bi bi-book fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Knowledge for All</h5>
                        <p class="text-muted">We ensure that our library is accessible to everyone, promoting lifelong learning and curiosity.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px; transition: transform 0.3s; cursor: pointer;" onmouseover="this.style.transform='translateY(-10px)'" onmouseout="this.style.transform='none'">
                    <div class="card-body p-4 text-center">
                        <div class="mb-4 d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success" style="width: 70px; height: 70px; border-radius: 20px;">
                            <i class="bi bi-laptop fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Digital Innovation</h5>
                        <p class="text-muted">Using modern web technologies to provide a seamless, intuitive, and fast borrowing experience.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px; transition: transform 0.3s; cursor: pointer;" onmouseover="this.style.transform='translateY(-10px)'" onmouseout="this.style.transform='none'">
                    <div class="card-body p-4 text-center">
                        <div class="mb-4 d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning" style="width: 70px; height: 70px; border-radius: 20px;">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Community First</h5>
                        <p class="text-muted">Building a strong community of readers who share ideas, reviews, and their love for literature.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="container py-5 my-5">
    <div class="bg-dark text-white text-center rounded-4 shadow-lg p-5 position-relative overflow-hidden">
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(45deg, rgba(79, 70, 229, 0.8), rgba(236, 72, 153, 0.8)); z-index: 0;"></div>
        <div class="position-relative z-1 py-4">
            <h2 class="fw-bold mb-3">Ready to Start Reading?</h2>
            <p class="mb-4 mx-auto" style="max-width: 500px; color: #e2e8f0;">Join our community today and get instant access to our entire collection of amazing books.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="browse.php" class="btn btn-light btn-lg rounded-pill px-4 fw-bold text-primary">Browse Catalog</a>
                <a href="user_register.php" class="btn btn-outline-light btn-lg rounded-pill px-4">Become a Member</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'public_footer.php'; ?>
