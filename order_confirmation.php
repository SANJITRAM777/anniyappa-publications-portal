<?php
$page_title = "Order Invoice - Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: /login.php");
    exit;
}

$order_id = (int)($_GET['order_id'] ?? 0);
$user_id = get_logged_in_user_id();

// Fetch order details directly from orders table (unified schema)
$orderStmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ? AND user_id = ?
");
$orderStmt->execute([$order_id, $user_id]);
$order = $orderStmt->fetch();

if (!$order) {
    echo "Order not found or access denied.";
    exit;
}

// Fetch order items
$itemsStmt = $pdo->prepare("
    SELECT oi.*, b.title, b.cover_image
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll();

// Fetch user profile
$profStmt = $pdo->prepare("SELECT full_name, phone FROM user_profiles WHERE user_id = ?");
$profStmt->execute([$user_id]);
$profile = $profStmt->fetch();
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <!-- Success Badge Banner -->
      <div class="card border-0 bg-success bg-opacity-10 text-success p-4 mb-4 text-center" style="border-radius: 15px;">
        <i class="bi bi-patch-check-fill display-3 mb-2"></i>
        <h2 class="fw-bold font-title">Order Completed Successfully!</h2>
        <p class="mb-0">Thank you for your purchase. Your payment has been verified, and your digital access is now active.</p>
      </div>

      <!-- Printable Invoice Area -->
      <div class="card border shadow p-4 p-md-5 bg-white" style="border-radius:15px;" id="invoiceArea">
        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4 flex-wrap gap-3">
          <div>
            <h1 class="font-title text-primary fw-bold mb-1" style="font-size:1.8rem;"><i class="bi bi-journal-bookmark-fill me-2"></i>Anniyappa Publications</h1>
            <small class="text-muted d-block">SB Institute Academic Initiative, Bangalore, India</small>
          </div>
          <div class="text-md-end">
            <h2 class="h5 fw-bold text-dark mb-1">INVOICE</h2>
            <span class="text-muted small">No: <strong><?php echo sanitize($order['invoice_number']); ?></strong></span>
            <br>
            <span class="text-muted small">Date: <?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-sm-6 mb-3 mb-sm-0">
            <h6 class="text-muted small text-uppercase fw-bold mb-2">Billed To:</h6>
            <strong class="text-dark d-block"><?php echo sanitize($profile['full_name'] ?? 'Registered Customer'); ?></strong>
            <span class="text-muted small">Email: <?php echo sanitize($_SESSION['user_email']); ?></span>
            <br>
            <span class="text-muted small">Phone: <?php echo sanitize($profile['phone'] ?? 'N/A'); ?></span>
          </div>
          <div class="col-sm-6 text-sm-end">
            <h6 class="text-muted small text-uppercase fw-bold mb-2">Transaction Info:</h6>
            <span class="text-muted small">Status: <strong class="text-success"><?php echo sanitize($order['status'] ?: 'Success'); ?></strong></span>
            <br>
            <span class="text-muted small">Method: <?php echo sanitize($order['payment_method'] ?: 'UPI'); ?></span>
            <br>
            <span class="text-muted small">Transaction ID: <strong class="text-dark"><?php echo sanitize($order['transaction_id'] ?: 'N/A'); ?></strong></span>
          </div>
        </div>

        <!-- Order Items Statement Table -->
        <table class="table align-middle mb-4">
          <thead>
            <tr class="table-light text-muted small">
              <th scope="col">Purchased Publication</th>
              <th scope="col" class="text-center">Qty</th>
              <th scope="col" class="text-end">Unit Price</th>
              <th scope="col" class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $subtotal = 0;
            foreach ($items as $item): 
              $item_total = $item['price'] * $item['quantity'];
              $subtotal += $item_total;
            ?>
              <tr>
                <td>
                  <h6 class="fw-bold text-dark mb-0 small"><?php echo sanitize($item['title']); ?></h6>
                </td>
                <td class="text-center text-dark small"><?php echo $item['quantity']; ?></td>
                <td class="text-end text-dark small">&#8377;<?php echo number_format($item['price'], 2); ?></td>
                <td class="text-end text-dark fw-bold">&#8377;<?php echo number_format($item_total, 2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Summary Totals -->
        <div class="row justify-content-end mb-4">
          <div class="col-md-5">
            <div class="d-flex justify-content-between mb-2 small text-muted">
              <span>Subtotal:</span>
              <span class="text-dark fw-semibold">&#8377;<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php if ($order['discount_amount'] > 0): ?>
              <div class="d-flex justify-content-between mb-2 small text-success">
                <span>Coupon Applied (<?php echo sanitize($order['coupon_code']); ?>):</span>
                <span>-&#8377;<?php echo number_format($order['discount_amount'], 2); ?></span>
              </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between mb-3 small text-muted">
              <span>Shipping Charge:</span>
              <span class="text-success">Free</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between">
              <span class="fw-bold text-dark">Grand Total Paid:</span>
              <span class="fw-bold text-primary fs-5">&#8377;<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
          </div>
        </div>

        <hr class="my-4">
        
        <div class="text-center text-muted small">
          <p class="mb-1">This is a system-generated electronic receipt issued by Anniyappa Publications portal.</p>
          <p class="mb-0">&copy; <?php echo date('Y'); ?> Anniyappa Publications. All Rights Reserved.</p>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="d-flex gap-3 justify-content-center mt-4">
        <button onclick="window.print();" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-printer me-2"></i>Print Invoice</button>
        <a href="/bookshelf.php" class="btn btn-primary rounded-pill px-4 fw-semibold"><i class="bi bi-book me-2"></i>Go to Bookshelf</a>
        
        <?php if (has_role('Student')): ?>
          <a href="/student/dashboard.php" class="btn btn-outline-primary rounded-pill px-4">My Dashboard</a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
