<?php
require_once 'header.php';

$message = '';
$messageType = '';

// Handle Borrower Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_borrower'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($name) || empty($email)) {
        $message = "Name and Email are required.";
        $messageType = "danger";
    } else {
        try {
            $student_id = trim($_POST['student_id'] ?? '');

            // Default password is '12345'
            $default_password = password_hash('12345', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO borrowers (name, email, password, phone, address, student_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $default_password, $phone, $address, $student_id]);
            $message = "Member added successfully! Default password is '12345'.";
            $messageType = "success";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate email)
                $message = "A member with this email already exists.";
            } else {
                $message = "Error adding member: " . $e->getMessage();
            }
            $messageType = "danger";
        }
    }
}

// Fetch all borrowers
try {
    $stmt = $pdo->query("SELECT * FROM borrowers ORDER BY created_at DESC");
    $borrowers = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error fetching borrowers: " . $e->getMessage());
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title mb-0">Library Members</h2>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMemberModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add Member
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Borrowers List -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Student ID</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($borrowers)): ?>
                        <?php foreach($borrowers as $member): ?>
                            <tr>
                                <td><?php echo $member['id']; ?></td>
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-2 me-2 text-primary" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <?php echo htmlspecialchars($member['name']); ?>
                                    </div>
                                </td>
                                <td><a href="mailto:<?php echo htmlspecialchars($member['email']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($member['email']); ?></a></td>
                                <td><?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($member['student_id'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                                No members found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold" id="addMemberModalLabel">Add New Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Student ID</label>
                        <input type="text" name="student_id" class="form-control" placeholder="Enter Student ID">
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_borrower" class="btn btn-success">Save Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
