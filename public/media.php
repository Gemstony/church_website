<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/models/Media.php';

$mediaItems = Media::getAll();
$isAdmin = Auth::isAdmin();
$csrf_token = Security::generateCSRFToken();

include __DIR__ . '/../app/views/header.php';
?>

<style>
    .gallery-card {
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
        overflow: hidden;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .gallery-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px rgba(0,0,0,0.15);
    }
    .gallery-card img, .gallery-card video {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
        pointer-events: none; /* makes whole card clickable */
    }
    .gallery-caption {
        padding: 12px;
    }
    .gallery-caption h5 {
        font-size: 1rem;
        margin-bottom: 5px;
    }
    .gallery-caption p {
        font-size: 0.85rem;
        color: #6c757d;
        margin: 0;
    }
    .modal-body img, .modal-body video {
        max-width: 100%;
        max-height: 70vh;
        display: block;
        margin: 0 auto;
    }
    #uploadBtn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        font-size: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .gallery-card .video-wrapper {
        position: relative;
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
        z-index: 2;
    }
    @media (max-width: 768px) {
        .gallery-card img, .gallery-card video {
            height: 180px;
        }
        #uploadBtn {
            width: 50px;
            height: 50px;
            font-size: 20px;
            bottom: 20px;
            right: 20px;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Media Gallery</h1>
    <?php if ($isAdmin): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload"></i> Upload Media
        </button>
    <?php endif; ?>
</div>

<?php if (empty($mediaItems)): ?>
    <div class="alert alert-info text-center">No media items yet. Check back soon!</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($mediaItems as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="gallery-card" data-bs-toggle="modal" data-bs-target="#mediaModal" 
                     data-title="<?php echo Security::escape($item['title']); ?>" 
                     data-description="<?php echo Security::escape($item['description']); ?>" 
                     data-file="<?php echo APP_URL . '/' . $item['file_path']; ?>" 
                     data-type="<?php echo $item['file_type']; ?>" 
                     data-id="<?php echo $item['id']; ?>">
                    <?php if ($item['file_type'] === 'image'): ?>
                        <img src="<?php echo APP_URL . '/' . $item['file_path']; ?>" alt="<?php echo Security::escape($item['title']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="video-wrapper">
                            <video src="<?php echo APP_URL . '/' . $item['file_path']; ?>" preload="metadata"></video>
                            <i class="fas fa-play-circle play-icon"></i>
                        </div>
                    <?php endif; ?>
                    <div class="gallery-caption">
                        <h5><?php echo Security::escape($item['title']); ?></h5>
                        <p><?php echo Security::escape(substr($item['description'], 0, 60)); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Lightbox Modal (improved with video stop) -->
<div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="modalMedia"></div>
                <p id="modalDesc" class="mt-3 text-muted"></p>
            </div>
            <div class="modal-footer">
                <?php if ($isAdmin): ?>
                    <button type="button" class="btn btn-danger" id="deleteMediaBtn">Delete</button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal (Admin only) -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Media</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div id="uploadMsg"></div>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label>Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>File (Image or Video, max 20MB)</label>
                        <input type="file" name="media_file" class="form-control" accept="image/*,video/mp4,video/webm" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Lightbox modal population with improved video stop
const mediaModal = document.getElementById('mediaModal');
if (mediaModal) {
    mediaModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const title = trigger.dataset.title;
        const description = trigger.dataset.description;
        const file = trigger.dataset.file;
        const type = trigger.dataset.type;
        const id = trigger.dataset.id;
        
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalDesc').innerText = description;
        const mediaContainer = document.getElementById('modalMedia');
        if (type === 'image') {
            mediaContainer.innerHTML = `<img src="${file}" alt="${title}" class="img-fluid">`;
        } else {
            mediaContainer.innerHTML = `<video src="${file}" controls autoplay class="w-100"></video>`;
        }
        
        // Store current id for delete button
        const deleteBtn = document.getElementById('deleteMediaBtn');
        if (deleteBtn) {
            deleteBtn.dataset.id = id;
        }
    });
    
    // Stop video playback when modal is closed
    mediaModal.addEventListener('hidden.bs.modal', function () {
        const mediaContainer = document.getElementById('modalMedia');
        const video = mediaContainer.querySelector('video');
        if (video) {
            video.pause();
            video.currentTime = 0;
        }
        mediaContainer.innerHTML = ''; // Clear to release resources
    });
}

// Delete media (admin)
const deleteBtn = document.getElementById('deleteMediaBtn');
if (deleteBtn) {
    deleteBtn.addEventListener('click', function() {
        const id = this.dataset.id;
        if (!confirm('Delete this media item permanently?')) return;
        fetch('api/delete-media.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&csrf_token=<?php echo $csrf_token; ?>'
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
}

// Upload form handling
const uploadForm = document.getElementById('uploadForm');
if (uploadForm) {
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('api/upload-media.php', {
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
            }
        })
        .catch(err => {
            document.getElementById('uploadMsg').innerHTML = '<div class="alert alert-danger">Error uploading file</div>';
        });
    });
}
</script>

<?php include __DIR__ . '/../app/views/footer.php'; ?>