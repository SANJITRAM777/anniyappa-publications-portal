<?php
$page_title = "Orders & Receipts Management - Admin | Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Admin');

$success = $error = '';

// ─── POST Actions ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_order_status') {
        $id         = (int)$_POST['order_id'];
        $new_status = trim($_POST['new_status'] ?? '');
        $allowed    = ['Pending','Processing','Shipped','Delivered','Cancelled','Refunded'];
        if (in_array($new_status, $allowed)) {
            $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$new_status, $id]);
            $success = "Order status updated to <strong>" . sanitize($new_status) . "</strong>.";
        }
    }

    if ($action === 'delete_order') {
        $id = (int)$_POST['order_id'];
        $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
        $success = "Order record deleted.";
    }
}

// ─── Fetch / Filter Orders ─────────────────────────────────────────────
$filter_status  = trim($_GET['status']   ?? '');
$filter_payment = trim($_GET['payment']  ?? '');
$search         = trim($_GET['search']   ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$per_page       = 20;
$offset         = ($page - 1) * $per_page;

$where  = "WHERE 1=1";
$params = [];
if ($filter_status) {
    $where   .= " AND o.status = ?";
    $params[] = $filter_status;
}
if ($filter_payment) {
    $where   .= " AND o.payment_method = ?";
    $params[] = $filter_payment;
}
if ($search) {
    $where   .= " AND (o.invoice_number LIKE ? OR u.email LIKE ? OR u.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Total rows for pagination
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM orders o
    JOIN users u ON o.user_id = u.id
    $where
");
$count_stmt->execute($params);
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = (int)ceil($total_rows / $per_page);

// Paginated orders
$orders_stmt = $pdo->prepare("
    SELECT o.*, u.email AS customer_email,
           COALESCE(up.full_name, u.email) AS customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    $where
    ORDER BY o.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll();

// Revenue Analytics
$analytics = $pdo->query("
    SELECT
        COUNT(*) AS total_orders,
        SUM(total_amount) AS total_revenue,
        SUM(status = 'Delivered') AS delivered,
        SUM(status = 'Pending') AS pending,
        SUM(status = 'Cancelled') AS cancelled,
        SUM(status = 'Refunded') AS refunded,
        AVG(total_amount) AS avg_order
    FROM orders
")->fetch();

// Monthly revenue (last 6 months)
$monthly = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') AS month_label,
           SUM(total_amount) AS revenue
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m')
    ORDER BY MIN(created_at) ASC
")->fetchAll();

$months_labels  = json_encode(array_column($monthly, 'month_label'));
$months_revenue = json_encode(array_column($monthly, 'revenue'));
?>
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
  <div class="row g-4">

    <!-- Sidebar -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm p-4 text-center bg-light" style="border-radius:15px;">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 mx-auto mb-3" style="width:70px;height:70px;">A</div>
        <h4 class="fw-bold text-dark h5 mb-1">Portal Admin</h4>
        <small class="text-muted d-block mb-3">System Administrator</small>
        <hr class="my-3">
        <div class="list-group list-group-flush text-start small" style="border-radius:10px;overflow:hidden;">
          <a href="/admin/dashboard.php"   class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
          <a href="/admin/users.php"       class="list-group-item list-group-item-action"><i class="bi bi-people me-2"></i>User Roles</a>
          <a href="/admin/books.php"       class="list-group-item list-group-item-action"><i class="bi bi-book me-2"></i>Manage Books</a>
          <a href="/admin/internships.php" class="list-group-item list-group-item-action"><i class="bi bi-briefcase me-2"></i>Internship Apps</a>
          <a href="/admin/courses.php"     class="list-group-item list-group-item-action"><i class="bi bi-laptop me-2"></i>LMS Courses</a>
          <a href="/admin/events.php"      class="list-group-item list-group-item-action"><i class="bi bi-calendar-event me-2"></i>Webinars</a>
          <a href="/admin/blog.php"        class="list-group-item list-group-item-action"><i class="bi bi-newspaper me-2"></i>Blog News</a>
          <a href="/admin/orders.php"      class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-receipt me-2"></i>Orders &amp; Receipts</a>
          <a href="/admin/inquiries.php"   class="list-group-item list-group-item-action"><i class="bi bi-envelope me-2"></i>Inquiries</a>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9">
      <h2 class="font-title fw-bold text-dark h3 mb-4"><i class="bi bi-receipt me-2 text-primary"></i>Orders &amp; Receipts</h2>

      <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show small rounded-3">
          <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- ── Analytics Widgets ─────────────────────────────────────── -->
      <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Total Revenue</span>
            <strong class="text-success fs-4">&#8377;<?php echo number_format($analytics['total_revenue'] ?? 0, 2); ?></strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Total Orders</span>
            <strong class="text-primary fs-4"><?php echo $analytics['total_orders'] ?? 0; ?></strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Avg. Order Value</span>
            <strong class="text-info fs-4">&#8377;<?php echo number_format($analytics['avg_order'] ?? 0, 2); ?></strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Delivered</span>
            <strong class="text-success fs-4"><?php echo $analytics['delivered'] ?? 0; ?></strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Pending</span>
            <strong class="text-warning fs-4"><?php echo $analytics['pending'] ?? 0; ?></strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Cancelled</span>
            <strong class="text-danger fs-4"><?php echo $analytics['cancelled'] ?? 0; ?></strong>
          </div>
        </div>
      </div>

      <!-- Revenue Chart -->
      <?php if (!empty($monthly)): ?>
      <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius:15px;">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-graph-up me-2 text-primary"></i>Monthly Revenue — Last 6 Months</h6>
        <canvas id="revenueChart" height="90"></canvas>
      </div>
      <?php endif; ?>

      <!-- Search & Filter -->
      <form method="GET" class="row g-2 mb-3 align-items-end">
        <div class="col-md-4">
          <input type="text" name="search" class="form-control rounded-pill"
                 placeholder="Invoice #, email…" value="<?php echo sanitize($search); ?>">
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select rounded-pill">
            <option value="">All Statuses</option>
            <?php foreach (['Pending','Processing','Shipped','Delivered','Cancelled','Refunded'] as $s): ?>
              <option value="<?php echo $s; ?>" <?php echo $filter_status===$s?'selected':''; ?>><?php echo $s; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select name="payment" class="form-select rounded-pill">
            <option value="">All Payment Methods</option>
            <option value="UPI"    <?php echo $filter_payment==='UPI'?'selected':''; ?>>UPI</option>
            <option value="Card"   <?php echo $filter_payment==='Card'?'selected':''; ?>>Card</option>
            <option value="COD"    <?php echo $filter_payment==='COD'?'selected':''; ?>>COD</option>
            <option value="NetBanking" <?php echo $filter_payment==='NetBanking'?'selected':''; ?>>Net Banking</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-primary rounded-pill w-100"><i class="bi bi-search"></i></button>
        </div>
      </form>

      <!-- Orders Table -->
      <div class="card border-0 shadow-sm mb-3" style="border-radius:15px;overflow:hidden;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Payment</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($orders)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No orders match the criteria.</td></tr>
              <?php else: ?>
                <?php foreach ($orders as $oi => $ord): ?>
                  <?php
                  $badge = match($ord['status']) {
                      'Pending'    => 'bg-warning text-dark',
                      'Processing' => 'bg-info text-dark',
                      'Shipped'    => 'bg-primary',
                      'Delivered'  => 'bg-success',
                      'Cancelled'  => 'bg-danger',
                      'Refunded'   => 'bg-secondary',
                      default      => 'bg-light text-dark'
                  };
                  ?>
                  <tr>
                    <td class="fw-bold text-muted"><?php echo $offset + $oi + 1; ?></td>
                    <td>
                      <a href="#" class="text-primary fw-semibold text-decoration-none"
                         data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $ord['id']; ?>">
                        <?php echo sanitize($ord['invoice_number']); ?>
                      </a>
                    </td>
                    <td>
                      <div><?php echo sanitize($ord['customer_name']); ?></div>
                      <small class="text-muted"><?php echo sanitize($ord['customer_email']); ?></small>
                    </td>
                    <td class="fw-bold text-dark">&#8377;<?php echo number_format($ord['total_amount'], 2); ?></td>
                    <td><span class="badge bg-light text-dark border"><?php echo sanitize($ord['payment_method'] ?? 'N/A'); ?></span></td>
                    <td style="white-space:nowrap;"><?php echo date('d M Y', strtotime($ord['created_at'])); ?></td>
                    <td><span class="badge rounded-pill <?php echo $badge; ?>"><?php echo sanitize($ord['status']); ?></span></td>
                    <td>
                      <div class="d-flex gap-1">
                        <!-- View Invoice -->
                        <a href="/order_confirmation.php?invoice=<?php echo urlencode($ord['invoice_number']); ?>"
                           target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" title="Invoice">
                          <i class="bi bi-printer"></i>
                        </a>
                        <!-- Status Update Dropdown -->
                        <div class="dropdown">
                          <button class="btn btn-sm btn-outline-primary rounded-pill py-0 px-2 dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:130px;">
                            <?php foreach (['Pending','Processing','Shipped','Delivered','Cancelled','Refunded'] as $ns): ?>
                              <li>
                                <form method="POST" class="px-2 py-1">
                                  <input type="hidden" name="action" value="update_order_status">
                                  <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                  <input type="hidden" name="new_status" value="<?php echo $ns; ?>">
                                  <button class="dropdown-item rounded-2 small py-1 <?php echo $ord['status']===$ns?'active':''; ?>"><?php echo $ns; ?></button>
                                </form>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        </div>
                        <!-- Delete -->
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this order record permanently?')">
                          <input type="hidden" name="action" value="delete_order">
                          <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                          <button class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <nav>
          <ul class="pagination pagination-sm justify-content-center gap-1">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
              <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                <a class="page-link rounded-pill"
                   href="?page=<?php echo $p; ?>&status=<?php echo urlencode($filter_status); ?>&payment=<?php echo urlencode($filter_payment); ?>&search=<?php echo urlencode($search); ?>">
                  <?php echo $p; ?>
                </a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>

    </div><!-- /col -->
  </div><!-- /row -->
</div><!-- /container -->

<!-- Order Detail Modals -->
<?php foreach ($orders as $ord):
    $items_stmt = $pdo->prepare("
        SELECT oi.*, b.title AS book_title
        FROM order_items oi
        LEFT JOIN books b ON oi.book_id = b.id
        WHERE oi.order_id = ?
    ");
    $items_stmt->execute([$ord['id']]);
    $items = $items_stmt->fetchAll();
?>
<div class="modal fade" id="orderModal<?php echo $ord['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-dark text-white rounded-top-4">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-receipt me-2"></i>Invoice: <?php echo sanitize($ord['invoice_number']); ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row mb-3">
          <div class="col-md-6">
            <p class="mb-1 small"><strong>Customer:</strong> <?php echo sanitize($ord['customer_name']); ?></p>
            <p class="mb-1 small"><strong>Email:</strong> <?php echo sanitize($ord['customer_email']); ?></p>
            <p class="mb-1 small"><strong>Payment:</strong> <?php echo sanitize($ord['payment_method'] ?? '—'); ?></p>
          </div>
          <div class="col-md-6 text-md-end">
            <p class="mb-1 small"><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($ord['created_at'])); ?></p>
            <p class="mb-1 small"><strong>Coupon:</strong> <?php echo sanitize($ord['coupon_code'] ?: 'None'); ?></p>
            <p class="mb-1 small"><strong>Discount:</strong> &#8377;<?php echo number_format($ord['discount_amount'] ?? 0, 2); ?></p>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered small align-middle mb-0">
            <thead class="table-light">
              <tr><th>Book</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="4" class="text-center text-muted">No items found.</td></tr>
              <?php else: ?>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td><?php echo sanitize($it['book_title'] ?? 'Book #' . $it['book_id']); ?></td>
                    <td class="text-center"><?php echo (int)$it['quantity']; ?></td>
                    <td>&#8377;<?php echo number_format($it['unit_price'], 2); ?></td>
                    <td class="fw-semibold">&#8377;<?php echo number_format($it['quantity'] * $it['unit_price'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
            <tfoot class="table-light">
              <tr>
                <td colspan="3" class="text-end fw-bold">Total Amount</td>
                <td class="fw-bold text-success">&#8377;<?php echo number_format($ord['total_amount'], 2); ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0">
        <a href="/order_confirmation.php?invoice=<?php echo urlencode($ord['invoice_number']); ?>"
           target="_blank" class="btn btn-outline-dark rounded-pill px-4">
          <i class="bi bi-printer me-1"></i> Print Invoice
        </a>
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- Chart.js Revenue Graph -->
<?php if (!empty($monthly)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('revenueChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?php echo $months_labels; ?>,
      datasets: [{
        label: 'Revenue (₹)',
        data: <?php echo $months_revenue; ?>,
        backgroundColor: 'rgba(13,110,253,0.7)',
        borderColor: 'rgba(13,110,253,1)',
        borderWidth: 2,
        borderRadius: 6,
        hoverBackgroundColor: 'rgba(13,110,253,0.9)'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => '₹' + parseFloat(ctx.raw).toLocaleString('en-IN', {minimumFractionDigits: 2})
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: val => '₹' + Number(val).toLocaleString('en-IN')
          }
        }
      }
    }
  });
});
</script>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
