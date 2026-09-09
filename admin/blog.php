<?php
$page_title = "Blog & News Management - Admin | Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Admin');

$success = $error = '';

// ─── Helper: slug generator ───────────────────────────────────────────
function make_slug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ─── Handle POST Actions ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Create / Publish Article ──────────────────────────────────────
    if ($action === 'add_post') {
        $title    = trim($_POST['title']    ?? '');
        $content  = trim($_POST['content']  ?? '');
        $category = trim($_POST['category'] ?? '');
        $tags     = trim($_POST['tags']     ?? '');
        $status   = trim($_POST['status']   ?? 'Draft');
        $author   = trim($_POST['author']   ?? $_SESSION['user_name'] ?? 'Admin');
        $excerpt  = trim($_POST['excerpt']  ?? '');

        if ($title && $content) {
            $slug  = make_slug($title) . '-' . time();
            $thumb = 'default_blog.jpg';

            if (!empty($_FILES['featured_image']['name'])) {
                $ext   = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
                $allow = ['jpg','jpeg','png','webp','gif'];
                if (in_array($ext, $allow)) {
                    $fname = 'blog_' . time() . '.' . $ext;
                    $dest  = __DIR__ . '/../uploads/blog/' . $fname;
                    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
                    if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $dest)) $thumb = $fname;
                }
            }

            $pdo->prepare("
                INSERT INTO blog_posts
                    (title, slug, content, excerpt, category, tags, author, featured_image, status, created_at)
                VALUES (?,?,?,?,?,?,?,?,?,NOW())
            ")->execute([$title, $slug, $content, $excerpt, $category, $tags, $author, $thumb, $status]);

            $success = "Post <strong>" . sanitize($title) . "</strong> saved as <strong>$status</strong>.";
        } else {
            $error = "Title and content are required.";
        }
    }

    // ── Toggle Publish / Draft ────────────────────────────────────────
    if ($action === 'toggle_status') {
        $id  = (int)$_POST['post_id'];
        $cur = trim($_POST['current_status'] ?? '');
        $new = ($cur === 'Published') ? 'Draft' : 'Published';
        $pdo->prepare("UPDATE blog_posts SET status = ? WHERE id = ?")->execute([$new, $id]);
        $success = "Post status changed to <strong>$new</strong>.";
    }

    // ── Delete Post ──────────────────────────────────────────────────
    if ($action === 'delete_post') {
        $id = (int)$_POST['post_id'];
        $pdo->prepare("DELETE FROM blog_comments WHERE post_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$id]);
        $success = "Post deleted successfully.";
    }

    // ── Approve / Delete Comment ──────────────────────────────────────
    if ($action === 'approve_comment') {
        $id = (int)$_POST['comment_id'];
        $pdo->prepare("UPDATE blog_comments SET status = 'Approved' WHERE id = ?")->execute([$id]);
        $success = "Comment approved.";
    }
    if ($action === 'delete_comment') {
        $id = (int)$_POST['comment_id'];
        $pdo->prepare("DELETE FROM blog_comments WHERE id = ?")->execute([$id]);
        $success = "Comment removed.";
    }
}

// ─── Fetch Data ────────────────────────────────────────────────────────
$filter   = trim($_GET['filter'] ?? '');
$search   = trim($_GET['search'] ?? '');
$tab      = trim($_GET['tab']    ?? 'posts'); // posts | comments

