<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
header('Content-Type: application/json');

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$query = "SELECT * FROM books WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR author LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY title ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    
    // Fetch user's borrowed books
    $borrowed_book_ids = [];
    if (isset($_SESSION['user_id'])) {
        $stmt_borrowed = $pdo->prepare("SELECT book_id FROM transactions WHERE borrower_id = ? AND status = 'borrowed'");
        $stmt_borrowed->execute([$_SESSION['user_id']]);
        $borrowed_book_ids = $stmt_borrowed->fetchAll(PDO::FETCH_COLUMN);
    }
    
    echo json_encode([
        'success' => true, 
        'books' => $books, 
        'borrowed_book_ids' => $borrowed_book_ids
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching catalog: ' . $e->getMessage()]);
}
?>
