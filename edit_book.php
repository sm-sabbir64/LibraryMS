<?php
require_once 'header.php';

$message = '';
$messageType = '';

if (!isset($_GET['id'])) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Invalid Request. No book ID provided.</div></div>";
    require_once 'footer.php';
    exit();
}

$book_id = (int)$_GET['id'];

// Fetch existing book
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Book not found.</div></div>";
    require_once 'footer.php';
    exit();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_book'])) {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $book_content = trim($_POST['book_content'] ?? '');
    $year = (int)$_POST['published_year'];
    
    $new_total_copies = (int)$_POST['total_copies'];
    $cover_url = trim($_POST['cover_url']); // Fallback or user input
    
    // Handle File Upload
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
                // Remove old image if it exists in uploads folder
                if (!empty($book['cover_url']) && file_exists($book['cover_url']) && strpos($book['cover_url'], 'uploads/') === 0) {
                    unlink($book['cover_url']);
                }
                $cover_url = $targetFilePath; 
            }
        }
    }

    // Calculate new available copies
    $copies_difference = $new_total_copies - $book['total_copies'];
    $new_available_copies = $book['available_copies'] + $copies_difference;
    
    if ($new_available_copies < 0) {
        $message = "Cannot reduce total copies below currently borrowed copies.";
        $messageType = "danger";
    } elseif (empty($title) || empty($author)) {
        $message = "Title and Author are required.";
        $messageType = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE books SET title=?, author=?, isbn=?, category=?, cover_url=?, description=?, book_content=?, published_year=?, total_copies=?, available_copies=? WHERE id=?");
            $stmt->execute([$title, $author, $isbn, $category, $cover_url, $description, $book_content, $year, $new_total_copies, $new_available_copies, $book_id]);
            
            $message = "Book updated successfully!";
            $messageType = "success";
            
            // Refresh book data
            $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { 
                $message = "A book with this ISBN already exists.";
            } else {
                $message = "Error updating book: " . $e->getMessage();
            }
            $messageType = "danger";
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="books.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Back to Manage Books</a>
            <h2 class="fw-bold text-dark">Edit Book</h2>
        </div>
    </div>

    <?php if(!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4 p-md-5">
            <form method="POST" action="edit_book.php?id=<?php echo $book_id; ?>" enctype="multipart/form-data">
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">Book Title *</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Author *</label>
                                <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Category</label>
                                <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($book['category']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">ISBN</label>
                                <input type="text" name="isbn" class="form-control" value="<?php echo htmlspecialchars($book['isbn']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Published Year</label>
                                <input type="number" name="published_year" class="form-control" value="<?php echo htmlspecialchars($book['published_year']); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Short Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($book['description']); ?></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Full Book Content (For Reading)</label>
                            <textarea name="book_content" class="form-control" rows="8"><?php echo htmlspecialchars($book['book_content']); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="col-md-4">
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Cover Image</h6>
                                <?php if(!empty($book['cover_url'])): ?>
                                    <div class="mb-3 text-center">
                                        <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="Current Cover" class="img-fluid rounded shadow-sm" style="max-height: 200px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label small">Upload New Cover (Replaces current)</label>
                                    <input type="file" name="cover_image" class="form-control form-control-sm" accept="image/*">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Or provide image URL</label>
                                    <input type="url" name="cover_url" class="form-control form-control-sm" value="<?php echo htmlspecialchars($book['cover_url']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Inventory Settings</h6>
                                <div class="mb-3">
                                    <label class="form-label small">Total Copies</label>
                                    <input type="number" name="total_copies" class="form-control" value="<?php echo $book['total_copies']; ?>" min="1" required>
                                </div>
                                <div class="mb-2 text-muted small">
                                    Currently Borrowed: <?php echo $book['total_copies'] - $book['available_copies']; ?><br>
                                    Available: <?php echo $book['available_copies']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="update_book" class="btn btn-primary btn-lg">Update Book</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
