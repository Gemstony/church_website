<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/CV.php';

Auth::requireLogin();

$userId = Auth::userId();
$user = User::find($userId);
$cv = CV::getByUserId($userId);
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        if (empty($fullName)) {
            $error = 'Full name is required.';
        } else {
            // Handle profile picture upload
            $profilePic = null;
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($_FILES['profile_pic']['type'], $allowed)) {
                    $error = 'Profile picture must be JPG, PNG, GIF, or WEBP.';
                } elseif ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
                    $error = 'Profile picture max size 2MB.';
                } else {
                    $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
                    $uploadDir = __DIR__ . '/../public/assets/uploads/profiles/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $target = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
                        $profilePic = 'assets/uploads/profiles/' . $filename;
                        // Delete old profile pic if exists
                        if ($user['profile_pic'] && file_exists(__DIR__ . '/../public/' . $user['profile_pic'])) {
                            unlink(__DIR__ . '/../public/' . $user['profile_pic']);
                        }
                    } else {
                        $error = 'Failed to upload profile picture.';
                    }
                }
            }
            if (!$error) {
                if (User::updateProfile($userId, $fullName, $profilePic)) {
                    $success = 'Profile updated successfully.';
                    // Update session name
                    $_SESSION['user_name'] = $fullName;
                    if ($profilePic) $_SESSION['user_pic'] = $profilePic;
                    // Refresh user data
                    $user = User::find($userId);
                } else {
                    $error = 'Failed to update profile.';
                }
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (User::changePassword($userId, $old, $new)) {
            $success = 'Password changed successfully.';
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

// Handle CV delete
if (isset($_GET['delete_cv']) && $_GET['delete_cv'] === '1') {
    if (CV::delete($userId)) {
        $success = 'CV deleted successfully.';
        $cv = null;
    } else {
        $error = 'Failed to delete CV.';
    }
    header('Location: dashboard.php?msg=' . urlencode($success ?: $error));
    exit;
}

$csrf_token = Security::generateCSRFToken();
include __DIR__ . '/../app/views/header.php';
?>

<style>
    .dashboard-card {
        transition: transform 0.2s;
        margin-bottom: 1.5rem;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }
    .dashboard-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
    }
    .profile-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid var(--primary-color);
    }
</style>

<div class="row">
    <div class="col-md-4">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <?php if ($user['profile_pic']): ?>
                    <img src="<?php echo APP_URL . '/' . $user['profile_pic']; ?>" class="profile-img mb-3" alt="Profile">
                <?php else: ?>
                    <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:100px;height:100px;">
                        <span class="text-white display-4"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                    </div>
                <?php endif; ?>
                <h4><?php echo Security::escape($user['full_name']); ?></h4>
                <p class="text-muted"><?php echo Security::escape($user['email']); ?></p>
                <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo Security::escape($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo Security::escape($error); ?></div>
        <?php endif; ?>
        
        <!-- Edit Profile Form -->
        <div class="card dashboard-card">
            <div class="card-header bg-white fw-bold">Edit Profile</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo Security::escape($user['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Profile Picture</label>
                        <input type="file" name="profile_pic" class="form-control" accept="image/*">
                        <small class="text-muted">Max 2MB. JPG, PNG, GIF, WEBP.</small>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
        
        <!-- Change Password Form -->
        <div class="card dashboard-card">
            <div class="card-header bg-white fw-bold">Change Password</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label>Current Password</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>New Password (min 6 characters)</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-secondary">Change Password</button>
                </form>
            </div>
        </div>
        
        <!-- CV Management -->
        <div class="card dashboard-card">
            <div class="card-header bg-white fw-bold">My CV</div>
            <div class="card-body">
                <?php if ($cv): ?>
                    <div class="alert alert-info">
                        <strong>Current CV:</strong> <?php echo Security::escape($cv['file_name']); ?>
                        <br><small>Uploaded: <?php echo date('F j, Y', strtotime($cv['uploaded_at'])); ?></small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo APP_URL . '/' . $cv['file_path']; ?>" class="btn btn-sm btn-success" target="_blank">Download CV</a>
                        <a href="dashboard.php?delete_cv=1" class="btn btn-sm btn-danger" onclick="return confirm('Delete your CV permanently?')">Delete CV</a>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadCVModal">Replace CV</button>
                    </div>
                <?php else: ?>
                    <p>No CV uploaded yet.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadCVModal">Upload CV</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- CV Upload Modal -->
<div class="modal fade" id="uploadCVModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload CV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="cvUploadMsg"></div>
                <form id="cvUploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label>Select CV (PDF or DOCX, max 5MB)</label>
                        <input type="file" name="cv_file" class="form-control" accept=".pdf,.docx" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('cvUploadForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('api/upload-cv.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const msgDiv = document.getElementById('cvUploadMsg');
        if (data.success) {
            msgDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            setTimeout(() => location.reload(), 1500);
        } else {
            msgDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        }
    })
    .catch(() => {
        document.getElementById('cvUploadMsg').innerHTML = '<div class="alert alert-danger">Upload failed. Try again.</div>';
    });
});
</script>

<?php include __DIR__ . '/../app/views/footer.php'; ?>