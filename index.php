<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
require_once 'public_header.php';

// Fetch a few featured books (e.g., latest 3 that have cover images)
try {
    $stmt = $pdo->query("SELECT * FROM books WHERE cover_url IS NOT NULL AND cover_url != '' ORDER BY id DESC LIMIT 3");
    $featured_books = $stmt->fetchAll();
} catch(PDOException $e) {
    $featured_books = [];
}
?>

<div class="hero-section">
    <div class="hero-shape"></div>
    <div class="container position-relative z-1">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <span class="badge bg-primary bg-opacity-10 text-primary mb-3 px-3 py-2 rounded-pill fw-bold">Welcome to KPI Central Library</span>
                <h1 class="hero-title">Expand Your Mind, One Book at a Time.</h1>
                <p class="hero-subtitle">The Official Digital Library of Kurigram Polytechnic Institute.</p>
                <div class="d-flex gap-3">
                    <a href="browse.php" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm">Explore Catalog <i class="bi bi-arrow-right ms-2"></i></a>
                    <a href="#featured" class="btn btn-outline-dark btn-lg rounded-pill px-4">View Features</a>
                </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0 text-center" data-aos="fade-left" data-aos-delay="200">
                <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?q=80&w=800&auto=format&fit=crop" alt="Library" class="img-fluid rounded-4 shadow-lg" style="transform: rotate(-3deg); border: 10px solid white;">
            </div>
        </div>
    </div>
</div>

<section class="py-5 bg-white" id="featured">
    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <h6 class="text-primary fw-bold text-uppercase tracking-wider">New Arrivals</h6>
            <h2 class="fw-bold mb-3">Featured Books</h2>
            <p class="text-muted max-w-2xl mx-auto">Explore our most recent additions to the library collection. From classic literature to modern sci-fi.</p>
        </div>

        <div class="row g-4 mt-2">
            <?php foreach($featured_books as $index => $book): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="book-card">
                    <div class="book-cover-wrapper">
                        <span class="book-badge"><?php echo htmlspecialchars($book['category'] ?? 'General'); ?></span>
                        <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" onerror="this.src='https://images.unsplash.com/photo-1544947950-fa07a98d237f?q=80&w=600&auto=format&fit=crop'" alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover">
                    </div>
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($book['title']); ?>"><?php echo htmlspecialchars($book['title']); ?></h5>
                        <p class="text-primary fw-medium small mb-3">By <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="text-muted small mb-4" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo htmlspecialchars($book['description'] ?? 'No description available for this book.'); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <span class="small fw-semibold <?php echo $book['available_copies'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $book['available_copies'] > 0 ? '<i class="bi bi-check-circle me-1"></i> Available' : '<i class="bi bi-x-circle me-1"></i> Out of Stock'; ?>
                            </span>
                            <a href="browse.php?search=<?php echo urlencode($book['title']); ?>" class="btn btn-sm btn-light">Details</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="browse.php" class="btn btn-outline-primary rounded-pill px-5 py-2 fw-semibold">View All Books</a>
        </div>
    </div>
</section>

<section class="py-5" style="background: linear-gradient(135deg, #f0fdf4 0%, #e0e7ff 100%);">
    <div class="container py-5 text-center" data-aos="zoom-in">
        <h2 class="fw-bold mb-4" style="color: var(--dark);">Ready to start reading?</h2>
        <p class="mb-5 max-w-2xl mx-auto fs-5" style="color: #475569;">Join our library today to get access to thousands of premium books, audiobooks, and journals.</p>
        <div class="d-flex justify-content-center gap-3">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="user_dashboard.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">Go to My Dashboard</a>
            <?php else: ?>
                <a href="user_login.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">Member Login</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'public_footer.php'; ?>
