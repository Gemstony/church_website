<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/ContactMessage.php';

Auth::requireAdmin();

$success = '';
$error = '';
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$result = ContactMessage::getAll($search, $page, 15);

// Defensive: ensure expected keys exist
if (!isset($result['messages'])) {
    $messages = [];
    $total = 0;
    $perPage = 15;
    $error = 'Could not load messages. Please check database connection.';
} else {
    $messages = $result['messages'];
    $total = $result['total'] ?? 0;
    $perPage = $result['perPage'] ?? 15;
}
$totalPages = ($perPage > 0) ? ceil($total / $perPage) : 0;

$csrf_token = Security::generateCSRFToken();

// Handle mark as read (AJAX or direct)
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    if (!isset($_GET['csrf_token']) || !Security::verifyCSRFToken($_GET['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $id = (int)$_GET['mark_read'];
        if (ContactMessage::markRead($id)) {
            $success = 'Message marked as read.';
        } else {
            $error = 'Operation failed.';
        }
    }
    header('Location: messages.php?msg=' . urlencode($success ?: $error) . ($search ? '&search=' . urlencode($search) : ''));
    exit;
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!isset($_GET['csrf_token']) || !Security::verifyCSRFToken($_GET['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $id = (int)$_GET['delete'];
        if (ContactMessage::delete($id)) {
            $success = 'Message deleted successfully.';
        } else {
            $error = 'Delete failed.';
        }
    }
    header('Location: messages.php?msg=' . urlencode($success ?: $error) . ($search ? '&search=' . urlencode($search) : ''));
    exit;
}

$msg = $_GET['msg'] ?? '';
if ($msg) $success = urldecode($msg);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Contact Messages</h1>
    <span class="badge bg-primary fs-6">Unread: <?php echo ContactMessage::getUnreadCount(); ?></span>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo Security::escape($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo Security::escape($error); ?></div>
<?php endif; ?>

<!-- Search Form -->
<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search by name, email, or message..." value="<?php echo Security::escape($search); ?>">
        <button type="submit" class="btn btn-outline-secondary">Search</button>
        <?php if ($search): ?>
            <a href="messages.php" class="btn btn-outline-danger">Clear</a>
        <?php endif; ?>
    </div>
</form>

<!-- Messages Table -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Submitted</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($messages)): ?>
                <tr><td colspan="7" class="text-center">No messages found.<?php echo $error ? ' (Database error?)' : ''; ?></td></tr>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <tr class="<?php echo $msg['is_read'] ? '' : 'table-warning'; ?>">
                        <td><?php echo $msg['id']; ?></td>
                        <td><?php echo Security::escape($msg['name']); ?></td>
                        <td><?php echo Security::escape($msg['email']); ?></td>
                        <td>
                            <?php echo Security::escape(substr($msg['message'], 0, 80)); ?>
                            <?php if (strlen($msg['message']) > 80): ?>...<?php endif; ?>
                            <button class="btn btn-sm btn-link p-0 ms-2 view-message" data-id="<?php echo $msg['id']; ?>" data-name="<?php echo Security::escape($msg['name']); ?>" data-email="<?php echo Security::escape($msg['email']); ?>" data-message="<?php echo Security::escape($msg['message']); ?>" data-bs-toggle="modal" data-bs-target="#messageModal">View full</button>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($msg['submitted_at'])); ?></td>
                        <td>
                            <?php if ($msg['is_read']): ?>
                                <span class="badge bg-success">Read</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Unread</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$msg['is_read']): ?>
                                <a href="?mark_read=<?php echo $msg['id']; ?>&csrf_token=<?php echo $csrf_token; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-success">Mark Read</a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $msg['id']; ?>&csrf_token=<?php echo $csrf_token; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this message permanently?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- View Full Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Message from <span id="modalName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                <hr>
                <p><strong>Message:</strong></p>
                <p id="modalMessage" style="white-space: pre-wrap;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.view-message').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modalName').innerText = this.dataset.name;
            document.getElementById('modalEmail').innerText = this.dataset.email;
            document.getElementById('modalMessage').innerText = this.dataset.message;
        });
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>