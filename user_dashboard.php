<?php
require_once 'user_header.php';
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$message = '';
$messageType = '';

// Check for flash messages
if (isset($_SESSION['dashboard_message'])) {
    $message = $_SESSION['dashboard_message'];
    $messageType = $_SESSION['dashboard_message_type'] ?? 'success';
    unset($_SESSION['dashboard_message']);
    unset($_SESSION['dashboard_message_type']);
}

// Handle Return Book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_book'])) {
    $transaction_id = (int)$_POST['transaction_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Verify transaction belongs to user and is borrowed
        $stmt = $pdo->prepare("SELECT book_id FROM transactions WHERE id = ? AND borrower_id = ? AND status = 'borrowed' FOR UPDATE");
        $stmt->execute([$transaction_id, $user_id]);
        $transaction = $stmt->fetch();
        
        if ($transaction) {
            $book_id = $transaction['book_id'];
            
            // Update transaction
            $stmt1 = $pdo->prepare("UPDATE transactions SET status = 'returned', return_date = CURDATE() WHERE id = ?");
            $stmt1->execute([$transaction_id]);
            
            // Increment copies
            $stmt2 = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
            $stmt2->execute([$book_id]);
            
            $pdo->commit();
            $message = "Book returned successfully! Thank you.";
            $messageType = "success";
        } else {
            $pdo->rollBack();
            $message = "Invalid transaction or book already returned.";
            $messageType = "danger";
        }
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Error returning book: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Fetch user's currently borrowed books
try {
    $stmt = $pdo->prepare("SELECT t.id, t.book_id, b.title, b.author, b.cover_url, t.borrow_date, t.due_date 
                           FROM transactions t 
                           JOIN books b ON t.book_id = b.id 
                           WHERE t.borrower_id = ? AND t.status = 'borrowed' 
                           ORDER BY t.borrow_date DESC, t.id DESC");
    $stmt->execute([$user_id]);
    $borrowed_books = $stmt->fetchAll();

    // Fetch user's past borrowed books (returned)
    $stmt2 = $pdo->prepare("SELECT t.id, b.title, b.author, t.borrow_date, t.return_date 
                            FROM transactions t 
                            JOIN books b ON t.book_id = b.id 
                            WHERE t.borrower_id = ? AND t.status = 'returned' 
                            ORDER BY t.return_date DESC");
    $stmt2->execute([$user_id]);
    $returned_books = $stmt2->fetchAll();

    // Summary Stats
    $total_active = count($borrowed_books);
    $total_returned = count($returned_books);
    $total_books_ever = $total_active + $total_returned;

} catch(PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!-- Include AOS for Dashboard Animations -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<style>
    .stat-card {
        border-radius: 20px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        transition: transform 0.3s, box-shadow 0.3s;
        background: linear-gradient(145deg, #ffffff, #f3f4f6);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    }
    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    
    /* Book Card for active books */
    .reading-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
        background: #fff;
    }
    .reading-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }
    .reading-card .cover-wrapper {
        position: relative;
        height: 250px;
        overflow: hidden;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .reading-card .cover-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .reading-card:hover .cover-wrapper img {
        transform: scale(1.05);
    }
    .reading-card .card-body {
        padding: 1.5rem;
    }
    
    /* Table styling for history */
    .table-custom {
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    .table-custom tbody tr {
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        border-radius: 12px;
        background: white;
        transition: all 0.2s;
    }
    .table-custom tbody tr:hover {
        transform: scale(1.01);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .table-custom td, .table-custom th {
        border: none;
        padding: 18px 20px;
        vertical-align: middle;
    }
    .table-custom th {
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .table-custom td:first-child { border-radius: 12px 0 0 12px; }
    .table-custom td:last-child { border-radius: 0 12px 12px 0; }
</style>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show shadow-sm mb-4 rounded-4" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-end mb-5 mt-3" data-aos="fade-down">
    <div>
        <p class="text-primary fw-semibold mb-1" style="letter-spacing: 1px; text-transform: uppercase; font-size: 0.85rem;">Member Dashboard</p>
        <h2 class="fw-bold mb-0" style="color: #1e293b; font-size: 2.2rem;">Welcome back, <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
    </div>
    <a href="browse.php" class="btn btn-primary rounded-pill px-4 shadow-sm py-2 fw-semibold"><i class="bi bi-search me-2"></i>Find New Books</a>
</div>

<!-- Statistics Row -->
<div class="row g-4 mb-5">
    <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="card stat-card h-100 p-4">
            <div class="d-flex align-items-center">
                <div class="icon-box bg-success bg-opacity-10 text-success me-4 shadow-sm">
                    <i class="bi bi-book-half"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 fw-semibold">Active Borrows</h6>
                    <h2 class="fw-bold mb-0 text-dark"><?php echo $total_active; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="card stat-card h-100 p-4">
            <div class="d-flex align-items-center">
                <div class="icon-box bg-primary bg-opacity-10 text-primary me-4 shadow-sm">
                    <i class="bi bi-journal-check"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 fw-semibold">Total Read (Returned)</h6>
                    <h2 class="fw-bold mb-0 text-dark"><?php echo $total_returned; ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="card stat-card h-100 p-4">
            <div class="d-flex align-items-center">
                <div class="icon-box bg-warning bg-opacity-10 text-warning me-4 shadow-sm">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 fw-semibold">Library Rank</h6>
                    <h4 class="fw-bold mb-0 text-dark mt-1"><?php echo $total_books_ever > 5 ? 'Avid Reader 🌟' : 'Beginner 🌱'; ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Currently Reading Section -->
<div class="mb-5" data-aos="fade-up" data-aos-delay="300">
    <div class="d-flex align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-bookmark-star-fill text-primary me-2"></i>Books You Are Reading Now</h4>
    </div>
    
    <div class="row g-4">
        <?php if(!empty($borrowed_books)): ?>
            <?php foreach($borrowed_books as $book): ?>
                <?php 
                    $due_date = strtotime($book['due_date']);
                    $is_overdue = ($due_date < time());
                    $cover = !empty($book['cover_url']) ? $book['cover_url'] : 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?q=80&w=600&auto=format&fit=crop';
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card reading-card h-100">
                        <div class="cover-wrapper">
                            <img src="<?php echo htmlspecialchars($cover); ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1544947950-fa07a98d237f?q=80&w=600&auto=format&fit=crop';" alt="Cover">
                            <?php if($is_overdue): ?>
                                <span class="position-absolute top-0 end-0 m-3 badge bg-danger fs-6 shadow"><i class="bi bi-exclamation-triangle me-1"></i>Overdue</span>
                            <?php else: ?>
                                <span class="position-absolute top-0 end-0 m-3 badge bg-white text-dark fs-6 shadow-sm"><i class="bi bi-clock me-1 text-primary"></i>Active</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($book['title']); ?>"><?php echo htmlspecialchars($book['title']); ?></h5>
                            <p class="text-muted small mb-3">By <?php echo htmlspecialchars($book['author']); ?></p>
                            
                            <div class="mt-auto border-top pt-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="small text-muted">Due Date:</span>
                                    <span class="small fw-bold <?php echo $is_overdue ? 'text-danger' : 'text-dark'; ?>">
                                        <?php echo date('M d, Y', $due_date); ?>
                                    </span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="read_book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-primary rounded-pill w-100 fw-semibold"><i class="bi bi-book-half me-1"></i> Read Now</a>
                                    <form method="POST" class="m-0 w-100" onsubmit="return confirm('Are you sure you want to return this book?');">
                                        <input type="hidden" name="transaction_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" name="return_book" class="btn btn-outline-danger rounded-pill w-100 fw-semibold">Return</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5 shadow-sm" style="background: rgba(0,0,0,0.02); border: 1px dashed rgba(0,0,0,0.1); border-radius: 20px;">
                    <div class="mb-3"><i class="bi bi-journal-x fs-1 text-muted opacity-50"></i></div>
                    <h5 class="fw-bold text-dark">No Books Currently Borrowed</h5>
                    <p class="text-muted">You haven't borrowed any books yet. Discover your next great read!</p>
                    <a href="browse.php" class="btn btn-primary rounded-pill mt-2 px-4 shadow-sm">Browse Catalog</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- History Section -->
<div class="col-md-12" data-aos="fade-up" data-aos-delay="400">
    <div class="d-flex align-items-center mb-3 mt-5">
        <h5 class="fw-bold mb-0 text-secondary"><i class="bi bi-clock-history me-2"></i>Your Borrowing History</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Borrow Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($returned_books)): ?>
                    <?php foreach($returned_books as $book): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($book['title']); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($book['borrow_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($book['return_date'])); ?></td>
                            <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i>Returned</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted" style="background: rgba(0,0,0,0.02); border-radius: 15px;">
                            No past borrowing history found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true
    });
</script>

<?php require_once 'footer.php'; ?>
