<?php
require_once 'header.php';

// Fetch statistics
$stats = [
    'books' => 0,
    'borrowers' => 0,
    'active_transactions' => 0,
    'overdue_returns' => 0
];

try {
    // Total Books
    $stmt = $pdo->query("SELECT SUM(total_copies) as total FROM books");
    $res = $stmt->fetch();
    $stats['books'] = $res['total'] ?? 0;

    // Total Borrowers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrowers");
    $res = $stmt->fetch();
    $stats['borrowers'] = $res['total'] ?? 0;

    // Active Transactions (Borrowed)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE status = 'borrowed'");
    $res = $stmt->fetch();
    $stats['active_transactions'] = $res['total'] ?? 0;

    // Overdue returns (Borrowed and due date passed)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE status = 'borrowed' AND due_date < CURDATE()");
    $res = $stmt->fetch();
    $stats['overdue_returns'] = $res['total'] ?? 0;

    // Recent Transactions
    $stmt = $pdo->query("SELECT t.id, b.title, u.name, t.borrow_date, t.due_date, t.status 
                         FROM transactions t 
                         JOIN books b ON t.book_id = b.id 
                         JOIN borrowers u ON t.borrower_id = u.id 
                         ORDER BY t.created_at DESC LIMIT 5");
    $recent_transactions = $stmt->fetchAll();

} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Error fetching stats: " . $e->getMessage() . "</div>";
}

?>

<h2 class="page-title">Dashboard Overview</h2>

<div class="row g-4 mb-5">
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card books h-100">
            <div class="card-body position-relative">
                <h6 class="text-muted text-uppercase mb-2">Total Books</h6>
                <h2 class="display-5 fw-bold mb-0 text-primary"><?php echo $stats['books']; ?></h2>
                <i class="bi bi-journal-text stat-icon text-primary"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card borrowers h-100">
            <div class="card-body position-relative">
                <h6 class="text-muted text-uppercase mb-2">Total Members</h6>
                <h2 class="display-5 fw-bold mb-0 text-success"><?php echo $stats['borrowers']; ?></h2>
                <i class="bi bi-people stat-icon text-success"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card stat-card transactions h-100">
            <div class="card-body position-relative">
                <h6 class="text-muted text-uppercase mb-2">Books Borrowed</h6>
                <h2 class="display-5 fw-bold mb-0 text-warning"><?php echo $stats['active_transactions']; ?></h2>
                <i class="bi bi-arrow-left-right stat-icon text-warning"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card stat-card returns h-100">
            <div class="card-body position-relative">
                <h6 class="text-muted text-uppercase mb-2">Overdue Returns</h6>
                <h2 class="display-5 fw-bold mb-0 text-danger"><?php echo $stats['overdue_returns']; ?></h2>
                <i class="bi bi-exclamation-triangle stat-icon text-danger"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Transactions</span>
                <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Borrower</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($recent_transactions)): ?>
                                <?php foreach($recent_transactions as $t): ?>
                                    <tr>
                                        <td class="fw-medium"><?php echo htmlspecialchars($t['title']); ?></td>
                                        <td><?php echo htmlspecialchars($t['name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($t['borrow_date'])); ?></td>
                                        <td>
                                            <?php 
                                            $due_date = strtotime($t['due_date']);
                                            $is_overdue = ($t['status'] === 'borrowed' && $due_date < time());
                                            ?>
                                            <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                                <?php echo date('M d, Y', $due_date); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($t['status'] == 'returned'): ?>
                                                <span class="badge bg-success">Returned</span>
                                            <?php else: ?>
                                                <?php if($is_overdue): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Borrowed</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No recent transactions found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
