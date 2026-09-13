<?php
$page_title = "Book Management - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Admin');

$success = '';
$error = '';

// Handle add book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0.00);
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    if (empty($title) || $category_id <= 0 || $price < 0 || $stock < 0) {
        $error = "Please fill in all required fields correctly.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO books (title, category_id, price, stock, description, is_featured, cover_image) 
                VALUES (?, ?, ?, ?, ?, ?, 'default_book.png')
            ");
            $stmt->execute([$title, $category_id, $price, $stock, $description, $is_featured]);
            
            // Map to existing author or default author
            $authorCheck = $pdo->query("SELECT id FROM authors ORDER BY id ASC LIMIT 1")->fetchColumn();
            $author_id = $authorCheck ?: 1;
            if (!$authorCheck) {
                $pdo->exec("INSERT IGNORE INTO authors (id, name, bio) VALUES (1, 'Dr. R. Anniyappa', 'Chief Editor')");
            }
            $mapStmt = $pdo->prepare("INSERT IGNORE INTO book_authors (book_id, author_id) VALUES (?, ?)");
            $mapStmt->execute([$book_id, $author_id]);
            
            $success = "Book added to digital catalog successfully!";
        } catch (PDOException $e) {
            $error = "Failed to add book: " . $e->getMessage();
        }
    }
}

// Handle delete book
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$del_id]);
        $success = "Book removed from catalog successfully.";
    } catch (PDOException $e) {
        $error = "Failed to remove book (it may be referenced in orders): " . $e->getMessage();
    }
}

// Fetch all books
$booksStmt = $pdo->query("
    SELECT b.*, c.name AS category_name 
    FROM books b
    JOIN categories c ON b.category_id = c.id
    ORDER BY b.title ASC
");
$books = $booksStmt->fetchAll();

// Fetch categories of type Book
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'Book' ORDER BY name ASC")->fetchAll();
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
          <a href="/admin/books.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-book me-2"></i>Manage Books</a>
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
      <h2 class="font-title fw-bold text-dark mb-4 h3">Manage Digital Catalog</h2>
      
      <?php if (!empty($success)): ?>
        <?php echo get_alert($success, 'success'); ?>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <?php echo get_alert($error, 'danger'); ?>
      <?php endif; ?>

      <div class="row g-4">
        <!-- Add Book Form -->
        <div class="col-md-5">
          <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius:15px;">
            <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Add New Book</h3>
            <form action="/admin/books.php" method="POST">
              <div class="mb-2">
                <label for="bookTitle" class="form-label small text-muted">Book Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="bookTitle" class="form-control bg-light" required placeholder="E.g., Quantum Computing Fundamentals">
              </div>
              <div class="mb-2">
                <label for="catId" class="form-label small text-muted">Category <span class="text-danger">*</span></label>
                <select name="category_id" id="catId" class="form-select bg-light" required>
                  <option value="">-- Choose Category --</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="row g-2 mb-2">
                <div class="col-6">
                  <label for="bookPrice" class="form-label small text-muted">Price (INR) <span class="text-danger">*</span></label>
                  <input type="number" name="price" id="bookPrice" class="form-control bg-light" required min="0" step="0.01" value="0.00">
                </div>
                <div class="col-6">
                  <label for="bookStock" class="form-label small text-muted">Inventory Stock <span class="text-danger">*</span></label>
                  <input type="number" name="stock" id="bookStock" class="form-control bg-light" required min="0" value="10">
                </div>
              </div>
              <div class="mb-2 form-check">
                <input type="checkbox" name="is_featured" id="isFeatured" class="form-check-input" value="1">
                <label class="form-check-label small text-muted" for="isFeatured">Mark as Featured on Home Slider</label>
              </div>
              <div class="mb-3">
                <label for="bookDesc" class="form-label small text-muted">Description</label>
                <textarea name="description" id="bookDesc" rows="4" class="form-control bg-light" placeholder="Summary or outline..."></textarea>
              </div>
              <button type="submit" name="add_book" class="btn btn-primary rounded-pill w-100 py-2 btn-sm fw-bold">Publish Book</button>
            </form>
          </div>
        </div>

        <!-- Books List -->
        <div class="col-md-7">
          <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius:15px;">
            <h3 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Catalog Inventory</h3>
            
            <div class="table-responsive">
              <table class="table align-middle small">
                <thead>
                  <tr class="text-muted">
                    <th>Title / Category</th>
                    <th class="text-end">Price</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($books as $b): ?>
                    <tr>
                      <td>
                        <strong class="text-dark small d-block"><?php echo sanitize($b['title']); ?></strong>
                        <span class="text-muted" style="font-size:0.75rem;"><?php echo sanitize($b['category_name']); ?></span>
                      </td>
                      <td class="text-end text-dark font-monospace">&#8377;<?php echo number_format($b['price'], 2); ?></td>
                      <td class="text-center text-dark"><?php echo $b['stock']; ?></td>
                      <td class="text-center">
                        <a href="/admin/books.php?delete_id=<?php echo $b['id']; ?>" class="btn btn-outline-danger btn-sm border-0 rounded-circle" onclick="return confirm('Are you sure you want to delete this book?');">
                          <i class="bi bi-trash"></i>
                        </a>
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
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
