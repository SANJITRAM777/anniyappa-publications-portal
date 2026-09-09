<?php
$page_title = "Digital Bookshelf | Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch all categories of type Book
$categoriesStmt = $pdo->query("SELECT * FROM categories WHERE type = 'Book' ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll();

// Construct base query
$search = trim($_GET['search'] ?? '');
$cat_id = (int)($_GET['category'] ?? 0);

$query = "
    SELECT b.*, c.name AS category_name, GROUP_CONCAT(a.name SEPARATOR ', ') AS author_names 
    FROM books b
    JOIN categories c ON b.category_id = c.id
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    WHERE 1=1
";

$params = [];
if (!empty($search)) {
    $query .= " AND (b.title LIKE ? OR b.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($cat_id > 0) {
    $query .= " AND b.category_id = ?";
    $params[] = $cat_id;
}

$query .= " GROUP BY b.id ORDER BY b.title ASC";
$booksStmt = $pdo->prepare($query);
$booksStmt->execute($params);
$books = $booksStmt->fetchAll();
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<!-- Page Header -->
<section class="hero-section text-center d-flex align-items-center" style="padding: 120px 0 80px; background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%); color: #fff;">
  <div class="container hero-content animate-up text-white">
    <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
      <i class="bi bi-bookshelf me-1"></i>Our Library
    </span>
    <h1 class="hero-title mb-3 fs-2 text-white">Digital Bookshelf</h1>
    <p class="hero-subtitle mx-auto mb-0 text-white-50" style="max-width: 650px;">Explore our curated collection of academic textbooks, research references, and learning materials across cutting-edge technology domains.</p>
  </div>
</section>

<!-- Bookshelf Content -->
<section class="section-padding bg-white">
  <div class="container">
    
    <!-- Search and Filter Panel -->
    <form action="/bookshelf.php" method="GET" class="mb-5">
      <div class="row g-3 justify-content-center">
        <div class="col-lg-5">
          <div class="input-group shadow-sm rounded-pill overflow-hidden border">
            <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-0 py-2.5 ps-1" placeholder="Search by title or description..." value="<?php echo sanitize($search); ?>">
          </div>
        </div>
        <div class="col-lg-3">
          <select name="category" class="form-select shadow-sm py-2.5 border rounded-pill" onchange="this.form.submit()">
            <option value="0">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>" <?php echo $cat_id == $cat['id'] ? 'selected' : ''; ?>><?php echo sanitize($cat['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-lg-2">
          <button type="submit" class="btn btn-primary-custom w-100 rounded-pill py-2.5">
            Filter <i class="bi bi-funnel ms-1"></i>
          </button>
        </div>
      </div>
    </form>

    <!-- Books Grid -->
    <div class="row g-4">
      <?php if (empty($books)): ?>
        <div class="col-12 text-center py-5">
          <i class="bi bi-book-half display-3 text-muted"></i>
          <h3 class="mt-3 text-dark">No Books Found</h3>
          <p class="text-muted">Try resetting search query filters or categories.</p>
          <a href="/bookshelf.php" class="btn btn-primary rounded-pill px-4 mt-2">View All Books</a>
        </div>
      <?php else: ?>
        <?php 
        $color_gradients = [
          'linear-gradient(135deg, #1e3a8a, #0d9488)',
          'linear-gradient(135deg, #7c3aed, #db2777)',
          'linear-gradient(135deg, #ea580c, #b45309)',
          'linear-gradient(135deg, #0f766e, #111827)',
          'linear-gradient(135deg, #4338ca, #1e40af)',
          'linear-gradient(135deg, #15803d, #166534)'
        ];
        $i = 0;
        foreach ($books as $book): 
          $grad = $color_gradients[$i % count($color_gradients)];
          $i++;
        ?>
          <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius:15px; transition: transform 0.2s;">
              <div class="book-cover text-white p-4" style="background: <?php echo $grad; ?>; height: 200px; display:flex; flex-direction:column; justify-content:space-between; position:relative;">
                <span class="small text-white-50"><?php echo sanitize($book['category_name']); ?></span>
                <h3 class="font-title fs-5 text-white mb-2"><?php echo sanitize($book['title']); ?></h3>
                <span class="small"><?php echo sanitize($book['author_names'] ?: 'Dr. R. Anniyappa'); ?></span>
                <div style="position:absolute; bottom:10px; right:15px; font-size: 1.5rem; opacity:0.15;"><i class="bi bi-journal-bookmark-fill"></i></div>
              </div>
              <div class="card-body d-flex flex-column justify-content-between p-4">
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1.5 rounded-pill small"><?php echo sanitize($book['category_name']); ?></span>
                    <span class="fw-bold text-dark fs-5">
                      <?php echo $book['price'] == 0 ? '<span class="text-success">Free</span>' : '&#8377;' . number_format($book['price'], 2); ?>
                    </span>
                  </div>
                  <h4 class="card-title fw-bold h5 text-dark mb-2"><?php echo sanitize($book['title']); ?></h4>
                  <p class="text-muted small" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; height: 60px;">
                    <?php echo sanitize($book['description']); ?>
                  </p>
                </div>
                
                <div class="mt-4 pt-3 border-top">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small text-muted">Stock: <strong><?php echo $book['stock']; ?></strong></span>
                    <a href="/book_details.php?id=<?php echo $book['id']; ?>" class="text-primary small text-decoration-none fw-semibold">View Reviews &rarr;</a>
                  </div>
                  <div class="row g-2">
                    <div class="col-6">
                      <a href="/book_details.php?id=<?php echo $book['id']; ?>" class="btn btn-outline-secondary btn-sm w-100 rounded-pill py-2">Details</a>
                    </div>
                    <div class="col-6">
                      <?php if ($book['price'] == 0): ?>
                        <a href="/book_details.php?id=<?php echo $book['id']; ?>&download=1" class="btn btn-success btn-sm w-100 rounded-pill py-2"><i class="bi bi-download me-1"></i>Download</a>
                      <?php else: ?>
                        <form action="/cart.php" method="POST">
                          <input type="hidden" name="action" value="add">
                          <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                          <input type="hidden" name="qty" value="1">
                          <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill py-2"><i class="bi bi-cart-plus me-1"></i>Buy Now</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
