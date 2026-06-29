<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';

// Handle Borrow Request (AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_borrow'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please log in to borrow books.', 'redirect' => true]);
        exit();
    }
    
    $book_id = (int)$_POST['book_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT available_copies FROM books WHERE id = ? FOR UPDATE");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();
        
        $check_stmt = $pdo->prepare("SELECT id FROM transactions WHERE book_id = ? AND borrower_id = ? AND status = 'borrowed'");
        $check_stmt->execute([$book_id, $user_id]);
        
        if ($check_stmt->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'You already borrowed this book.']);
            exit();
        } elseif ($book && $book['available_copies'] > 0) {
            $due_date = date('Y-m-d', strtotime('+14 days'));
            
            $stmt1 = $pdo->prepare("INSERT INTO transactions (book_id, borrower_id, borrow_date, due_date) VALUES (?, ?, CURDATE(), ?)");
            $stmt1->execute([$book_id, $user_id, $due_date]);

            $stmt2 = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
            $stmt2->execute([$book_id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'new_count' => $book['available_copies'] - 1, 'message' => 'Borrowed successfully!']);
            exit();
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Out of stock!']);
            exit();
        }
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Error borrowing book.']);
        exit();
    }
}

require_once 'public_header.php';

// Search and Category filtering
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

// The AJAX handler was moved to the top.

try {
    // Get unique categories for filter
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != ''");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    die("Error fetching catalog: " . $e->getMessage());
}
?>

<div class="bg-light pt-5 pb-4" style="margin-top: 70px;">
    <div class="container pt-4">
    <div class="container pt-4">
        <!-- Toast Notification Container -->
        <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999; margin-top: 80px;">
            <div id="liveToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="toastMessage">
                        <!-- Message goes here -->
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <h1 class="fw-bold mb-2">Book Catalog</h1>
                <p class="text-muted">Browse our entire collection of <span id="book-count">...</span> books.</p>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <form id="search-form" method="GET" class="d-flex gap-2 bg-white p-2 rounded-pill shadow-sm">
                    <input type="text" id="search-input" name="search" class="form-control border-0 bg-transparent shadow-none px-3" placeholder="Search by title or author..." value="<?php echo htmlspecialchars($search); ?>">
                    <select id="category-select" name="category" class="form-select border-0 bg-light w-auto rounded-pill text-muted">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container py-5 min-vh-100">
    <div class="row g-4" id="books-container">
        <!-- Books will be loaded here via JavaScript -->
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toastEl = document.getElementById('liveToast');
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    const toastBody = document.getElementById('toastMessage');

    const booksContainer = document.getElementById('books-container');
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const categorySelect = document.getElementById('category-select');
    const bookCount = document.getElementById('book-count');

    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe.toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    function loadBooks() {
        const search = encodeURIComponent(searchInput.value);
        const category = encodeURIComponent(categorySelect.value);
        
        booksContainer.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
        
        fetch(`api_books.php?search=${search}&category=${category}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    booksContainer.innerHTML = `<div class="col-12 text-center py-5 text-danger">${escapeHtml(data.message)}</div>`;
                    return;
                }
                
                const books = data.books;
                const borrowedIds = data.borrowed_book_ids || [];
                bookCount.textContent = books.length;
                
                if (books.length === 0) {
                    booksContainer.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3">No books found</h4>
                            <p class="text-muted">Try adjusting your search or category filter.</p>
                            <button type="button" class="btn btn-outline-primary mt-2" onclick="document.getElementById('search-form').reset(); loadBooks();">Clear Filters</button>
                        </div>
                    `;
                    return;
                }
                
                let html = '';
                books.forEach(book => {
                    const available = parseInt(book.available_copies);
                    let buttonHtml = '';
                    
                    if (borrowedIds.includes(book.id) || borrowedIds.includes(book.id.toString())) {
                        buttonHtml = `<button class="btn btn-sm btn-success rounded-pill px-3" disabled><i class="bi bi-check2"></i> Borrowed</button>`;
                    } else if (available > 0) {
                        buttonHtml = `<button type="button" class="btn btn-sm btn-primary rounded-pill px-3 borrow-btn" data-id="${book.id}">Borrow Now</button>`;
                    } else {
                        buttonHtml = `<button class="btn btn-sm btn-secondary rounded-pill px-3" disabled>Unavailable</button>`;
                    }
                    
                    const coverHtml = book.cover_url ? 
                        `<img src="${escapeHtml(book.cover_url)}" onerror="this.src='https://images.unsplash.com/photo-1544947950-fa07a98d237f?q=80&w=600&auto=format&fit=crop'" alt="${escapeHtml(book.title)}" class="book-cover">` :
                        `<div class="book-cover d-flex align-items-center justify-content-center bg-secondary text-white"><i class="bi bi-book fs-1"></i></div>`;
                        
                    const badgeHtml = book.category ? `<span class="book-badge">${escapeHtml(book.category)}</span>` : '';
                    
                    html += `
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="book-card h-100">
                            <div class="book-cover-wrapper">
                                ${badgeHtml}
                                ${coverHtml}
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-bold mb-1 text-truncate" title="${escapeHtml(book.title)}">${escapeHtml(book.title)}</h6>
                                <p class="text-primary small mb-2">By ${escapeHtml(book.author)}</p>
                                <p class="small text-muted mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;" title="${escapeHtml(book.description || '')}">
                                    ${escapeHtml(book.description || 'No description available.')}
                                </p>
                                
                                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold stock-count-${book.id} ${available > 0 ? 'text-success' : 'text-danger'}">
                                        ${available > 0 ? available + ' Available' : 'Out of Stock'}
                                    </span>
                                    ${buttonHtml}
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
                
                booksContainer.innerHTML = html;
                attachBorrowListeners();
            });
    }

    function attachBorrowListeners() {
        document.querySelectorAll('.borrow-btn').forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.getAttribute('data-id');
                const btn = this;
                
                // Disable button during request
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                btn.disabled = true;

                const formData = new FormData();
                formData.append('ajax_borrow', '1');
                formData.append('book_id', bookId);

                fetch('browse.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.redirect) {
                        window.location.href = 'user_login.php';
                        return;
                    }

                    toastBody.textContent = data.message;
                    toastEl.className = `toast align-items-center text-white border-0 bg-${data.success ? 'success' : 'danger'}`;
                    toast.show();

                    if(data.success) {
                        btn.innerHTML = '<i class="bi bi-check2"></i> Borrowed';
                        btn.classList.replace('btn-primary', 'btn-success');
                        
                        // Update count
                        const stockSpan = document.querySelector(`.stock-count-${bookId}`);
                        if (data.new_count > 0) {
                            stockSpan.textContent = data.new_count + ' Available';
                        } else {
                            stockSpan.textContent = 'Out of Stock';
                            stockSpan.classList.replace('text-success', 'text-danger');
                            btn.classList.replace('btn-success', 'btn-secondary');
                            btn.textContent = 'Unavailable';
                            btn.disabled = true;
                        }
                    } else {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });
        });
    }

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        loadBooks();
        // Update URL to match query (optional, for shareability)
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('search', searchInput.value);
        newUrl.searchParams.set('category', categorySelect.value);
        window.history.pushState({}, '', newUrl);
    });

    categorySelect.addEventListener('change', function() {
        searchForm.dispatchEvent(new Event('submit'));
    });

    // Initial load
    loadBooks();
});
</script>

<?php require_once 'public_footer.php'; ?>
