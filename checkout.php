<?php
$page_title = "Checkout - Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: /login.php");
    exit;
}

$user_id = get_logged_in_user_id();

// Fetch cart items
$cartStmt = $pdo->prepare("
    SELECT c.*, b.title, b.price, b.cover_image 
    FROM cart c 
    JOIN books b ON c.book_id = b.id 
    WHERE c.user_id = ?
");
$cartStmt->execute([$user_id]);
$cart_items = $cartStmt->fetchAll();

if (empty($cart_items)) {
    header("Location: /bookshelf.php");
    exit;
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$coupon_code = trim($_POST['coupon_code'] ?? '');
$discount_percent = 0;
$coupon_id = null;
$coupon_msg = '';
$coupon_msg_type = 'danger';

// If session coupon is set, fetch it
if (isset($_SESSION['coupon_id'])) {
    $coupon_id = $_SESSION['coupon_id'];
    $discount_percent = $_SESSION['coupon_discount'];
    $coupon_code = $_SESSION['coupon_code'];
}

// Handle coupon submission
if (isset($_POST['apply_coupon'])) {
    if (!empty($coupon_code)) {
        $cpStmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND active = TRUE AND expiry_date >= CURDATE()");
        $cpStmt->execute([$coupon_code]);
        $coupon = $cpStmt->fetch();
        
        if ($coupon) {
            $coupon_id = $coupon['id'];
            $discount_percent = $coupon['discount_percent'];
            
            $_SESSION['coupon_id'] = $coupon_id;
            $_SESSION['coupon_discount'] = $discount_percent;
            $_SESSION['coupon_code'] = $coupon_code;
            
            $coupon_msg = "Coupon code '{$coupon_code}' applied successfully! ({$discount_percent}% off)";
            $coupon_msg_type = 'success';
        } else {
            unset($_SESSION['coupon_id']);
            unset($_SESSION['coupon_discount']);
            unset($_SESSION['coupon_code']);
            $coupon_id = null;
            $discount_percent = 0;
            $coupon_msg = "Invalid or expired coupon code.";
        }
    }
}

$discount_amount = ($subtotal * $discount_percent) / 100;
$total_amount = $subtotal - $discount_amount;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'Credit Card';
    
    if (empty($address) || empty($phone)) {
        $error = "Shipping address and phone number are required.";
    } else {
        $pdo->beginTransaction();
        try {
            $invoice_no = 'INV-' . strtoupper(dechex(time())) . '-' . rand(100, 999);
            $transaction_id = 'TXN-' . time() . '-' . rand(1000, 9999);
            $coupon_code_val = !empty($coupon_code) ? $coupon_code : null;

            // Insert Order directly with billing, shipping, discount and payment details
            $insOrder = $pdo->prepare("
                INSERT INTO orders (user_id, invoice_number, total_amount, discount_amount, coupon_code, payment_method, transaction_id, status, shipping_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Processing', ?)
            ");
            $insOrder->execute([$user_id, $invoice_no, $total_amount, $discount_amount, $coupon_code_val, $payment_method, $transaction_id, $address]);
            $order_id = $pdo->lastInsertId();
            
            // Insert Order Items (using unit_price column as in schema)
            $insItem = $pdo->prepare("
                INSERT INTO order_items (order_id, book_id, quantity, unit_price) 
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cart_items as $item) {
                $insItem->execute([$order_id, $item['book_id'], $item['quantity'], $item['price']]);
                
                // Deduct inventory
                $deductStock = $pdo->prepare("UPDATE books SET stock = stock - ? WHERE id = ?");
                $deductStock->execute([$item['quantity'], $item['book_id']]);
            }
            
            // Clear Cart
            $clearCart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $clearCart->execute([$user_id]);
            
            // Clear Session coupon
            unset($_SESSION['coupon_id']);
            unset($_SESSION['coupon_discount']);
            unset($_SESSION['coupon_code']);
            
            $pdo->commit();
            header("Location: /order_confirmation.php?order_id=" . $order_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to process order. Details: " . $e->getMessage();
        }
    }
}
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container py-5">
  <h1 class="font-title fw-bold text-dark mb-4 h2"><i class="bi bi-shield-check me-2 text-primary"></i>Checkout</h1>
  
  <?php if (!empty($error)): ?>
    <?php echo get_alert($error, 'danger'); ?>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Checkout Details Form -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm p-4 p-lg-5 mb-4" style="border-radius:15px;">
        <h3 class="fw-bold text-dark h5 mb-4 border-bottom pb-2">Shipping & Payment Details</h3>
        
        <form action="/checkout.php" method="POST">
          <div class="mb-3">
            <label for="phone" class="form-label small text-muted">Contact Phone Number <span class="text-danger">*</span></label>
            <input type="tel" name="phone" id="phone" class="form-control bg-light" required placeholder="E.g., +91 9988776655" value="<?php echo isset($_SESSION['user_phone']) ? sanitize($_SESSION['user_phone']) : ''; ?>">
          </div>
          
          <div class="mb-4">
            <label for="address" class="form-label small text-muted">Full Shipping Address <span class="text-danger">*</span></label>
            <textarea name="address" id="address" rows="4" class="form-control bg-light" required placeholder="Enter house no, street, landmark, city, and state details..."></textarea>
          </div>
          
          <div class="mb-4">
            <label class="form-label small text-muted d-block">Payment Mode <span class="text-danger">*</span></label>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="payment_method" id="payCC" value="Credit Card" checked>
              <label class="form-check-label small" for="payCC"><i class="bi bi-credit-card me-1 text-primary"></i>Credit/Debit Card</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="payment_method" id="payUPI" value="UPI">
              <label class="form-check-label small" for="payUPI"><i class="bi bi-qr-code me-1 text-primary"></i>UPI / QR Code</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="payment_method" id="payNB" value="Net Banking">
              <label class="form-check-label small" for="payNB"><i class="bi bi-bank me-1 text-primary"></i>Net Banking</label>
            </div>
          </div>
          
          <div class="p-3 bg-light rounded-3 mb-4">
            <small class="text-muted d-block"><i class="bi bi-lock me-1"></i>Secure Gateway integration Placeholder</small>
            <span class="small text-muted" style="font-size: 0.75rem;">Your transaction credentials are encrypted. Mock processing will successfully record payment parameters.</span>
          </div>

          <button type="submit" name="place_order" class="btn btn-primary-custom w-100 rounded-pill py-3 fw-bold">
            Place Order & Pay &#8377;<?php echo number_format($total_amount, 2); ?> <i class="bi bi-arrow-right-circle ms-1"></i>
          </button>
        </form>
      </div>
    </div>

    <!-- Right: Summary & Coupon -->
    <div class="col-lg-5">
      <!-- Checkout Books Summary -->
      <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius:15px;">
        <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Your Items</h3>
        <ul class="list-group list-group-flush mb-0">
          <?php foreach ($cart_items as $item): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
              <div>
                <h6 class="fw-bold mb-0 text-dark small text-truncate" style="max-width: 200px;"><?php echo sanitize($item['title']); ?></h6>
                <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
              </div>
              <span class="small text-dark fw-semibold">&#8377;<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Coupon Apply Card -->
      <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius:15px;">
        <h4 class="fw-bold text-dark h6 mb-3">Apply Coupon Discount</h4>
        
        <?php if (!empty($coupon_msg)): ?>
          <?php echo get_alert($coupon_msg, $coupon_msg_type); ?>
        <?php endif; ?>

        <form action="/checkout.php" method="POST" class="row g-2">
          <div class="col-8">
            <input type="text" name="coupon_code" class="form-control form-control-sm bg-light text-uppercase fw-bold" placeholder="E.g., WELCOME20" value="<?php echo sanitize($coupon_code); ?>">
          </div>
          <div class="col-4">
            <button type="submit" name="apply_coupon" class="btn btn-outline-primary btn-sm w-100 py-2 rounded">Apply</button>
          </div>
        </form>
        <small class="text-muted d-block mt-2" style="font-size: 0.75rem;"><i class="bi bi-lightbulb"></i> Try using <strong>WELCOME20</strong> (20% off) or <strong>ANNIYAPPA10</strong> (10% off).</small>
      </div>

      <!-- Checkout Totals Summary -->
      <div class="card border-0 shadow-sm p-4 bg-light" style="border-radius:15px;">
        <h3 class="fw-bold text-dark h5 mb-3">Total Statement</h3>
        <div class="d-flex justify-content-between mb-2 small text-muted">
          <span>Subtotal</span>
          <span>&#8377;<?php echo number_format($subtotal, 2); ?></span>
        </div>
        
        <?php if ($discount_percent > 0): ?>
          <div class="d-flex justify-content-between mb-2 small text-success">
            <span>Coupon Discount (<?php echo $discount_percent; ?>%)</span>
            <span>-&#8377;<?php echo number_format($discount_amount, 2); ?></span>
          </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between mb-3 small text-muted">
          <span>Shipping</span>
          <span class="text-success">Free</span>
        </div>
        
        <hr class="my-3">
        
        <div class="d-flex justify-content-between">
          <span class="fw-bold text-dark">Grand Total</span>
          <span class="fw-bold text-primary fs-4">&#8377;<?php echo number_format($total_amount, 2); ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
