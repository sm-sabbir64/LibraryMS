<?php
require_once 'user_header.php';
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$message = '';
$messageType = '';
$active_tab = 'overview'; // Default active tab

// Check for flash messages
if (isset($_SESSION['dashboard_message'])) {
    $message = $_SESSION['dashboard_message'];
    $messageType = $_SESSION['dashboard_message_type'] ?? 'success';
    unset($_SESSION['dashboard_message']);
    unset($_SESSION['dashboard_message_type']);
}

// Handle Return Book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_book'])) {
    $active_tab = 'overview';
    $transaction_id = (int)$_POST['transaction_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Verify transaction belongs to user and is borrowed
        $stmt = $pdo->prepare("SELECT book_id, due_date FROM transactions WHERE id = ? AND borrower_id = ? AND status = 'borrowed' FOR UPDATE");
        $stmt->execute([$transaction_id, $user_id]);
        $transaction = $stmt->fetch();
        
        if ($transaction) {
            if (strtotime($transaction['due_date']) < time()) {
                $pdo->rollBack();
                $message = "This book is overdue. Please contact admin to pay the fine and return it.";
                $messageType = "danger";
            } else {
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
            }
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

// Handle Update Profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $active_tab = 'profile';
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $student_id = trim($_POST['student_id']);
    
    // Fetch current pic
    $stmt_pic = $pdo->prepare("SELECT profile_picture FROM borrowers WHERE id = ?");
    $stmt_pic->execute([$user_id]);
    $current_pic = $stmt_pic->fetchColumn();
    $profile_picture = $current_pic;

    if (!empty($_POST['cropped_image'])) {
        $uploadDir = 'uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $image_parts = explode(";base64,", $_POST['cropped_image']);
        if (count($image_parts) == 2) {
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $fileName = time() . '_' . uniqid() . '.' . $image_type;
            $targetFilePath = $uploadDir . $fileName;
            file_put_contents($targetFilePath, $image_base64);
            $profile_picture = $targetFilePath;
        }
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['profile_picture']['name']));
        $targetFilePath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFilePath)) {
            $profile_picture = $targetFilePath; 
        }
    }
    
    if (empty($name) || empty($email)) {
        $message = "Name and Email are required.";
        $messageType = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE borrowers SET name = ?, email = ?, phone = ?, address = ?, student_id = ?, profile_picture = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $student_id, $profile_picture, $user_id]);
            $_SESSION['user_name'] = $name;
            $user_name = $name;
            $message = "Profile updated successfully!";
            $messageType = "success";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Email is already in use by another account.";
            } else {
                $message = "Error updating profile: " . $e->getMessage();
            }
            $messageType = "danger";
        }
    }
}

