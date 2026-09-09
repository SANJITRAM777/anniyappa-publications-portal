<?php
$page_title = "Admin Dashboard - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Access Control: require Admin
require_role('Admin');

// Fetch Metrics
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$books_count = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$interns_count = $pdo->query("SELECT COUNT(*) FROM internships WHERE status = 'Active'")->fetchColumn();
$enrolls_count = $pdo->query("SELECT COUNT(*) FROM course_enrollments")->fetchColumn();
$orders_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn() ?: 0.00;

// Fetch recent orders
$recentOrdersStmt = $pdo->query("
    SELECT o.*, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$recent_orders = $recentOrdersStmt->fetchAll();

// Fetch recent inquiries
$recentInquiriesStmt = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5");
$recent_inquiries = $recentInquiriesStmt->fetchAll();
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
          <a href="/admin/dashboard.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
          <a href="/admin/users.php" class="list-group-item list-group-item-action"><i class="bi bi-people me-2"></i>User Roles</a>
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
      <h2 class="font-title fw-bold text-dark mb-4 h3">Dashboard Analytics</h2>
      
      <!-- Metrics Dashboard Widgets -->
      <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Total Users</span>
            <strong class="text-primary fs-4"><?php echo $users_count; ?></strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Digital Catalog</span>
            <strong class="text-primary fs-4"><?php echo $books_count; ?> Books</strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Active Internships</span>
            <strong class="text-primary fs-4"><?php echo $interns_count; ?> Programs</strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">LMS Enrollments</span>
            <strong class="text-primary fs-4"><?php echo $enrolls_count; ?> Enrolls</strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Total Orders</span>
            <strong class="text-primary fs-4"><?php echo $orders_count; ?> Purchases</strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Revenue Earned</span>
            <strong class="text-success fs-4">&#8377;<?php echo number_format($revenue, 2); ?></strong>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Recent Orders Audit List -->
        <div class="col-md-6">
          <div class="card border-0 shadow-sm p-4 bg-white h-100" style="border-radius:15px;">
            <h3 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Recent Transactions</h3>
            <?php if (empty($recent_orders)): ?>
              <p class="text-muted small">No order payments logged yet.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush mb-0 small">
                <?php foreach ($recent_orders as $ord): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent py-2">
                    <div>
                      <strong class="text-dark d-block" style="font-size:0.75rem;"><?php echo sanitize($ord['invoice_number']); ?></strong>
                      <span class="text-muted" style="font-size:0.7rem;"><?php echo sanitize($ord['email']); ?></span>
                    </div>
                    <span class="fw-bold text-primary">&#8377;<?php echo number_format($ord['total_amount'], 2); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
              <a href="/admin/orders.php" class="btn btn-outline-primary btn-sm rounded-pill mt-3 py-1 px-3 align-self-start">View All Orders</a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Recent Contact Inquiries -->
        <div class="col-md-6">
          <div class="card border-0 shadow-sm p-4 bg-white h-100" style="border-radius:15px;">
            <h3 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Recent Inquiries</h3>
            <?php if (empty($recent_inquiries)): ?>
              <p class="text-muted small">No inquiries submitted yet.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush mb-0 small">
                <?php foreach ($recent_inquiries as $inq): ?>
                  <li class="list-group-item px-0 bg-transparent py-2">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                      <strong class="text-dark small d-block"><?php echo sanitize($inq['name']); ?></strong>
                      <small class="text-muted" style="font-size: 0.7rem;"><?php echo date('d-M', strtotime($inq['created_at'])); ?></small>
                    </div>
                    <span class="text-muted d-block text-truncate" style="font-size: 0.75rem; max-width:280px;"><?php echo sanitize($inq['subject']); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
              <a href="/admin/inquiries.php" class="btn btn-outline-primary btn-sm rounded-pill mt-3 py-1 px-3 align-self-start">View Inquiries Panel</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
