<?php
$page_title = "Internship Applications - Admin - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Admin');

$success = '';
$error = '';

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id = (int)($_POST['app_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($app_id > 0 && in_array($status, ['Pending', 'Approved', 'Rejected'])) {
        try {
            $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
            $stmt->execute([$status, $app_id]);
            $success = "Application status updated to '$status' successfully!";
        } catch (PDOException $e) {
            $error = "Failed to update status: " . $e->getMessage();
        }
    }
}

// Fetch all applications
$appsStmt = $pdo->query("
    SELECT a.id, a.resume_path, a.status, a.applied_at, i.title AS intern_title, i.domain, prof.full_name AS student_name, u.email AS student_email
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    JOIN user_profiles prof ON a.student_id = prof.user_id
    JOIN users u ON a.student_id = u.id
    ORDER BY a.applied_at DESC
");
$applications = $appsStmt->fetchAll();
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
  <div class="row g-4">
    <!-- Sidebar Navigation -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm p-4 text-center bg-light" style="border-radius:15px;">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 mx-auto mb-3" style="width: 70px; height: 70px;">
          A
        </div>
        <h4 class="fw-bold text-dark h5 mb-1">Portal Admin</h4>
        <small class="text-muted d-block mb-3">System Administrator</small>
        
        <hr class="my-3">
        
        <div class="list-group list-group-flush text-start small shadow-xs" style="border-radius: 10px; overflow:hidden;">
          <a href="/admin/dashboard.php" class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
          <a href="/admin/users.php" class="list-group-item list-group-item-action"><i class="bi bi-people me-2"></i>User Roles</a>
          <a href="/admin/books.php" class="list-group-item list-group-item-action"><i class="bi bi-book me-2"></i>Manage Books</a>
          <a href="/admin/internships.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-briefcase me-2"></i>Internship Apps</a>
          <a href="/admin/courses.php" class="list-group-item list-group-item-action"><i class="bi bi-laptop me-2"></i>LMS Courses</a>
          <a href="/admin/events.php" class="list-group-item list-group-item-action"><i class="bi bi-calendar-event me-2"></i>Webinars</a>
          <a href="/admin/blog.php" class="list-group-item list-group-item-action"><i class="bi bi-newspaper me-2"></i>Blog News</a>
          <a href="/admin/orders.php" class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>Orders & Receipts</a>
          <a href="/admin/inquiries.php" class="list-group-item list-group-item-action"><i class="bi bi-envelope me-2"></i>Inquiries</a>
        </div>
      </div>
    </div>

    <!-- Main Content Panel -->
    <div class="col-lg-9">
      <h2 class="font-title fw-bold text-dark mb-4 h3">Internship Registrations Manager</h2>
      
      <?php if (!empty($success)): ?>
        <?php echo get_alert($success, 'success'); ?>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <?php echo get_alert($error, 'danger'); ?>
      <?php endif; ?>

      <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <h3 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Student Applications Checklist</h3>
        
        <?php if (empty($applications)): ?>
          <p class="text-muted small mb-0">No internship applications submitted yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle small">
              <thead>
                <tr class="text-muted">
                  <th>Student Info</th>
                  <th>Internship Title</th>
                  <th>Applied At</th>
                  <th>Resume</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($applications as $app): 
                  $status_badge = 'bg-warning text-dark';
                  if ($app['status'] === 'Approved') $status_badge = 'bg-success text-white';
                  if ($app['status'] === 'Rejected') $status_badge = 'bg-danger text-white';
                ?>
                  <tr>
                    <td>
                      <strong class="text-dark small d-block"><?php echo sanitize($app['student_name']); ?></strong>
                      <span class="text-muted font-monospace" style="font-size:0.75rem;"><?php echo sanitize($app['student_email']); ?></span>
                    </td>
                    <td>
                      <strong class="text-dark small d-block"><?php echo sanitize($app['intern_title']); ?></strong>
                      <span class="text-muted" style="font-size:0.75rem;"><?php echo sanitize($app['domain']); ?></span>
                    </td>
                    <td class="text-muted"><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                    <td>
                      <a href="/book_details.php?download=1" class="btn btn-outline-secondary btn-sm p-1.5 border-0 rounded-circle" title="View CV File" target="_blank">
                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                      </a>
                    </td>
                    <td>
                      <span class="badge rounded-pill <?php echo $status_badge; ?>" style="font-size:0.65rem;"><?php echo sanitize($app['status']); ?></span>
                    </td>
                    <td>
                      <form action="/admin/internships.php" method="POST" class="d-flex align-items-center gap-1">
                        <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                        <select name="status" class="form-select form-select-sm bg-light" style="width: 100px; font-size:0.75rem; border-radius:5px;" required>
                          <option value="Pending" <?php echo $app['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                          <option value="Approved" <?php echo $app['status'] === 'Approved' ? 'selected' : ''; ?>>Approve</option>
                          <option value="Rejected" <?php echo $app['status'] === 'Rejected' ? 'selected' : ''; ?>>Reject</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary btn-sm rounded-3 py-1.5"><i class="bi bi-check-lg"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
