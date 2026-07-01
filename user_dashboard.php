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

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
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
      </div>
  </div>

  <!-- PROFILE TAB -->
  <div class="tab-pane fade <?php echo $active_tab == 'profile' ? 'show active' : ''; ?>" id="profile" role="tabpanel">
      <div class="row justify-content-center" data-aos="fade-up">
          <div class="col-md-4 mb-4">
              <div class="card border-0 shadow-sm rounded-4 h-100">
                  <div class="card-body p-4 text-center">
                      <?php $display_pic = !empty($user_info['profile_picture']) ? $user_info['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_info['name']) . '&background=0D8ABC&color=fff&size=256'; ?>
                      <img src="<?php echo htmlspecialchars($display_pic); ?>" alt="Profile" class="rounded-circle mb-3 shadow-sm" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #e2e8f0;">
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
                          <div class="row">
                              <div class="col-12 mb-4">
                                  <label class="form-label text-muted fw-semibold">Update Profile Picture</label>
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
                      <h4 class="fw-bold mb-4 text-dark"><i class="bi bi-shield-lock-fill me-2 text-warning"></i>Change Password</h4>
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
                              <button type="submit" name="change_password" class="btn btn-warning btn-lg rounded-pill fw-bold shadow-sm text-dark">Update Password</button>
                          </div>
                      </form>
                  </div>
              </div>
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
</script>

<?php require_once 'footer.php'; ?>
