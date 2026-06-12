<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/Slider.php';

Auth::requireAdmin();

$success = '';
$error = '';
$csrf_token = Security::generateCSRFToken();

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_slide'])) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $data = [
            'title' => trim($_POST['title']),
            'subtitle' => trim($_POST['subtitle']),
            'btn_text' => trim($_POST['btn_text']),
            'btn_link' => trim($_POST['btn_link']),
            'order' => (int) $_POST['order'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        $imageFile = $_FILES['image'] ?? null;
        if (isset($_POST['id']) && $_POST['id']) {
            $id = (int) $_POST['id'];
            if (Slider::update($id, $data, $imageFile)) {
                $success = 'Slide updated successfully.';
            } else {
                $error = 'Update failed.';
            }
        } else {
            if (!$imageFile || $imageFile['error'] !== UPLOAD_ERR_OK) {
                $error = 'Image is required for new slide.';
            } else {
                if (Slider::create($data, $imageFile)) {
                    $success = 'Slide added successfully.';
                } else {
                    $error = 'Add failed.';
                }
            }
        }
        if ($success)
            header('Location: slides.php?msg=' . urlencode($success));
        else
            header('Location: slides.php?error=' . urlencode($error));
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!isset($_GET['csrf_token']) || !Security::verifyCSRFToken($_GET['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $id = (int) $_GET['delete'];
        if (Slider::delete($id)) {
            $success = 'Slide deleted.';
        } else {
            $error = 'Delete failed.';
        }
    }
    header('Location: slides.php?msg=' . urlencode($success ?: $error));
    exit;
}

$slides = Slider::getAll(false);
$msg = $_GET['msg'] ?? '';
$err = $_GET['error'] ?? '';
if ($msg)
    $success = urldecode($msg);
if ($err)
    $error = urldecode($err);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Slider Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#slideModal" data-mode="add">+ Add New
        Slide</button>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo Security::escape($success); ?></div><?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo Security::escape($error); ?></div><?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Order</th>
                <th>Image</th>
                <th>Title</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($slides as $s): ?>
                <tr>
                    <td><?php echo $s['order']; ?></td>
                    <td><img src="<?php echo APP_URL . '/' . $s['image']; ?>" width="80" height="50"
                            style="object-fit:cover;"></td>
                    <td><?php echo Security::escape($s['title']); ?></td>
                    <td><?php echo $s['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-slide" data-bs-toggle="modal"
                            data-bs-target="#slideModal" data-mode="edit" data-id="<?php echo $s['id']; ?>"
                            data-title="<?php echo Security::escape($s['title']); ?>"
                            data-subtitle="<?php echo Security::escape($s['subtitle']); ?>"
                            data-btn_text="<?php echo Security::escape($s['btn_text']); ?>"
                            data-btn_link="<?php echo Security::escape($s['btn_link']); ?>"
                            data-order="<?php echo $s['order']; ?>" data-active="<?php echo $s['is_active']; ?>"
                            data-image="<?php echo $s['image']; ?>">Edit</button>
                        <a href="slides.php?delete=<?php echo $s['id']; ?>&csrf_token=<?php echo $csrf_token; ?>"
                            class="btn btn-sm btn-danger" onclick="return confirm('Delete this slide?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($slides)): ?>
                <tr>
                    <td colspan="5">No slides. Add one.</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Slide Modal -->
<div class="modal fade" id="slideModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Slide</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="id" id="slide_id">
                    <div class="mb-3"><label>Image (max 2MB, JPG/PNG/GIF/WEBP)</label><input type="file" name="image"
                            class="form-control" accept="image/*" id="slide_image">
                        <div id="current_image" class="mt-2"></div>
                    </div>
                    <div class="mb-3"><label>Title</label><input type="text" name="title" id="slide_title"
                            class="form-control"></div>
                    <div class="mb-3"><label>Subtitle</label><textarea name="subtitle" id="slide_subtitle"
                            class="form-control" rows="2"></textarea></div>
                    <div class="mb-3"><label>Button Text</label><input type="text" name="btn_text" id="slide_btn_text"
                            class="form-control"></div>
                    <div class="mb-3"><label>Button Link</label><input type="text" name="btn_link" id="slide_btn_link"
                            class="form-control"></div>
                    <div class="mb-3"><label>Order (lower number appears first)</label><input type="number" name="order"
                            id="slide_order" class="form-control" value="0"></div>
                    <div class="mb-3 form-check"><input type="checkbox" name="is_active" id="slide_active"
                            class="form-check-input" value="1"> <label class="form-check-label">Active</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancel</button><button type="submit" name="save_slide"
                        class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>
<script>
    const slideModal = document.getElementById('slideModal');
    slideModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const mode = button.dataset.mode;
        if (mode === 'edit' || button.dataset.id) {
            // Edit mode
            document.getElementById('slide_id').value = button.dataset.id;
            document.getElementById('slide_title').value = button.dataset.title;
            document.getElementById('slide_subtitle').value = button.dataset.subtitle;
            document.getElementById('slide_btn_text').value = button.dataset.btn_text;
            document.getElementById('slide_btn_link').value = button.dataset.btn_link;
            document.getElementById('slide_order').value = button.dataset.order;
            document.getElementById('slide_active').checked = button.dataset.active == '1';
            document.getElementById('current_image').innerHTML = `<img src="<?php echo APP_URL; ?>/${button.dataset.image}" style="max-height:80px"><br><small>Current image (replace by uploading new)</small>`;
            // Remove required attribute from file input for edit
            document.getElementById('slide_image').removeAttribute('required');
        } else {
            // Add mode
            document.getElementById('slide_id').value = '';
            document.getElementById('slide_title').value = '';
            document.getElementById('slide_subtitle').value = '';
            document.getElementById('slide_btn_text').value = '';
            document.getElementById('slide_btn_link').value = '';
            document.getElementById('slide_order').value = '0';
            document.getElementById('slide_active').checked = true;
            document.getElementById('current_image').innerHTML = '';
            // Make file input required for add
            document.getElementById('slide_image').setAttribute('required', 'required');
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>