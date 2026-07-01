<?php
require_once 'header.php';

$message = '';
$messageType = '';

// Handle Return Book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_book'])) {
    $transaction_id = (int)$_POST['transaction_id'];
    $book_id = (int)$_POST['book_id'];

    try {
        $pdo->beginTransaction();

        // Get the due date to calculate fine
        $stmt_due = $pdo->prepare("SELECT due_date FROM transactions WHERE id = ?");
        $stmt_due->execute([$transaction_id]);
        $txn = $stmt_due->fetch();
        
        $fine_amount = 0;
        if ($txn && strtotime($txn['due_date']) < time()) {
            $days_overdue = floor((time() - strtotime($txn['due_date'])) / (60 * 60 * 24));
            $fine_amount = $days_overdue * 10;
        }

        // Update transaction status and fine
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'returned', return_date = CURDATE(), fine_amount = ? WHERE id = ?");
        $stmt->execute([$fine_amount, $transaction_id]);

        // Increment available copies
        $stmt2 = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
        $stmt2->execute([$book_id]);

        $pdo->commit();
        $message = "Book returned successfully!";
        $messageType = "success";
    } catch(PDOException $e) {
        $pdo->rollBack();
        $message = "Error returning book: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Handle Issue Book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['issue_book'])) {
    $book_id = (int)$_POST['book_id'];
    $borrower_id = (int)$_POST['borrower_id'];
    $due_date = $_POST['due_date'];

    try {
        // Check availability first
        $stmt = $pdo->prepare("SELECT available_copies FROM books WHERE id = ? FOR UPDATE");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();

        if ($book && $book['available_copies'] > 0) {
            $pdo->beginTransaction();

            // Insert transaction
            $stmt1 = $pdo->prepare("INSERT INTO transactions (book_id, borrower_id, borrow_date, due_date) VALUES (?, ?, CURDATE(), ?)");
            $stmt1->execute([$book_id, $borrower_id, $due_date]);

            // Decrement copies
            $stmt2 = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
            $stmt2->execute([$book_id]);

            $pdo->commit();
            $message = "Book issued successfully!";
            $messageType = "success";
        } else {
            $message = "Sorry, this book is currently out of stock.";
            $messageType = "warning";
        }
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Error issuing book: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Fetch lists for the modal dropdowns
try {
    $booksList = $pdo->query("SELECT id, title, available_copies FROM books WHERE available_copies > 0 ORDER BY title")->fetchAll();
    $borrowersList = $pdo->query("SELECT id, name FROM borrowers ORDER BY name")->fetchAll();
} catch(PDOException $e) {}


// Fetch transactions (with filtering)
$status_filter = $_GET['status'] ?? 'all';
$query = "SELECT t.id, t.book_id, b.title as book_title, u.name as borrower_name, t.borrow_date, t.due_date, t.return_date, t.status, t.fine_amount 
          FROM transactions t 
          JOIN books b ON t.book_id = b.id 
          JOIN borrowers u ON t.borrower_id = u.id";

if ($status_filter === 'borrowed') {
    $query .= " WHERE t.status = 'borrowed'";
} elseif ($status_filter === 'returned') {
    $query .= " WHERE t.status = 'returned'";
}

$query .= " ORDER BY t.created_at DESC";

try {
    $stmt = $pdo->query($query);
    $transactions = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error fetching transactions: " . $e->getMessage());
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title mb-0">Transactions</h2>
    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#issueBookModal">
        <i class="bi bi-bookmark-check-fill me-1"></i> Issue Book
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body p-3">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?php echo $status_filter == 'all' ? 'active' : ''; ?>" href="?status=all">All</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $status_filter == 'borrowed' ? 'active bg-warning text-dark' : 'text-warning'; ?>" href="?status=borrowed">Currently Borrowed</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $status_filter == 'returned' ? 'active bg-success' : 'text-success'; ?>" href="?status=returned">Returned</a>
            </li>
        </ul>
    </div>
</div>

<!-- Transactions List -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Book</th>
                        <th>Borrower</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Fine</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($transactions)): ?>
                        <?php foreach($transactions as $t): ?>
                            <tr>
                                <td><?php echo $t['id']; ?></td>
                                <td class="fw-medium text-primary"><?php echo htmlspecialchars($t['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($t['borrower_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($t['borrow_date'])); ?></td>
                                <td>
                                    <?php 
                                    $due_date = strtotime($t['due_date']);
                                    $is_overdue = ($t['status'] === 'borrowed' && $due_date < time());
                                    
                                    $current_fine = 0;
                                    if ($t['status'] === 'borrowed' && $is_overdue) {
                                        $current_fine = floor((time() - $due_date) / (60 * 60 * 24)) * 10;
                                    } elseif ($t['status'] === 'returned') {
                                        $current_fine = $t['fine_amount'];
                                    }
                                    ?>
                                    <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo date('M d, Y', $due_date); ?>
                                        <?php if($is_overdue) echo ' <i class="bi bi-exclamation-circle"></i>'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($current_fine > 0): ?>
                                        <span class="text-danger fw-bold">৳ <?php echo number_format($current_fine, 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($t['status'] == 'returned'): ?>
                                        <span class="badge bg-success" title="Returned on <?php echo $t['return_date']; ?>">Returned</span>
                                    <?php else: ?>
                                        <?php if($is_overdue): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Borrowed</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($t['status'] == 'borrowed'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Confirm book return?');">
                                            <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                            <input type="hidden" name="book_id" value="<?php echo $t['book_id']; ?>">
                                            <button type="submit" name="return_book" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-arrow-return-left"></i> Return
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light text-muted" disabled><i class="bi bi-check2"></i> Done</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-arrow-left-right fs-1 d-block mb-2 text-secondary"></i>
                                No transactions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Issue Book Modal -->
<div class="modal fade" id="issueBookModal" tabindex="-1" aria-labelledby="issueBookModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold" id="issueBookModalLabel">Issue Book to Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">Select Book *</label>
                        <select name="book_id" class="form-select" required>
                            <option value="">-- Choose a book --</option>
                            <?php foreach($booksList as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['title']) . " (" . $b['available_copies'] . " available)"; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Member *</label>
                        <select name="borrower_id" class="form-select" required>
                            <option value="">-- Choose a member --</option>
                            <?php foreach($borrowersList as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date *</label>
                        <!-- Default to 14 days from now -->
                        <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="issue_book" class="btn btn-warning">Issue Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
