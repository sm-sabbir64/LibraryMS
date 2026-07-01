<?php
require_once 'header.php';
require_once 'db.php';

// Handle Resolve Ticket
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resolve_ticket'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    try {
        $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'resolved' WHERE id = ?");
        $stmt->execute([$ticket_id]);
        $message = "Ticket #$ticket_id marked as resolved.";
        $messageType = "success";
    } catch(PDOException $e) {
        $message = "Error resolving ticket: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Fetch Tickets
try {
    $stmt = $pdo->query("
        SELECT st.*, b.name as user_name, b.email as user_email 
        FROM support_tickets st 
        JOIN borrowers b ON st.user_id = b.id 
        ORDER BY FIELD(st.status, 'open', 'resolved'), st.created_at DESC
    ");
    $tickets = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error fetching tickets: " . $e->getMessage());
}
?>

<div class="container my-5">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show shadow-sm" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark"><i class="bi bi-headset me-2 text-primary"></i>Support Tickets</h2>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Attachment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($tickets)): ?>
                            <?php foreach($tickets as $ticket): ?>
                                <tr>
                                    <td><span class="fw-bold">#<?php echo $ticket['id']; ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($ticket['user_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($ticket['user_email']); ?></div>
                                    </td>
                                    <td class="fw-medium text-dark"><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-toggle="modal" data-bs-target="#msgModal<?php echo $ticket['id']; ?>">
                                            Read Message
                                        </button>
                                        
                                        <!-- Message Modal -->
                                        <div class="modal fade" id="msgModal<?php echo $ticket['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold">Message from <?php echo htmlspecialchars($ticket['user_name']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="mb-0 text-muted" style="white-space: pre-wrap;"><?php echo htmlspecialchars($ticket['message']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if(!empty($ticket['attachment_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($ticket['attachment_url']); ?>" target="_blank" class="btn btn-sm btn-primary rounded-pill"><i class="bi bi-paperclip"></i> View</a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($ticket['status'] == 'open'): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2"><i class="bi bi-hourglass-split me-1"></i>Open</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i>Resolved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
                                    <td>
                                        <?php if($ticket['status'] == 'open'): ?>
                                            <form method="POST" class="m-0" onsubmit="return confirm('Mark this ticket as resolved?');">
                                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                <button type="submit" name="resolve_ticket" class="btn btn-sm btn-success rounded-pill fw-semibold shadow-sm"><i class="bi bi-check-lg me-1"></i>Resolve</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary rounded-pill" disabled>Resolved</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
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

<?php require_once 'footer.php'; ?>
