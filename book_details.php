<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$book_id = (int)($_GET['id'] ?? 0);

// Fetch book details
$stmt = $pdo->prepare("
    SELECT b.*, c.name AS category_name, GROUP_CONCAT(a.name SEPARATOR ', ') AS author_names, GROUP_CONCAT(a.bio SEPARATOR '\n\n') AS author_bios
    FROM books b
    JOIN categories c ON b.category_id = c.id
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    WHERE b.id = ?
    GROUP BY b.id
");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    header("Location: /bookshelf.php");
    exit;
}

$page_title = sanitize($book['title']) . " - Anniyappa Publications";

// Handle free download tracking
if (isset($_GET['download']) && $book['price'] == 0) {
    if (is_logged_in()) {
        try {
            $dlStmt = $pdo->prepare("INSERT INTO downloads (book_id, user_id) VALUES (?, ?)");
            $dlStmt->execute([$book_id, get_logged_in_user_id()]);
        } catch (PDOException $e) {
            // Log silenty
        }
        
        // Output mock pdf download or alert
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $book['title']) . '_Sample.pdf"');
        echo "%PDF-1.4\n1 0 obj\n<< /Title (Sample) >>\nendobj\nxref\n0 1\n0000000000 65535 f\ntrailer\n<< /Size 2 >>\nstartxref\n10\n%%EOF\n";
        exit;
    } else {
        $_SESSION['login_redirect'] = "/book_details.php?id=$book_id&download=1";
        header("Location: /login.php");
        exit;
    }
}

