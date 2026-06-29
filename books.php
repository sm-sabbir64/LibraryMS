<?php
require_once 'header.php';

$message = '';
$messageType = '';

// Handle Book Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_book'])) {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $category = trim($_POST['category']);
    $cover_url = trim($_POST['cover_url']);
    $description = trim($_POST['description']);
    $book_content = trim($_POST['book_content'] ?? '');
    $year = (int)$_POST['published_year'];
    $copies = (int)$_POST['total_copies'];

    // Handle file upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['cover_image']['name']));
        $targetFilePath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $targetFilePath)) {
                $cover_url = $targetFilePath; // Override url with uploaded file path
            }
        }
    }
    $year = (int)$_POST['published_year'];
    $copies = (int)$_POST['total_copies'];

    if (empty($title) || empty($author)) {
        $message = "Title and Author are required.";
        $messageType = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO books (title, author, isbn, category, cover_url, description, book_content, published_year, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $author, $isbn, $category, $cover_url, $description, $book_content, $year, $copies, $copies]);
            $message = "Book added successfully!";
            $messageType = "success";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate ISBN)
                $message = "A book with this ISBN already exists.";
            } else {
                $message = "Error adding book: " . $e->getMessage();
            }
            $messageType = "danger";
        }
    }
}

// Handle Book Deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_book'])) {
    $delete_id = (int)$_POST['book_id'];
    try {
        // Fetch cover_url to delete the file if necessary
        $stmt = $pdo->prepare("SELECT cover_url FROM books WHERE id = ?");
        $stmt->execute([$delete_id]);
        $book_to_delete = $stmt->fetch();
        
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        // Delete the associated image file if it's a local upload
        if ($book_to_delete && !empty($book_to_delete['cover_url']) && file_exists($book_to_delete['cover_url']) && strpos($book_to_delete['cover_url'], 'uploads/') === 0) {
            unlink($book_to_delete['cover_url']);
        }
        
        $message = "Book deleted successfully!";
        $messageType = "success";
    } catch(PDOException $e) {
        $message = "Error deleting book: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM books";
$params = [];

if (!empty($search)) {
    $query .= " WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$query .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error fetching books: " . $e->getMessage());
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title mb-0">Manage Books</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-toggle="modal" data-bs-target="#addBookModal">
        <i class="bi bi-plus-lg me-1"></i> Add New Book
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Search Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by title, author, or ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<!-- Books List -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Year</th>
                        <th>Availability</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($books)): ?>
                        <?php foreach($books as $book): ?>
                            <tr>
                                <td><?php echo $book['id']; ?></td>
                                <td class="fw-medium text-primary"><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td><?php echo htmlspecialchars($book['published_year']); ?></td>
                                <td>
                                    <?php 
                                        $available = $book['available_copies'];
                                        $total = $book['total_copies'];
                                        $badgeClass = $available > 0 ? 'bg-success' : 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo "$available / $total available"; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this book? This action cannot be undone.');">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" name="delete_book" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                                No books found matching your criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold" id="addBookModalLabel">Add New Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">Book Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author *</label>
                        <input type="text" name="author" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="e.g. Fiction, Science">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cover Image Upload</label>
                        <input type="file" name="cover_image" class="form-control" accept="image/*">
                        <div class="form-text mb-1 mt-2">Or provide an image URL below:</div>
                        <input type="url" name="cover_url" class="form-control" placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Short)</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Book Content (Text)</label>
                        <textarea name="book_content" class="form-control" rows="6" placeholder="Paste the full text of the book here..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Published Year</label>
                            <input type="number" name="published_year" class="form-control" min="1000" max="<?php echo date('Y'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total Copies *</label>
                            <input type="number" name="total_copies" class="form-control" value="1" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_book" class="btn btn-primary">Save Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
