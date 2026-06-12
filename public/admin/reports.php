<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/Event.php';
require_once __DIR__ . '/../../app/models/AdminManager.php';

use Dompdf\Dompdf;
use Dompdf\Options;

Auth::requireAdmin();

$csrf_token = Security::generateCSRFToken();
$export_type = $_GET['export'] ?? '';
$format = $_GET['format'] ?? 'csv'; // csv or pdf
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// Helper function to export CSV
function exportCSV($filename, $headers, $data) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Helper function to export PDF
// Helper function to export PDF (with buffer cleaning)
function exportPDF($filename, $headers, $data, $title) {
    // Clean any output buffers that might have been started
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
            h1 { text-align: center; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #777; }
        </style>
    </head>
    <body>
        <h1>' . htmlspecialchars($title) . '</h1>
        <table>
            <thead>
                <tr>';
    foreach ($headers as $h) {
        $html .= '<th>' . htmlspecialchars($h) . '</th>';
    }
    $html .= '</tr>
            </thead>
            <tbody>';
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody>
        </table>
        <div class="footer">Generated on ' . date('Y-m-d H:i:s') . '</div>
    </body>
    </html>';
    
    try {
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        // Output the PDF
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    } catch (Exception $e) {
        // If PDF generation fails, output error message
        die('PDF Generation Error: ' . $e->getMessage());
    }
}

// Handle exports (no output before this point)
if ($export_type) {
    if (!Security::verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        $_SESSION['report_error'] = 'Invalid CSRF token.';
        header('Location: reports.php');
        exit;
    }
    
    $db = Database::getConnection();
    $data = [];
    $headers = [];
    $title = '';
    
    if ($export_type === 'members') {
        $stmt = $db->query("SELECT id, full_name, email, role, is_active, created_at FROM users ORDER BY created_at DESC");
        $rows = $stmt->fetchAll();
        $headers = ['Full Name', 'Email', 'Role', 'Active', 'Registered Date'];
        foreach ($rows as $r) {
            $data[] = [
                $r['full_name'],
                $r['email'],
                $r['role'],
                $r['is_active'] ? 'Yes' : 'No',
                $r['created_at']
            ];
        }
        $title = 'Members Report';
        $filename = 'members_report_' . date('Y-m-d');
        
    } elseif ($export_type === 'events') {
        $stmt = $db->query("SELECT id, title, event_date, event_end_date, location, created_at FROM events ORDER BY event_date DESC");
        $rows = $stmt->fetchAll();
        $headers = ['Title', 'Start Date', 'End Date', 'Location', 'Created At'];
        foreach ($rows as $r) {
            $data[] = [
                $r['title'],
                $r['event_date'],
                $r['event_end_date'],
                $r['location'],
                $r['created_at']
            ];
        }
        $title = 'Events Report';
        $filename = 'events_report_' . date('Y-m-d');
        
    } elseif ($export_type === 'registrations') {
        if ($event_id <= 0) {
            $_SESSION['report_error'] = 'Please select a valid event.';
            header('Location: reports.php');
            exit;
        }
        $stmt = $db->prepare("
            SELECT e.title as event_title, u.full_name, u.email, er.registered_at
            FROM event_registrations er
            JOIN events e ON er.event_id = e.id
            JOIN users u ON er.user_id = u.id
            WHERE er.event_id = :event_id
            ORDER BY er.registered_at DESC
        ");
        $stmt->execute([':event_id' => $event_id]);
        $rows = $stmt->fetchAll();
        if (empty($rows)) {
            $_SESSION['report_error'] = 'No registrations found for the selected event.';
            header('Location: reports.php');
            exit;
        }
        $headers = ['Event Title', 'Member Name', 'Member Email', 'Registered At'];
        foreach ($rows as $r) {
            $data[] = [
                $r['event_title'],
                $r['full_name'],
                $r['email'],
                $r['registered_at']
            ];
        }
        $title = 'Registrations for: ' . $rows[0]['event_title'];
        $filename = 'registrations_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $rows[0]['event_title']) . '_' . date('Y-m-d');
        
    } elseif ($export_type === 'cvs') {
        $stmt = $db->query("
            SELECT u.id, u.full_name, u.email, cvs.file_name, cvs.uploaded_at
            FROM cvs
            JOIN users u ON cvs.user_id = u.id
            ORDER BY cvs.uploaded_at DESC
        ");
        $rows = $stmt->fetchAll();
        $headers = ['Full Name', 'Email', 'CV Filename', 'Uploaded At'];
        foreach ($rows as $r) {
            $data[] = [
                $r['full_name'],
                $r['email'],
                $r['file_name'],
                $r['uploaded_at']
            ];
        }
        $title = 'CV Submissions Report';
        $filename = 'cvs_report_' . date('Y-m-d');
    }
    
    if ($format === 'pdf') {
        exportPDF($filename . '.pdf', $headers, $data, $title);
    } else {
        exportCSV($filename . '.csv', $headers, $data);
    }
}

// No export – show dashboard
include __DIR__ . '/includes/header.php';

$error = $_SESSION['report_error'] ?? '';
$success = $_SESSION['report_success'] ?? '';
unset($_SESSION['report_error'], $_SESSION['report_success']);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reports & Exports</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo Security::escape($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo Security::escape($success); ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Member Reports -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">Member Reports</div>
            <div class="card-body">
                <p>Export complete list of all registered members.</p>
                <div class="d-flex gap-2">
                    <a href="?export=members&format=csv&csrf_token=<?php echo $csrf_token; ?>" class="btn btn-primary">CSV</a>
                    <a href="?export=members&format=pdf&csrf_token=<?php echo $csrf_token; ?>" class="btn btn-secondary">PDF</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Event Reports -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">Event Reports</div>
            <div class="card-body">
                <p>Export all events with dates and locations.</p>
                <div class="d-flex gap-2">
                    <a href="?export=events&format=csv&csrf_token=<?php echo $csrf_token; ?>" class="btn btn-success">CSV</a>
                    <a href="?export=events&format=pdf&csrf_token=<?php echo $csrf_token; ?>" class="btn btn-secondary">PDF</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Event Registrations (with event selection) -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">Event Registrations</div>
            <div class="card-body">
                <p>Export registrations for a specific event.</p>
                <form method="GET" class="mt-2">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="export" value="registrations">
                    <div class="mb-2">
                        <select name="event_id" class="form-select" required>
                            <option value="">-- Select Event --</option>
                            <?php
                            $db = Database::getConnection();
                            $events = $db->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();
                            foreach ($events as $ev):
                            ?>
                                <option value="<?php echo $ev['id']; ?>"><?php echo Security::escape($ev['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="format" value="csv" class="btn btn-info">CSV</button>
                        <button type="submit" name="format" value="pdf" class="btn btn-secondary">PDF</button>
                    </div>
                </form>
                <?php if (empty($events)): ?>
                    <small class="text-muted">No events available.</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- CV Submissions -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">CV Submissions</div>
            <div class="card-body">
                <p>Export list of members who have uploaded CVs.</p>
                <div class="d-flex gap-2">
                    <a href="?export=cvs&format=csv&csrf_token=<?php echo $csrf_token; ?>" class="btn btn-warning">CSV</a>
                    <a href="?export=cvs&format=pdf&csrf_token=<?php echo $csrf_token; ?>" class="btn btn-secondary">PDF</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Card -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">Quick Statistics</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h5>Total Members</h5>
                        <p class="h3"><?php echo AdminManager::getStats()['members']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <h5>Total Events</h5>
                        <p class="h3"><?php echo AdminManager::getStats()['events']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <h5>Media Items</h5>
                        <p class="h3"><?php echo AdminManager::getStats()['media']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <h5>CVs Uploaded</h5>
                        <p class="h3"><?php
                            $db = Database::getConnection();
                            $stmt = $db->query("SELECT COUNT(*) as count FROM cvs");
                            echo $stmt->fetch()['count'];
                        ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>