$where  = "WHERE 1=1";
$params = [];
if ($filter && in_array($filter, ['Published','Draft'])) {
    $where   .= " AND bp.status = ?";
    $params[] = $filter;
}
if ($search) {
    $where   .= " AND (bp.title LIKE ? OR bp.author LIKE ? OR bp.category LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$posts_stmt = $pdo->prepare("
    SELECT bp.*,
           COUNT(DISTINCT bc.id) AS comment_count
    FROM blog_posts bp
    LEFT JOIN blog_comments bc ON bp.id = bc.post_id
    $where
    GROUP BY bp.id
    ORDER BY bp.created_at DESC
");
$posts_stmt->execute($params);
$posts = $posts_stmt->fetchAll();

// Pending comments
$pending_comments = $pdo->query("
    SELECT bc.*, bp.title AS post_title
    FROM blog_comments bc
    JOIN blog_posts bp ON bc.post_id = bp.id
    WHERE bc.status = 'Pending'
    ORDER BY bc.created_at DESC
")->fetchAll();

// Stats
$total_posts     = $pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
$published_posts = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='Published'")->fetchColumn();
$draft_posts     = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='Draft'")->fetchColumn();
$total_comments  = $pdo->query("SELECT COUNT(*) FROM blog_comments")->fetchColumn();
$pending_count   = count($pending_comments);

// Distinct categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM blog_posts WHERE category IS NOT NULL AND category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
  <div class="row g-4">

    <!-- ── Sidebar ─────────────────────────────────────────────────── -->
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
          <a href="/admin/blog.php"        class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-newspaper me-2"></i>Blog News</a>
          <a href="/admin/orders.php"      class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>Orders &amp; Receipts</a>
          <a href="/admin/inquiries.php"   class="list-group-item list-group-item-action"><i class="bi bi-envelope me-2"></i>Inquiries</a>
        </div>
      </div>
    </div>

    <!-- ── Main Content ────────────────────────────────────────────── -->
    <div class="col-lg-9">

      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h2 class="font-title fw-bold text-dark h3 mb-0">
          <i class="bi bi-newspaper me-2 text-primary"></i>Blog &amp; News Editorial
        </h2>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addPostModal">
          <i class="bi bi-plus-circle me-1"></i> Write Article
        </button>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show small rounded-3">
          <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show small rounded-3">
          <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Stats Row -->
      <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Total Posts</span>
            <strong class="text-primary fs-4"><?php echo $total_posts; ?></strong>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Published</span>
            <strong class="text-success fs-4"><?php echo $published_posts; ?></strong>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Drafts</span>
            <strong class="text-warning fs-4"><?php echo $draft_posts; ?></strong>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Pending Comments</span>
            <strong class="text-danger fs-4"><?php echo $pending_count; ?></strong>
          </div>
        </div>
      </div>

      <!-- Tab Navigation -->
      <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item">
          <a class="nav-link rounded-pill <?php echo $tab !== 'comments' ? 'active' : ''; ?>"
             href="?tab=posts&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>">
            <i class="bi bi-file-richtext me-1"></i> Posts
            <span class="badge bg-white text-primary ms-1"><?php echo $total_posts; ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link rounded-pill <?php echo $tab === 'comments' ? 'active' : ''; ?>" href="?tab=comments">
            <i class="bi bi-chat-dots me-1"></i> Pending Comments
            <?php if ($pending_count > 0): ?>
              <span class="badge bg-danger ms-1"><?php echo $pending_count; ?></span>
            <?php endif; ?>
          </a>
        </li>
      </ul>

      <?php if ($tab === 'comments'): ?>
        <!-- ── Pending Comments Panel ───────────────────────────── -->
        <div class="card border-0 shadow-sm" style="border-radius:15px;overflow:hidden;">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-dark">
                <tr><th>#</th><th>Commenter</th><th>Post</th><th>Comment</th><th>Date</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php if (empty($pending_comments)): ?>
                  <tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-check-all me-2"></i>No pending comments. Inbox is clear!</td></tr>
                <?php else: ?>
                  <?php foreach ($pending_comments as $ci => $cm): ?>
                    <tr>
                      <td class="fw-bold text-muted"><?php echo $ci + 1; ?></td>
                      <td>
                        <div class="fw-semibold"><?php echo sanitize($cm['author_name'] ?? 'Guest'); ?></div>
                        <small class="text-muted"><?php echo sanitize($cm['author_email'] ?? ''); ?></small>
                      </td>
                      <td class="text-truncate" style="max-width:150px;">
                        <a href="/blog_details.php?id=<?php echo $cm['post_id']; ?>" target="_blank" class="text-primary small">
                          <?php echo sanitize($cm['post_title']); ?>
                        </a>
                      </td>
                      <td class="text-muted text-truncate" style="max-width:220px;"><?php echo sanitize($cm['comment']); ?></td>
                      <td class="text-muted" style="white-space:nowrap;"><?php echo date('d M Y', strtotime($cm['created_at'])); ?></td>
                      <td>
                        <div class="d-flex gap-1">
                          <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="approve_comment">
                            <input type="hidden" name="comment_id" value="<?php echo $cm['id']; ?>">
                            <button class="btn btn-sm btn-success rounded-pill py-0 px-2" title="Approve">
                              <i class="bi bi-check-lg"></i>
                            </button>
                          </form>
                          <form method="POST" class="d-inline" onsubmit="return confirm('Delete this comment?')">
                            <input type="hidden" name="action" value="delete_comment">
                            <input type="hidden" name="comment_id" value="<?php echo $cm['id']; ?>">
                            <button class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2" title="Delete">
                              <i class="bi bi-trash"></i>
                            </button>
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

      <?php else: ?>
        <!-- ── Posts Panel ──────────────────────────────────────── -->

        <!-- Filter & Search Bar -->
        <form method="GET" class="row g-2 mb-3 align-items-end">
          <input type="hidden" name="tab" value="posts">
          <div class="col-md-5">
            <input type="text" name="search" class="form-control rounded-pill"
                   placeholder="Search title, author, category…"
                   value="<?php echo sanitize($search); ?>">
          </div>
          <div class="col-md-4">
            <select name="filter" class="form-select rounded-pill">
              <option value="">All Statuses</option>
              <option value="Published" <?php echo $filter==='Published'?'selected':''; ?>>Published</option>
              <option value="Draft"     <?php echo $filter==='Draft'?'selected':''; ?>>Draft</option>
            </select>
          </div>
          <div class="col-md-3">
            <button class="btn btn-outline-primary rounded-pill w-100"><i class="bi bi-search me-1"></i>Filter</button>
          </div>
        </form>

        <div class="card border-0 shadow-sm" style="border-radius:15px;overflow:hidden;">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Article</th>
                  <th>Category</th>
                  <th>Author</th>
                  <th>Comments</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($posts)): ?>
                  <tr><td colspan="8" class="text-center py-4 text-muted">No articles found. Write your first post!</td></tr>
                <?php else: ?>
                  <?php foreach ($posts as $pi => $p): ?>
                    <tr>
                      <td class="fw-bold text-muted"><?php echo $pi + 1; ?></td>
                      <td>
                        <div class="fw-semibold text-dark"><?php echo sanitize($p['title']); ?></div>
                        <small class="text-muted"><?php echo sanitize(mb_strimwidth($p['excerpt'] ?? '', 0, 60, '…')); ?></small>
                      </td>
                      <td>
                        <span class="badge bg-secondary rounded-pill"><?php echo sanitize($p['category'] ?: '—'); ?></span>
                      </td>
                      <td><?php echo sanitize($p['author']); ?></td>
                      <td class="text-center"><?php echo (int)$p['comment_count']; ?></td>
                      <td style="white-space:nowrap;"><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                      <td>
                        <span class="badge rounded-pill <?php echo $p['status']==='Published' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                          <?php echo sanitize($p['status']); ?>
                        </span>
                      </td>
                      <td>
                        <div class="d-flex gap-1 flex-wrap">
                          <!-- Preview -->
                          <a href="/blog_details.php?id=<?php echo $p['id']; ?>" target="_blank"
                             class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" title="Preview">
                            <i class="bi bi-eye"></i>
                          </a>
                          <!-- Toggle Publish -->
                          <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $p['status']; ?>">
                            <button class="btn btn-sm btn-outline-<?php echo $p['status']==='Published'?'warning':'success'; ?> rounded-pill py-0 px-2"
                                    title="<?php echo $p['status']==='Published'?'Unpublish':'Publish'; ?>">
                              <i class="bi bi-<?php echo $p['status']==='Published'?'arrow-down-circle':'cloud-upload'; ?>"></i>
                            </button>
                          </form>
                          <!-- Delete -->
                          <form method="POST" class="d-inline" onsubmit="return confirm('Delete this post permanently?')">
                            <input type="hidden" name="action" value="delete_post">
                            <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                            <button class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2" title="Delete">
                              <i class="bi bi-trash"></i>
                            </button>
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
      <?php endif; ?>

    </div><!-- /col -->
  </div><!-- /row -->
</div><!-- /container -->

<!-- ── Write Article Modal ──────────────────────────────────────────────── -->
<div class="modal fade" id="addPostModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-primary text-white rounded-top-4">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Write New Article</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_post">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Article Title *</label>
              <input type="text" name="title" class="form-control rounded-3" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Category</label>
              <input type="text" name="category" class="form-control rounded-3"
                     placeholder="e.g. Research, Engineering" list="cat-suggestions">
              <datalist id="cat-suggestions">
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo sanitize($cat); ?>">
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Author</label>
              <input type="text" name="author" class="form-control rounded-3"
                     value="<?php echo sanitize($_SESSION['user_name'] ?? 'Admin'); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Tags</label>
              <input type="text" name="tags" class="form-control rounded-3" placeholder="php, research, education">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" class="form-select rounded-3">
                <option value="Draft">Draft</option>
                <option value="Published">Published</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Excerpt (Short Summary)</label>
              <textarea name="excerpt" class="form-control rounded-3" rows="2"
                        placeholder="A brief summary shown in listing pages…"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Featured Image</label>
              <input type="file" name="featured_image" class="form-control rounded-3" accept="image/*">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Full Content *</label>
              <textarea name="content" id="postContent" class="form-control rounded-3" rows="10" required
                        placeholder="Write full article content here. HTML tags are supported."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-send me-1"></i> Save Article
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