// Handle review submission
$review_success = '';
$review_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!is_logged_in()) {
        $_SESSION['login_redirect'] = "/book_details.php?id=$book_id";
        header("Location: /login.php");
        exit;
    }
    
    $rating = (int)($_POST['rating'] ?? 5);
    $review_text = trim($_POST['review_text'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $review_error = "Please select a rating between 1 and 5.";
    } else {
        try {
            $insReview = $pdo->prepare("INSERT INTO reviews (book_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
            $insReview->execute([$book_id, get_logged_in_user_id(), $rating, $review_text]);
            $review_success = "Thank you! Your review has been published.";
        } catch (PDOException $e) {
            $review_error = "You have already reviewed this book.";
        }
    }
}

// Fetch reviews
$reviewsStmt = $pdo->prepare("
    SELECT r.*, p.full_name, p.profile_pic 
    FROM reviews r 
    JOIN user_profiles p ON r.user_id = p.user_id 
    WHERE r.book_id = ? 
    ORDER BY r.created_at DESC
");
$reviewsStmt->execute([$book_id]);
$reviews = $reviewsStmt->fetchAll();

// Fetch average rating
$avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE book_id = ?");
$avgStmt->execute([$book_id]);
$rating_summary = $avgStmt->fetch();
$avg_rating = round($rating_summary['avg_rating'] ?? 0, 1);
$review_count = (int)$rating_summary['count'];

// Fetch recommendations
$recStmt = $pdo->prepare("
    SELECT id, title, price, cover_image 
    FROM books 
    WHERE category_id = ? AND id != ? 
    LIMIT 3
");
$recStmt->execute([$book['category_id'], $book_id]);
$recommendations = $recStmt->fetchAll();
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container py-5">
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="/index.php" class="text-decoration-none">Home</a></li>
      <li class="breadcrumb-item"><a href="/bookshelf.php" class="text-decoration-none">Bookshelf</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?php echo sanitize($book['title']); ?></li>
    </ol>
  </nav>

  <div class="row g-5">
    <!-- Cover Display -->
    <div class="col-lg-4 text-center">
      <div class="card border-0 shadow-lg p-3 bg-light text-white rounded-4 mb-4" style="min-height: 380px; background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%); display:flex; flex-direction:column; justify-content:space-between;">
        <span class="badge bg-white text-primary align-self-start px-3 py-2 rounded-pill"><?php echo sanitize($book['category_name']); ?></span>
        <div class="py-5">
          <i class="bi bi-journal-bookmark-fill text-warning" style="font-size: 4rem;"></i>
          <h2 class="font-title fs-3 mt-3 text-white"><?php echo sanitize($book['title']); ?></h2>
        </div>
        <p class="mb-0 text-white-50 small">Anniyappa Academic Publications</p>
      </div>

      <!-- Action Area -->
      <div class="p-4 border rounded-4 bg-white shadow-sm">
        <h3 class="fw-bold text-dark fs-4 mb-3">
          <?php echo $book['price'] == 0 ? '<span class="text-success">Free E-Book</span>' : '&#8377;' . number_format($book['price'], 2); ?>
        </h3>
        
        <?php if ($book['price'] == 0): ?>
          <a href="/book_details.php?id=<?php echo $book_id; ?>&download=1" class="btn btn-success w-100 rounded-pill py-2.5 fw-bold mb-2">
            <i class="bi bi-download me-2"></i>Download PDF File
          </a>
          <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Logged in users can download PDF reference modules directly.</p>
        <?php else: ?>
          <form action="/cart.php" method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
            
            <div class="d-flex align-items-center mb-3">
              <label for="qty" class="me-3 text-muted small">Quantity:</label>
              <input type="number" id="qty" name="qty" class="form-control text-center bg-light border-0 fw-bold" style="width: 80px; border-radius: 10px;" value="1" min="1" max="<?php echo $book['stock']; ?>">
              <span class="small text-muted ms-3"><?php echo $book['stock']; ?> copies available</span>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 rounded-pill py-2.5 fw-bold">
              <i class="bi bi-cart-plus me-2"></i>Add to Shopping Cart
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Metadata Details -->
    <div class="col-lg-8">
      <div class="p-4 p-lg-5 bg-white border rounded-4 shadow-sm">
        <span class="text-primary fw-semibold text-uppercase" style="font-size:0.85rem; letter-spacing:1px;"><?php echo sanitize($book['category_name']); ?></span>
        <h1 class="font-title text-dark fw-bold mt-1 mb-3" style="font-size: 2.2rem;"><?php echo sanitize($book['title']); ?></h1>
        
        <div class="d-flex align-items-center mb-4 flex-wrap gap-3">
          <div class="d-flex align-items-center">
            <?php echo get_star_rating((int)$avg_rating); ?>
            <span class="text-dark fw-bold ms-2"><?php echo $avg_rating; ?></span>
            <span class="text-muted small ms-1">(<?php echo $review_count; ?> reviews)</span>
          </div>
          <div class="border-start ps-3 text-muted small">
            Author: <strong class="text-dark"><?php echo sanitize($book['author_names'] ?: 'Dr. R. Anniyappa'); ?></strong>
          </div>
        </div>

        <h3 class="fw-bold h5 text-dark border-bottom pb-2 mb-3">Book Description</h3>
        <p class="text-dark" style="line-height:1.7;"><?php echo nl2br(sanitize($book['description'])); ?></p>
        
        <div class="row g-3 my-4">
          <div class="col-sm-6">
            <div class="p-3 bg-light rounded-3">
              <span class="small text-muted d-block">Language</span>
              <strong class="text-dark">English</strong>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="p-3 bg-light rounded-3">
              <span class="small text-muted d-block">DOI Index / ISBN</span>
              <strong class="text-dark">978-3-16-1484-<?php echo 10 + $book_id; ?></strong>
            </div>
          </div>
        </div>

        <!-- Author Profile -->
        <div class="my-5 p-4 bg-light rounded-4">
          <h4 class="fw-bold h5 text-dark mb-3"><i class="bi bi-person-workspace me-2 text-primary"></i>About the Author</h4>
          <div class="d-flex align-items-center gap-3 mb-2">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 50px; height: 50px;">
              <?php echo substr($book['author_names'] ?: 'A', 0, 1); ?>
            </div>
            <div>
              <h5 class="fw-bold mb-0 text-dark"><?php echo sanitize($book['author_names'] ?: 'Dr. R. Anniyappa'); ?></h5>
              <small class="text-muted">Academic Contributor</small>
            </div>
          </div>
          <p class="text-muted small mb-0 mt-2"><?php echo nl2br(sanitize($book['author_bios'] ?: 'Scholarly researcher and editor at Anniyappa Publications.')); ?></p>
        </div>

        <!-- Recommended Books -->
        <?php if (!empty($recommendations)): ?>
          <div class="my-5">
            <h4 class="fw-bold h5 text-dark mb-4"><i class="bi bi-star me-2 text-primary"></i>Recommended Books</h4>
            <div class="row g-3">
              <?php foreach ($recommendations as $rec): ?>
                <div class="col-sm-4">
                  <div class="card h-100 border text-center p-3 shadow-xs" style="border-radius:12px; cursor:pointer;" onclick="window.location.href='/book_details.php?id=<?php echo $rec['id']; ?>'">
                    <i class="bi bi-book text-primary fs-2 mb-2 d-block"></i>
                    <h6 class="fw-bold small mb-1 text-dark text-truncate"><?php echo sanitize($rec['title']); ?></h6>
                    <span class="small text-muted"><?php echo $rec['price'] == 0 ? 'Free' : '&#8377;' . number_format($rec['price'], 2); ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Customer Reviews -->
        <div class="mt-5 border-top pt-5">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold h5 text-dark mb-0"><i class="bi bi-chat-left-text me-2 text-primary"></i>Reader Reviews</h4>
            <span class="badge bg-secondary px-3 py-2 rounded-pill"><?php echo $review_count; ?> Comments</span>
          </div>

          <?php if (!empty($review_success)): ?>
            <?php echo get_alert($review_success, 'success'); ?>
          <?php endif; ?>

          <?php if (!empty($review_error)): ?>
            <?php echo get_alert($review_error, 'danger'); ?>
          <?php endif; ?>

          <!-- Review Form -->
          <form action="/book_details.php?id=<?php echo $book_id; ?>" method="POST" class="mb-5 p-4 border rounded-3 bg-light">
            <h5 class="fw-bold text-dark h6 mb-3">Write a Review</h5>
            <div class="row g-3">
              <div class="col-md-4">
                <label for="rating" class="form-label small text-muted">Select Stars</label>
                <select name="rating" id="rating" class="form-select bg-white" required>
                  <option value="5">5 Stars (Excellent)</option>
                  <option value="4">4 Stars (Good)</option>
                  <option value="3">3 Stars (Average)</option>
                  <option value="2">2 Stars (Poor)</option>
                  <option value="1">1 Star (Very Poor)</option>
                </select>
              </div>
              <div class="col-12">
                <label for="review_text" class="form-label small text-muted">Your Review / Comments</label>
                <textarea name="review_text" id="review_text" rows="3" class="form-control bg-white" placeholder="Share your experience reading this book..." required></textarea>
              </div>
              <div class="col-12">
                <button type="submit" name="submit_review" class="btn btn-primary rounded-pill px-4 btn-sm">
                  Publish Review <i class="bi bi-send-fill ms-1"></i>
                </button>
              </div>
            </div>
          </form>

          <!-- Reviews Feed -->
          <?php if (empty($reviews)): ?>
            <p class="text-muted text-center py-4">No reviews yet. Be the first to share your thoughts!</p>
          <?php else: ?>
            <div class="reviews-feed">
              <?php foreach ($reviews as $rev): ?>
                <div class="d-flex gap-3 mb-4 pb-3 border-bottom align-items-start">
                  <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold text-uppercase" style="width: 40px; height: 40px; flex-shrink: 0;">
                    <?php echo substr($rev['full_name'], 0, 1); ?>
                  </div>
                  <div>
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                      <h6 class="fw-bold text-dark mb-0 small"><?php echo sanitize($rev['full_name']); ?></h6>
                      <span class="text-muted small" style="font-size:0.75rem;"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                    </div>
                    <div class="mb-2">
                      <?php echo get_star_rating($rev['rating']); ?>
                    </div>
                    <p class="text-muted small mb-0"><?php echo nl2br(sanitize($rev['review_text'])); ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
