</div> <!-- End page-wrapper -->

    <footer class="public-footer position-relative pt-5 pb-4">
        <!-- Optional decorative top border -->
        <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, var(--primary), var(--secondary));"></div>
        
        <div class="container pt-4">
            <div class="row gy-5">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="0">
                    <h4 class="fw-bold mb-4 text-white"><i class="bi bi-book-half me-2 text-primary"></i>LibraryMS</h4>
                    <p class="text-light opacity-75 mb-4 pe-lg-4" style="line-height: 1.7;">Empowering minds through knowledge. Our digital library offers thousands of books across all genres for you to explore.</p>
                    <div class="social-links d-flex gap-2">
                        <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                    <h6 class="fw-bold text-uppercase mb-4 text-white letter-spacing-1">Explore</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="browse.php">All Books</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="browse.php">Categories</a></li>
                        <li><a href="#">New Arrivals</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                    <h6 class="fw-bold text-uppercase mb-4 text-white letter-spacing-1">Links</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="user_login.php">Member Portal</a></li>
                        <li><a href="#">Help & Support</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-12" data-aos="fade-up" data-aos-delay="300">
                    <h6 class="fw-bold text-uppercase mb-4 text-white letter-spacing-1">Newsletter</h6>
                    <p class="text-light opacity-75 mb-4">Get notified about new book arrivals, updates, and library events directly in your inbox.</p>
                    <form class="subscribe-form position-relative">
                        <div class="input-group">
                            <input type="email" class="form-control bg-dark text-white border-secondary px-4 py-3 rounded-pill-start" placeholder="Your email address" style="border-top-left-radius: 50px; border-bottom-left-radius: 50px;">
                            <button class="btn btn-primary px-4 py-3 fw-medium" type="button" style="border-top-right-radius: 50px; border-bottom-right-radius: 50px;">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row mt-5 pt-4 border-top border-secondary border-opacity-50">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <span class="text-light opacity-50 small">
                        &copy; <?php echo date('Y'); ?> KPI Library Management System. All rights reserved.
                    </span>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <span class="text-light opacity-50 small">
                        Developed with <i class="bi bi-heart-fill text-danger"></i> by Sabbir Hossain (CST, KPI)
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize animations
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                document.getElementById('mainNav').classList.add('scrolled');
            } else {
                document.getElementById('mainNav').classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
