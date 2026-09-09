<?php
$page_title = "Blog & News Portal | Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch distinct categories from blog_posts
$catStmt = $pdo->query("SELECT DISTINCT category AS name FROM blog_posts WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");
$categories = $catStmt->fetchAll();

$search = trim($_GET['search'] ?? '');
$cat_name = trim($_GET['category'] ?? '');

$query = "
    SELECT p.*, p.category AS category_name, p.author AS author_name 
    FROM blog_posts p
    WHERE p.status = 'Published'
";

$params = [];
if (!empty($search)) {
    $query .= " AND (p.title LIKE ? OR p.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($cat_name)) {
    $query .= " AND p.category = ?";
    $params[] = $cat_name;
}

$query .= " ORDER BY p.created_at DESC";
$postsStmt = $pdo->prepare($query);
$postsStmt->execute($params);
$posts = $postsStmt->fetchAll();
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<!-- Page Header -->
<section class="hero-section text-center d-flex align-items-center" style="padding: 120px 0 80px; background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%); color: #fff;">
  <div class="container hero-content animate-up text-white">
    <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
      <i class="bi bi-newspaper me-1"></i>Publishing News
    </span>
    <h1 class="hero-title mb-3 fs-2 text-white">Academic Blog & News Portal</h1>
    <p class="hero-subtitle mx-auto mb-0 text-white-50" style="max-width: 650px;">Stay up-to-date with technical breakthroughs, Scopus indexing changes, internship notifications, and research insights.</p>
  </div>
</section>

<div class="container py-5">
  <div class="row g-4">
    <!-- Left Sidebar: Filter Panel -->
    <div class="col-lg-3">
      <div class="p-4 bg-light border rounded-4 mb-4">
        <h4 class="fw-bold h5 text-dark mb-3"><i class="bi bi-search me-2 text-primary"></i>Search Posts</h4>
        <form action="/blog.php" method="GET">
          <div class="input-group input-group-sm mb-3 border rounded-pill overflow-hidden bg-white">
            <input type="text" name="search" class="form-control border-0 px-3 py-2" placeholder="Keyword..." value="<?php echo sanitize($search); ?>">
            <button class="btn btn-white border-0 px-2" type="submit"><i class="bi bi-search"></i></button>
          </div>
          <?php if (!empty($cat_name)): ?>
            <input type="hidden" name="category" value="<?php echo sanitize($cat_name); ?>">
          <?php endif; ?>
        </form>
      </div>

      <div class="p-4 bg-light border rounded-4">
        <h4 class="fw-bold h5 text-dark mb-3"><i class="bi bi-tag-fill me-2 text-primary"></i>Categories</h4>
        <div class="list-group list-group-flush small" style="border-radius:10px; overflow:hidden;">
          <a href="/blog.php" class="list-group-item list-group-item-action <?php echo empty($cat_name) ? 'active text-white bg-primary' : ''; ?>">All Categories</a>
          <?php foreach ($categories as $cat): ?>
            <a href="/blog.php?category=<?php echo urlencode($cat['name']); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="list-group-item list-group-item-action <?php echo $cat_name === $cat['name'] ? 'active text-white bg-primary' : ''; ?>">
              <?php echo sanitize($cat['name']); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Center: Articles List -->
    <div class="col-lg-9">
      <?php if (empty($posts)): ?>
        <div class="card text-center p-5 border-0 bg-light">
          <i class="bi bi-newspaper display-3 text-muted"></i>
          <h4 class="mt-3">No Articles Found</h4>
          <p class="text-muted">No posts matched your filtering guidelines.</p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($posts as $post): ?>
            <div class="col-md-6">
              <div class="card h-100 border shadow-sm overflow-hidden" style="border-radius: 15px;">
                <div class="bg-primary text-white p-4 text-center d-flex flex-column justify-content-center align-items-center" style="height: 180px; background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%) !important;">
                  <i class="bi bi-file-earmark-post fs-1 text-warning mb-2"></i>
                  <span class="badge bg-white text-primary rounded-pill px-3 py-1"><?php echo sanitize($post['category_name']); ?></span>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                  <div>
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                      <span><i class="bi bi-person me-1"></i><?php echo sanitize($post['author_name']); ?></span>
                      <span><i class="bi bi-calendar-check me-1"></i><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                    </div>
                    <h3 class="fw-bold h5 text-dark mb-2"><?php echo sanitize($post['title']); ?></h3>
                    <p class="text-muted small" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; height: 60px;">
                      <?php echo sanitize($post['content']); ?>
                    </p>
                  </div>
                  <a href="/blog_details.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill w-100 mt-4 py-2">
                    Read Article <i class="bi bi-arrow-right ms-1"></i>
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
