<?php
$page_title = "User Management - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Admin');

$success = '';
$error = '';

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $role_id = (int)($_POST['role_id'] ?? 0);
    
    if ($user_id > 0 && $role_id > 0) {
        // Prevent editing own role for safety
        if ($user_id === get_logged_in_user_id()) {
            $error = "For safety reasons, you cannot change your own role.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                $stmt->execute([$role_id, $user_id]);
                $success = "User access role updated successfully!";
            } catch (PDOException $e) {
                $error = "Failed to update role: " . $e->getMessage();
            }
        }
    }
}

// Fetch users
$usersStmt = $pdo->query("
    SELECT u.id, u.email, u.created_at, r.name AS role_name, r.id AS role_id, p.full_name, p.phone
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN user_profiles p ON u.id = p.user_id
    ORDER BY u.created_at DESC
");
$users = $usersStmt->fetchAll();

// Fetch roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
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
          <a href="/admin/users.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-people me-2"></i>User Roles</a>
          <a href="/admin/books.php" class="list-group-item list-group-item-action"><i class="bi bi-book me-2"></i>Manage Books</a>
          <a href="/admin/internships.php" class="list-group-item list-group-item-action"><i class="bi bi-briefcase me-2"></i>Internship Apps</a>
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
      <h2 class="font-title fw-bold text-dark mb-4 h3">Manage User Access Roles</h2>
      
      <?php if (!empty($success)): ?>
        <?php echo get_alert($success, 'success'); ?>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <?php echo get_alert($error, 'danger'); ?>
      <?php endif; ?>

      <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <h3 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Registered Accounts List</h3>
        
        <div class="table-responsive">
          <table class="table align-middle small">
            <thead>
              <tr class="text-muted">
                <th>Account Holder</th>
                <th>Email Address</th>
                <th>Joined At</th>
                <th>Current Role</th>
                <th>Assign Role</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr>
                  <td>
                    <strong class="text-dark small d-block"><?php echo sanitize($user['full_name'] ?: 'No Profile Created'); ?></strong>
                    <small class="text-muted"><?php echo sanitize($user['phone'] ?: 'No Phone Number'); ?></small>
                  </td>
                  <td class="text-muted font-monospace"><?php echo sanitize($user['email']); ?></td>
                  <td class="text-muted"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                  <td>
                    <span class="badge bg-primary px-3 py-1.5 rounded-pill" style="font-size:0.65rem;"><?php echo sanitize($user['role_name']); ?></span>
                  </td>
                  <td>
                    <form action="/admin/users.php" method="POST" class="d-flex align-items-center gap-1">
                      <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                      <select name="role_id" class="form-select form-select-sm bg-light" style="width: 120px; font-size:0.75rem; border-radius:5px;" required>
                        <?php foreach ($roles as $r): ?>
                          <option value="<?php echo $r['id']; ?>" <?php echo $user['role_id'] == $r['id'] ? 'selected' : ''; ?>><?php echo sanitize($r['name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" name="update_role" class="btn btn-primary btn-sm rounded-3 py-1.5"><i class="bi bi-check-lg"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
