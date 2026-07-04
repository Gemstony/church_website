<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/Media.php';

Auth::requireAdmin();

$success = '';
$error = '';
$csrf_token = Security::generateCSRFToken();

// Handle delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_media'])) {
    header('Content-Type: application/json');
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF']);
        exit;
    }
    $id = (int)$_POST['id'];
    if (Media::delete($id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Delete failed']);
    }
    exit;
}

// Handle edit media (update title & description)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['edit_media']) || (isset($_POST['id']) && isset($_POST['title']) && isset($_POST['description'])))) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0) {
            $error = 'Invalid media ID.';
        } elseif ($title === '') {
            $error = 'Title is required.';
        } else {
            try {
                if (Media::update($id, $title, $description)) {
                    $success = 'Media updated successfully.';
                } else {
                    $error = 'Database error.';
                }
            } catch (Throwable $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$mediaItems = Media::getAll();
include __DIR__ . '/includes/header.php';
?>

<style>
    .media-card {
        transition: transform 0.2s;
        margin-bottom: 1.5rem;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        cursor: pointer;
    }
    .media-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
    }
    .media-card img, .media-card video {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        pointer-events: none;
    }
    .upload-area {
        border: 2px dashed #ccc;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s;
    }
    .upload-area:hover {
        background: #e9ecef;
        border-color: #0d6efd;
    }
    .play-icon {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 48px;
        color: white;
        text-shadow: 0 0 10px black;
        pointer-events: none;
        opacity: 0.9;
    }
    .card-buttons {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }
    .card-buttons button {
        flex: 1;
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Media Gallery Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="fas fa-upload"></i> Upload New Media
    </button>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo Security::escape($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo Security::escape($error); ?></div>
<?php endif; ?>

<div class="row g-4">
    <?php if (empty($mediaItems)): ?>
        <div class="col-12">
            <div class="alert alert-info text-center">No media items. Click "Upload New Media" to add.</div>
        </div>
    <?php else: ?>
        <?php foreach ($mediaItems as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card media-card" data-bs-toggle="modal" data-bs-target="#mediaViewModal" data-file="<?php echo APP_URL . '/' . $item['file_path']; ?>" data-type="<?php echo $item['file_type']; ?>" data-title="<?php echo Security::escape($item['title']); ?>" data-description="<?php echo Security::escape($item['description']); ?>">
                    <?php if ($item['file_type'] === 'image'): ?>
                        <img src="<?php echo APP_URL . '/' . $item['file_path']; ?>" alt="<?php echo Security::escape($item['title']); ?>">
                    <?php else: ?>
                        <div style="position: relative;">
                            <video src="<?php echo APP_URL . '/' . $item['file_path']; ?>" preload="metadata"></video>
                            <i class="fas fa-play-circle play-icon"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo Security::escape($item['title']); ?></h5>
                        <p class="card-text small text-muted"><?php echo Security::escape(substr($item['description'], 0, 80)); ?></p>
                        <p class="card-text"><small class="text-muted">Uploaded: <?php echo date('M j, Y', strtotime($item['uploaded_at'])); ?></small></p>
                        <div class="card-buttons">
                            <button class="btn btn-sm btn-warning edit-media" data-id="<?php echo $item['id']; ?>" data-title="<?php echo Security::escape($item['title']); ?>" data-description="<?php echo Security::escape($item['description']); ?>" onclick="event.stopPropagation();" data-bs-toggle="modal" data-bs-target="#editMediaModal">Edit</button>
                            <button class="btn btn-sm btn-danger delete-media" data-id="<?php echo $item['id']; ?>" onclick="event.stopPropagation();">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- View Lightbox Modal -->
<div class="modal fade" id="mediaViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="viewMediaContainer"></div>
                <p id="viewModalDesc" class="mt-3 text-muted"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Media Modal -->
<div class="modal fade" id="editMediaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Media</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" onsubmit="disableEditButton(this)">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label>Title *</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_media" class="btn btn-primary" id="editMediaBtn">
                        <span id="editMediaText">Save Changes</span>
                        <span id="editMediaSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Media</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="uploadMsg"></div>
                <form id="uploadForm" enctype="multipart/form-data" action="<?php echo APP_URL; ?>/api/upload-media.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label>Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="upload-area" onclick="document.getElementById('mediaFile').click()">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted"></i>
                        <p class="mt-2">Click to select image or video (max 20MB)</p>
                        <input type="file" name="media_file" id="mediaFile" class="d-none" accept="image/*,video/mp4,video/webm" required>
                    </div>
                    <div class="mt-3" id="fileNameDisplay"></div>
                    <button type="submit" class="btn btn-primary mt-3 w-100" id="uploadMediaBtn">
                        <span id="uploadMediaText">Upload</span>
                        <span id="uploadMediaSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to disable edit button
    function disableEditButton(form) {
        const btn = document.getElementById('editMediaBtn');
        if (btn) {
            btn.disabled = true;
            document.getElementById('editMediaText').textContent = 'Saving...';
            document.getElementById('editMediaSpinner').classList.remove('d-none');
        }
        return true;
    }

    // Populate view modal
    const viewModal = document.getElementById('mediaViewModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function(event) {
            const card = event.relatedTarget;
            const file = card.dataset.file;
            const type = card.dataset.type;
            const title = card.dataset.title;
            const description = card.dataset.description;
            document.getElementById('viewModalTitle').innerText = title;
            document.getElementById('viewModalDesc').innerText = description;
            const container = document.getElementById('viewMediaContainer');
            if (type === 'image') {
                container.innerHTML = `<img src="${file}" class="img-fluid" alt="${title}">`;
            } else {
                container.innerHTML = `<video src="${file}" controls autoplay class="w-100"></video>`;
            }
        });
        viewModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('viewMediaContainer').innerHTML = '';
        });
    }

    // Populate edit modal
    const editModal = document.getElementById('editMediaModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit_id').value = button.dataset.id;
        document.getElementById('edit_title').value = button.dataset.title;
        document.getElementById('edit_description').value = button.dataset.description;
        // Reset button state
        const btn = document.getElementById('editMediaBtn');
        if (btn) {
            btn.disabled = false;
            document.getElementById('editMediaText').textContent = 'Save Changes';
            document.getElementById('editMediaSpinner').classList.add('d-none');
        }
    });

    // File upload preview
    document.getElementById('mediaFile')?.addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        document.getElementById('fileNameDisplay').innerHTML = `<div class="alert alert-info">Selected: ${fileName}</div>`;
    });

    // Upload form handler with spinner
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('uploadMediaBtn');
            const textSpan = document.getElementById('uploadMediaText');
            const spinner = document.getElementById('uploadMediaSpinner');
            btn.disabled = true;
            textSpan.textContent = 'Uploading...';
            spinner.classList.remove('d-none');

            const formData = new FormData(this);
            fetch('<?php echo APP_URL; ?>/api/upload-media.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const msgDiv = document.getElementById('uploadMsg');
                if (data.success) {
                    msgDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    msgDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
                    btn.disabled = false;
                    textSpan.textContent = 'Upload';
                    spinner.classList.add('d-none');
                }
            })
            .catch(() => {
                document.getElementById('uploadMsg').innerHTML = '<div class="alert alert-danger">Upload failed. Try again.</div>';
                btn.disabled = false;
                textSpan.textContent = 'Upload';
                spinner.classList.add('d-none');
            });
        });
    }

    // Delete media
    document.querySelectorAll('.delete-media').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!confirm('Delete this media item permanently?')) return;
            const id = this.dataset.id;
            const formData = new FormData();
            formData.append('delete_media', 1);
            formData.append('id', id);
            formData.append('csrf_token', '<?php echo $csrf_token; ?>');
            fetch('media.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        });
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>