// Handle Support Ticket Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_ticket'])) {
    $active_tab = 'support';
    $subject = trim($_POST['subject']);
    $message_body = trim($_POST['message']);
    
    $attachment_url = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/support/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['attachment']['name']));
        $targetFilePath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)) {
            $attachment_url = $targetFilePath; 
        }
    }
    
    if (empty($subject) || empty($message_body)) {
        $message = "Subject and Message are required.";
        $messageType = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, message, attachment_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $subject, $message_body, $attachment_url]);
            $message = "Support ticket submitted successfully. We will review it shortly.";
            $messageType = "success";
        } catch(PDOException $e) {
            $message = "Error submitting ticket: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}
// Handle Change Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $active_tab = 'security';
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM borrowers WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            $message = "Current password is incorrect.";
            $messageType = "danger";
        } elseif ($new_password !== $confirm_password) {
            $message = "New passwords do not match.";
            $messageType = "danger";
        } elseif (strlen($new_password) < 5) {
            $message = "New password must be at least 5 characters.";
            $messageType = "danger";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE borrowers SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            $message = "Password changed successfully!";
            $messageType = "success";
        }
    } catch(PDOException $e) {
        $message = "Error changing password: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Fetch complete user info
try {
    $stmt = $pdo->prepare("SELECT * FROM borrowers WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();
} catch(PDOException $e) {
    die("Error fetching user info: " . $e->getMessage());
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
    $stmt2 = $pdo->prepare("SELECT t.id, b.title, b.author, t.borrow_date, t.return_date, t.fine_amount 
                            FROM transactions t 
                            JOIN books b ON t.book_id = b.id 
                            WHERE t.borrower_id = ? AND t.status = 'returned' 
                            ORDER BY t.return_date DESC, t.id DESC");
    $stmt2->execute([$user_id]);
    $returned_books = $stmt2->fetchAll();

    // Summary Stats
    $total_active = count($borrowed_books);
    $total_returned = count($returned_books);
    $total_books_ever = $total_active + $total_returned;
    
    // Calculate total pending fine
    $total_pending_fine = 0;
    foreach($borrowed_books as $book) {
        $due_date = strtotime($book['due_date']);
        if ($due_date < time()) {
            $days_overdue = floor((time() - $due_date) / (60 * 60 * 24));
            $total_pending_fine += $days_overdue * 10;
        }
    }

    // Fetch Support Tickets
    $stmt3 = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
    $stmt3->execute([$user_id]);
    $support_tickets = $stmt3->fetchAll();

} catch(PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!-- Include AOS for Dashboard Animations -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<!-- Cropper.js -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
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
    
    /* Nav Tabs Styling */
    .nav-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #6c757d;
        font-weight: 600;
        padding: 12px 20px;
        transition: all 0.3s;
    }
    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: #4f46e5;
    }
    .nav-tabs .nav-link.active {
        color: #4f46e5;
        background: transparent;
        border-bottom: 3px solid #4f46e5;
    }
</style>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show shadow-sm mb-4 rounded-4" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($total_pending_fine) && $total_pending_fine > 0 && $active_tab == 'overview'): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4 rounded-4 d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-octagon-fill fs-3 me-3"></i>
        <div>
            <h5 class="fw-bold mb-1">Pending Fines: ৳ <?php echo number_format($total_pending_fine, 2); ?></h5>
            <span class="small">You have overdue books. Please contact the admin to pay your fine and return the books.</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-end mb-4 mt-3" data-aos="fade-down">
    <div>
        <p class="text-primary fw-semibold mb-1" style="letter-spacing: 1px; text-transform: uppercase; font-size: 0.85rem;">Member Dashboard</p>
        <h2 class="fw-bold mb-0" style="color: #1e293b; font-size: 2.2rem;">Welcome back, <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
    </div>
    <a href="browse.php" class="btn btn-primary rounded-pill px-4 shadow-sm py-2 fw-semibold"><i class="bi bi-search me-2"></i>Find New Books</a>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist" data-aos="fade-up">
  <li class="nav-item" role="presentation">
    <button class="nav-link <?php echo $active_tab == 'overview' ? 'active' : ''; ?>" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab"><i class="bi bi-grid-fill me-2"></i>Overview</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?php echo $active_tab == 'history' ? 'active' : ''; ?>" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab"><i class="bi bi-clock-history me-2"></i>History</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?php echo $active_tab == 'profile' ? 'active' : ''; ?>" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab"><i class="bi bi-person-circle me-2"></i>My Profile</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?php echo $active_tab == 'security' ? 'active' : ''; ?>" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab"><i class="bi bi-shield-lock-fill me-2"></i>Security</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?php echo $active_tab == 'support' ? 'active' : ''; ?>" id="support-tab" data-bs-toggle="tab" data-bs-target="#support" type="button" role="tab"><i class="bi bi-headset me-2"></i>Help & Support</button>
  </li>
</ul>

<div class="tab-content" id="dashboardTabsContent">
    
  <!-- OVERVIEW TAB -->
  <div class="tab-pane fade <?php echo $active_tab == 'overview' ? 'show active' : ''; ?>" id="overview" role="tabpanel">
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
                          
                          // Calculate fine (10 Taka per day)
                          $fine_amount = 0;
                          if ($is_overdue) {
                              $days_overdue = floor((time() - $due_date) / (60 * 60 * 24));
                              $fine_amount = $days_overdue * 10;
                          }
                          
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
                                      <div class="d-flex justify-content-between align-items-center mb-2">
                                          <span class="small text-muted">Borrow Date:</span>
                                          <span class="small text-dark">
                                              <?php echo date('M d, Y', strtotime($book['borrow_date'])); ?>
                                          </span>
                                      </div>
                                      <div class="d-flex justify-content-between align-items-center mb-3">
                                          <span class="small text-muted">Due Date:</span>
                                          <span class="small fw-bold <?php echo $is_overdue ? 'text-danger' : 'text-dark'; ?>">
                                              <?php echo date('M d, Y', $due_date); ?>
                                          </span>
                                      </div>
                                      <?php if($is_overdue && $fine_amount > 0): ?>
                                      <div class="d-flex justify-content-between align-items-center mb-3 bg-danger bg-opacity-10 p-2 rounded">
                                          <span class="small text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Fine Amount:</span>
                                          <span class="small fw-bold text-danger">৳ <?php echo number_format($fine_amount, 2); ?></span>
                                      </div>
                                      <?php endif; ?>
                                      <div class="d-flex gap-2">
                                          <a href="read_book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-primary rounded-pill w-100 fw-semibold"><i class="bi bi-book-half me-1"></i> Read Now</a>
                                          <?php if($is_overdue): ?>
                                              <button class="btn btn-outline-secondary rounded-pill w-100 fw-semibold" disabled title="Contact Admin to return"><i class="bi bi-lock me-1"></i> Return via Admin</button>
                                          <?php else: ?>
                                              <form method="POST" class="m-0 w-100" onsubmit="return confirm('Are you sure you want to return this book?');">
                                                  <input type="hidden" name="transaction_id" value="<?php echo $book['id']; ?>">
                                                  <button type="submit" name="return_book" class="btn btn-outline-danger rounded-pill w-100 fw-semibold">Return</button>
                                              </form>
                                          <?php endif; ?>
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
  </div>

  <!-- HISTORY TAB -->
  <div class="tab-pane fade <?php echo $active_tab == 'history' ? 'show active' : ''; ?>" id="history" role="tabpanel">
      <div class="card border-0 shadow-sm rounded-4" data-aos="fade-up">
          <div class="card-body p-4">
              <h5 class="fw-bold mb-4 text-secondary"><i class="bi bi-clock-history me-2"></i>Your Borrowing History</h5>
              <div class="table-responsive">
                  <table class="table table-custom">
                      <thead>
                          <tr>
                              <th>Book Title</th>
                              <th>Author</th>
                              <th>Borrow Date</th>
                              <th>Return Date</th>
                              <th>Fine Paid</th>
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
                                      <td>
                                          <?php if($book['fine_amount'] > 0): ?>
                                              <span class="text-danger fw-bold">৳ <?php echo number_format($book['fine_amount'], 2); ?></span>
                                          <?php else: ?>
                                              <span class="text-muted">-</span>
                                          <?php endif; ?>
                                      </td>
                                      <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i>Returned</span></td>
                                  </tr>
                              <?php endforeach; ?>
                          <?php else: ?>
                              <tr>
                                  <td colspan="6" class="text-center py-5 text-muted" style="background: rgba(0,0,0,0.02); border-radius: 15px;">
                                      No past borrowing history found.
                                  </td>
                              </tr>
                          <?php endif; ?>
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
  </div>

  <!-- PROFILE TAB -->
  <div class="tab-pane fade <?php echo $active_tab == 'profile' ? 'show active' : ''; ?>" id="profile" role="tabpanel">
      <div class="row justify-content-center" data-aos="fade-up">
          <div class="col-md-4 mb-4">
              <div class="card border-0 shadow-sm rounded-4 h-100">
                  <div class="card-body p-4 text-center">
                      <?php $display_pic = !empty($user_info['profile_picture']) ? $user_info['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_info['name']) . '&background=0D8ABC&color=fff&size=256'; ?>
                      <div class="position-relative d-inline-block mb-3" style="cursor: pointer;" onclick="document.querySelector('input[name=\'profile_picture\']').click()">
                          <img src="<?php echo htmlspecialchars($display_pic); ?>" alt="Profile" class="rounded-circle shadow-sm profile-preview-img" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #e2e8f0; transition: filter 0.3s;">
                          <div class="position-absolute top-50 start-50 translate-middle text-white opacity-0 edit-overlay" style="transition: opacity 0.3s; pointer-events: none;">
                              <i class="bi bi-camera-fill fs-2"></i>
                          </div>
                      </div>
                      <style>
                          .profile-preview-img:hover { filter: brightness(0.7); }
                          .position-relative:hover .edit-overlay { opacity: 1 !important; }
                      </style>
                      <h4 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($user_info['name']); ?></h4>
                      <p class="text-muted mb-2"><?php echo htmlspecialchars($user_info['email']); ?></p>
                      <?php if(!empty($user_info['student_id'])): ?>
                          <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill"><i class="bi bi-person-badge me-1"></i> ID: <?php echo htmlspecialchars($user_info['student_id']); ?></span>
                      <?php endif; ?>
                      <hr class="my-4">
                      <div class="text-start">
                          <p class="text-muted small mb-1"><i class="bi bi-telephone me-2"></i>Phone</p>
                          <p class="fw-medium text-dark mb-3"><?php echo htmlspecialchars($user_info['phone'] ?? 'Not provided'); ?></p>
                          <p class="text-muted small mb-1"><i class="bi bi-geo-alt me-2"></i>Address</p>
                          <p class="fw-medium text-dark mb-0"><?php echo htmlspecialchars($user_info['address'] ?? 'Not provided'); ?></p>
                      </div>
                  </div>
              </div>
          </div>
          <div class="col-md-8 mb-4">
              <div class="card border-0 shadow-sm rounded-4 h-100">
                  <div class="card-body p-5">
                      <h4 class="fw-bold mb-4 text-dark"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Edit Profile Details</h4>
                      <form method="POST" enctype="multipart/form-data">
                          <input type="hidden" name="cropped_image" id="cropped_image">
                          <div class="row">
                              <div class="col-12 d-none">
                                  <input type="file" name="profile_picture" class="form-control form-control-lg bg-light border-0" accept="image/*">
                              </div>
                              <div class="col-md-6 mb-4">
                                  <label class="form-label text-muted fw-semibold">Full Name *</label>
                                  <input type="text" name="name" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($user_info['name']); ?>" required>
                              </div>
                              <div class="col-md-6 mb-4">
                                  <label class="form-label text-muted fw-semibold">Email Address *</label>
                                  <input type="email" name="email" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                              </div>
                              <div class="col-md-6 mb-4">
                                  <label class="form-label text-muted fw-semibold">Phone Number</label>
                                  <input type="text" name="phone" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>">
                              </div>
                              <div class="col-md-6 mb-4">
                                  <label class="form-label text-muted fw-semibold">Student ID</label>
                                  <input type="text" name="student_id" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($user_info['student_id'] ?? ''); ?>">
                              </div>
                              <div class="col-12 mb-4">
                                  <label class="form-label text-muted fw-semibold">Address</label>
                                  <textarea name="address" class="form-control bg-light border-0" rows="3"><?php echo htmlspecialchars($user_info['address'] ?? ''); ?></textarea>
                              </div>
                              <div class="col-12">
                                  <button type="submit" name="update_profile" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm">Save Changes</button>
                              </div>
                          </div>
                      </form>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <!-- SECURITY TAB -->
  <div class="tab-pane fade <?php echo $active_tab == 'security' ? 'show active' : ''; ?>" id="security" role="tabpanel">
      <div class="row justify-content-center" data-aos="fade-up">
          <div class="col-md-6">
              <div class="card border-0 shadow-sm rounded-4">
                  <div class="card-body p-5">
                      <h4 class="fw-bold mb-4 text-dark"><i class="bi bi-shield-lock-fill me-2 text-dark"></i>Change Password</h4>
                      <form method="POST">
                          <div class="mb-4">
                              <label class="form-label text-muted fw-semibold">Current Password</label>
                              <input type="password" name="current_password" class="form-control form-control-lg bg-light border-0" required>
                          </div>
                          <div class="mb-4">
                              <label class="form-label text-muted fw-semibold">New Password</label>
                              <input type="password" name="new_password" class="form-control form-control-lg bg-light border-0" required>
                          </div>
                          <div class="mb-5">
                              <label class="form-label text-muted fw-semibold">Confirm New Password</label>
                              <input type="password" name="confirm_password" class="form-control form-control-lg bg-light border-0" required>
                          </div>
                          <div class="d-grid">
                              <button type="submit" name="change_password" class="btn btn-dark btn-lg rounded-pill fw-bold shadow-sm text-white">Update Password</button>
                          </div>
                      </form>
                  </div>
              </div>
          </div>
      </div>
  </div>
  <!-- SUPPORT TAB -->
  <div class="tab-pane fade <?php echo $active_tab == 'support' ? 'show active' : ''; ?>" id="support" role="tabpanel">
      <div class="row g-4" data-aos="fade-up">
          <div class="col-md-5">
              <div class="card border-0 shadow-sm rounded-4 h-100">
                  <div class="card-body p-4">
                      <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-chat-text me-2 text-primary"></i>Submit a Ticket</h5>
                      <form method="POST" enctype="multipart/form-data">
                          <div class="mb-3">
                              <label class="form-label text-muted fw-semibold">Subject *</label>
                              <input type="text" name="subject" class="form-control bg-light border-0" required placeholder="What is the issue about?">
                          </div>
                          <div class="mb-3">
                              <label class="form-label text-muted fw-semibold">Message *</label>
                              <textarea name="message" class="form-control bg-light border-0" rows="5" required placeholder="Describe your problem in detail..."></textarea>
                          </div>
                          <div class="mb-4">
                              <label class="form-label text-muted fw-semibold">Attachment (Optional)</label>
                              <input type="file" name="attachment" class="form-control bg-light border-0" accept="image/*,.pdf,.doc,.docx">
                              <small class="text-muted mt-1 d-block">You can upload a screenshot or document.</small>
                          </div>
                          <button type="submit" name="submit_ticket" class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm">Submit Ticket</button>
                      </form>
                  </div>
              </div>
          </div>
          <div class="col-md-7">
              <div class="card border-0 shadow-sm rounded-4 h-100">
                  <div class="card-body p-4">
                      <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-inboxes me-2 text-primary"></i>My Tickets</h5>
                      <div class="table-responsive">
                          <table class="table table-custom">
                              <thead>
                                  <tr>
                                      <th>Subject</th>
                                      <th>Date</th>
                                      <th>Status</th>
                                  </tr>
                              </thead>
                              <tbody>
                                  <?php if(!empty($support_tickets)): ?>
                                      <?php foreach($support_tickets as $ticket): ?>
                                          <tr>
                                              <td>
                                                  <div class="fw-semibold text-dark mb-1"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                                                  <div class="small text-muted text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($ticket['message']); ?></div>
                                                  <?php if(!empty($ticket['attachment_url'])): ?>
                                                      <a href="<?php echo htmlspecialchars($ticket['attachment_url']); ?>" target="_blank" class="small text-primary mt-1 d-inline-block"><i class="bi bi-paperclip"></i> View Attachment</a>
                                                  <?php endif; ?>
                                              </td>
                                              <td class="text-muted"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
                                              <td>
                                                  <?php if($ticket['status'] == 'open'): ?>
                                                      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-hourglass-split me-1"></i>Open</span>
                                                  <?php else: ?>
                                                      <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i>Resolved</span>
                                                  <?php endif; ?>
                                              </td>
                                          </tr>
                                      <?php endforeach; ?>
                                  <?php else: ?>
                                      <tr>
                                          <td colspan="3" class="text-center py-5 text-muted" style="background: rgba(0,0,0,0.02); border-radius: 15px;">
                                              <i class="bi bi-inbox fs-2 opacity-50 mb-2 d-block"></i>
                                              No support tickets found.
                                          </td>
                                      </tr>
                                  <?php endif; ?>
                              </tbody>
                          </table>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>

</div>

<!-- Cropper Modal -->
<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="cropperModalLabel">Crop Profile Picture</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-4">
        <div class="img-container" style="max-height: 400px; display: inline-block; max-width: 100%;">
            <img id="imageToCrop" src="" style="max-width: 100%; display: block;">
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary rounded-pill px-4" id="cropBtn">Crop & Save</button>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true
    });

    document.addEventListener('DOMContentLoaded', function() {
        const profileInput = document.querySelector('input[name="profile_picture"]');
        const imageToCrop = document.getElementById('imageToCrop');
        let cropper;
        let cropperModal;

        if (profileInput) {
            profileInput.addEventListener('change', function(e) {
                const files = e.target.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        imageToCrop.src = event.target.result;
                        if (!cropperModal) {
                            cropperModal = new bootstrap.Modal(document.getElementById('cropperModal'));
                        }
                        cropperModal.show();
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        const cropperModalEl = document.getElementById('cropperModal');
        if (cropperModalEl) {
            cropperModalEl.addEventListener('shown.bs.modal', function () {
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 1,
                    responsive: true,
                });
            });

            cropperModalEl.addEventListener('hidden.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                if (!document.getElementById('cropped_image').value) {
                    profileInput.value = '';
                }
            });
        }

        const cropBtn = document.getElementById('cropBtn');
        if (cropBtn) {
            cropBtn.addEventListener('click', function() {
                if (cropper) {
                    const canvas = cropper.getCroppedCanvas({
                        width: 256,
                        height: 256,
                    });
                    const croppedBase64 = canvas.toDataURL('image/jpeg');
                    document.getElementById('cropped_image').value = croppedBase64;
                    
                    const previewImage = document.querySelector('.card-body.text-center img.rounded-circle');
                    if (previewImage) {
                        previewImage.src = croppedBase64;
                    }
                    
                    cropperModal.hide();
                }
            });
        }
    });
</script>

<?php require_once 'footer.php'; ?>
