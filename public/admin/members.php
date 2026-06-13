<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/AdminManager.php';

Auth::requireAdmin();

$success = '';
$error = '';
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$result = AdminManager::getMembers($search, $page, 10);
$members = $result['members'];
$totalPages = ceil($result['total'] / $result['perPage']);

// Handle add/edit/delete actions
$action = $_GET['action'] ?? '';
$memberId = (int)($_GET['id'] ?? 0);

// Add new member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        if (empty($email) || empty($password) || empty($full_name)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $res = AdminManager::addMember($email, $password, $full_name);
            if ($res['success']) {
                $success = 'Member added successfully.';
                header('Location: members.php?msg=' . urlencode($success));
                exit;
            } else {
                $error = $res['error'];
            }
        }
    }
}

// Edit member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_member'])) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $id = (int)$_POST['id'];
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'member';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        if (AdminManager::updateMember($id, $full_name, $email, $role, $is_active)) {
            $success = 'Member updated successfully.';
            header('Location: members.php?msg=' . urlencode($success));
            exit;
        } else {
            $error = 'Update failed.';
        }
    }
}

// Delete member
if (isset($_GET['delete']) && $_GET['delete'] == 1 && $memberId > 0) {
    if (AdminManager::deleteMember($memberId)) {
        $success = 'Member deleted successfully.';
        header('Location: members.php?msg=' . urlencode($success));
        exit;
    } else {
        $error = 'Cannot delete admin or member not found.';
    }
}

// Get message from redirect
$msg = $_GET['msg'] ?? '';
if ($msg) $success = urldecode($msg);

$csrf_token = Security::generateCSRFToken();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Members Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">+ Add New Member</button>
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
        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo Security::escape($search); ?>">
        <button type="submit" class="btn btn-outline-secondary">Search</button>
        <?php if ($search): ?>
            <a href="members.php" class="btn btn-outline-danger">Clear</a>
        <?php endif; ?>
    </div>
</form>

<!-- Members Table -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Profile</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($members)): ?>
                <tr>
                    <td colspan="8" class="text-center">No members found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?php echo $member['id']; ?></td>
                        <td>
                            <?php if ($member['profile_pic']): ?>
                                <img src="<?php echo APP_URL . '/' . $member['profile_pic']; ?>" width="40" height="40" class="rounded-circle" style="object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                    <span class="text-white small"><?php echo strtoupper(substr($member['full_name'], 0, 1)); ?></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo Security::escape($member['full_name']); ?></td>
                        <td><?php echo Security::escape($member['email']); ?></td>
                        <td>
                             <?php if ($member['role'] == 'admin'): ?>
                                <span class="badge bg-danger">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-info">Member</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($member['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editMemberModal" data-id="<?php echo $member['id']; ?>" data-name="<?php echo Security::escape($member['full_name']); ?>" data-email="<?php echo Security::escape($member['email']); ?>" data-role="<?php echo $member['role']; ?>" data-active="<?php echo $member['is_active']; ?>">Edit</button>
                            <a href="members.php?delete=1&id=<?php echo $member['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this member permanently? This will also delete their CV and profile picture.')">Delete</a>
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

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password * (min 6 characters)</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_member" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div class="modal fade" id="editMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" name="full_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Role</label>
                        <select name="role" id="edit_role" class="form-select">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                        </select>
                        <small class="text-muted">Promoting to admin gives full access.</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="edit_active" class="form-check-input" value="1">
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_member" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const editModal = document.getElementById('editMemberModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit_id').value = button.dataset.id;
        document.getElementById('edit_name').value = button.dataset.name;
        document.getElementById('edit_email').value = button.dataset.email;
        document.getElementById('edit_role').value = button.dataset.role;
        document.getElementById('edit_active').checked = button.dataset.active == '1';
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>