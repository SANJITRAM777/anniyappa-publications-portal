<?php
$page_title = "Shopping Cart - Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Force authentication for e-commerce transactions
if (!is_logged_in()) {
    $_SESSION['login_redirect'] = "/cart.php";
    header("Location: /login.php");
    exit;
}

$user_id = get_logged_in_user_id();
$msg = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $book_id = (int)($_POST['book_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);

    if ($action === 'add' && $book_id > 0) {
        // Add item
        try {
            $stmt = $pdo->prepare("
                INSERT INTO cart (user_id, book_id, quantity) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity = quantity + ?
            ");
            $stmt->execute([$user_id, $book_id, $qty, $qty]);
            $msg = "Book added to cart!";
        } catch (PDOException $e) {
            $msg = "Error adding to cart: " . $e->getMessage();
        }
    } elseif ($action === 'update' && $book_id > 0) {
        // Update item quantity
        if ($qty <= 0) {
            // Delete if zero or negative
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$user_id, $book_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$qty, $user_id, $book_id]);
        }
        $msg = "Cart updated!";
    } elseif ($action === 'delete' && $book_id > 0) {
        // Remove item
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$user_id, $book_id]);
        $msg = "Item removed from cart.";
    }
}

// Fetch all items in cart
$cartItemsStmt = $pdo->prepare("
    SELECT c.*, b.title, b.price, b.cover_image, b.stock, GROUP_CONCAT(a.name SEPARATOR ', ') AS author_names
    FROM cart c
    JOIN books b ON c.book_id = b.id
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    WHERE c.user_id = ?
    GROUP BY c.id
");
$cartItemsStmt->execute([$user_id]);
$cart_items = $cartItemsStmt->fetchAll();

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container py-5">
  <h1 class="font-title fw-bold text-dark mb-4 h2"><i class="bi bi-cart3 me-2 text-primary"></i>Your Shopping Cart</h1>
  
  <?php if (!empty($msg)): ?>
    <?php echo get_alert($msg, 'success'); ?>
  <?php endif; ?>

  <?php if (empty($cart_items)): ?>
    <div class="card text-center p-5 border-0 bg-light shadow-sm" style="border-radius:15px;">
      <i class="bi bi-cart-x-fill display-2 text-muted mb-3"></i>
      <h3 class="fw-bold text-dark">Your Cart is Empty</h3>
      <p class="text-muted mb-4">You have not added any paid reference textbooks to your shopping cart yet.</p>
      <a href="/bookshelf.php" class="btn btn-primary rounded-pill px-5 py-2.5 fw-semibold"><i class="bi bi-book me-2"></i>Browse Digital Bookshelf</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <!-- Items List -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4" style="border-radius:15px;">
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr class="text-muted small">
                  <th scope="col">Book Info</th>
                  <th scope="col" class="text-center">Quantity</th>
                  <th scope="col" class="text-end">Price</th>
                  <th scope="col" class="text-end">Total</th>
                  <th scope="col" class="text-center">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cart_items as $item): 
                  $item_total = $item['price'] * $item['quantity'];
                ?>
                  <tr class="border-bottom">
                    <td style="min-width: 250px;">
                      <div class="d-flex align-items-center gap-3 py-2">
                        <div class="bg-primary text-white rounded p-2 text-center" style="width: 50px; height: 65px; display: flex; align-items:center; justify-content:center; font-size: 0.75rem; background: linear-gradient(135deg, #0f4c81, #2563eb) !important;">
                          <i class="bi bi-book"></i>
                        </div>
                        <div>
                          <h6 class="fw-bold mb-1 text-dark"><?php echo sanitize($item['title']); ?></h6>
                          <small class="text-muted d-block">Author: <?php echo sanitize($item['author_names'] ?: 'Dr. R. Anniyappa'); ?></small>
                        </div>
                      </div>
                    </td>
                    <td class="text-center" style="width: 140px;">
                      <form action="/cart.php" method="POST" class="d-inline-block">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                        <div class="input-group input-group-sm rounded-pill overflow-hidden border">
                          <input type="number" name="qty" class="form-control text-center border-0 bg-light" style="width: 55px;" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" onchange="this.form.submit()">
                        </div>
                      </form>
                    </td>
                    <td class="text-end text-dark fw-medium">&#8377;<?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-end text-primary fw-bold">&#8377;<?php echo number_format($item_total, 2); ?></td>
                    <td class="text-center">
                      <form action="/cart.php" method="POST" class="d-inline-block">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-1 border-0" aria-label="Delete">
                          <i class="bi bi-trash fs-5"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Order Summary Card -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 bg-light" style="border-radius:15px;">
          <h3 class="fw-bold text-dark h5 mb-3">Order Summary</h3>
          
          <div class="d-flex justify-content-between mb-2 small text-muted">
            <span>Subtotal</span>
            <span class="fw-semibold text-dark">&#8377;<?php echo number_format($subtotal, 2); ?></span>
          </div>
          <div class="d-flex justify-content-between mb-3 small text-muted">
            <span>Shipping / Taxes</span>
            <span class="text-success">Free Delivery</span>
          </div>
          
          <hr class="my-3">
          
          <div class="d-flex justify-content-between mb-4">
            <span class="fw-bold text-dark">Estimated Total</span>
            <span class="fw-bold text-primary fs-4">&#8377;<?php echo number_format($subtotal, 2); ?></span>
          </div>

          <a href="/checkout.php" class="btn btn-primary-custom w-100 rounded-pill py-3 fw-bold mb-2">
            Proceed to Checkout <i class="bi bi-shield-check ms-1"></i>
          </a>
          <a href="/bookshelf.php" class="btn btn-outline-secondary w-100 rounded-pill py-2.5 btn-sm">
            Continue Shopping
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
