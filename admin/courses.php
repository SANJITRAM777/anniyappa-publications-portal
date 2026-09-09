<?php
$page_title = "LMS Courses Management - Admin | Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Admin');

$success = $error = '';

// ─── Handle POST Actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add new course
    if ($action === 'add_course') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $instructor  = trim($_POST['instructor'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $duration    = trim($_POST['duration'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $level       = trim($_POST['level'] ?? 'Beginner');
        $status      = trim($_POST['status'] ?? 'Active');

        if ($title && $description && $instructor) {
            $thumb = 'default_course.jpg';
            if (!empty($_FILES['thumbnail']['name'])) {
                $ext   = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
                $allow = ['jpg','jpeg','png','webp'];
                if (in_array($ext, $allow)) {
                    $fname = 'course_' . time() . '.' . $ext;
                    $dest  = __DIR__ . '/../uploads/courses/' . $fname;
                    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
                    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $dest)) $thumb = $fname;
                }
            }
            $stmt = $pdo->prepare("
                INSERT INTO courses (title, description, instructor_name, category, duration, price, level, thumbnail, status, created_at)
                VALUES (?,?,?,?,?,?,?,?,?,NOW())
            ");
            $stmt->execute([$title, $description, $instructor, $category, $duration, $price, $level, $thumb, $status]);
            $success = "Course <strong>" . sanitize($title) . "</strong> created successfully.";
        } else {
            $error = "Title, description and instructor are required.";
        }
    }

    // Toggle course status
    if ($action === 'toggle_status') {
        $id  = (int)$_POST['course_id'];
        $cur = trim($_POST['current_status'] ?? '');
        $new = ($cur === 'Active') ? 'Inactive' : 'Active';
        $pdo->prepare("UPDATE courses SET status = ? WHERE id = ?")->execute([$new, $id]);
        $success = "Course status updated to <strong>$new</strong>.";
    }

    // Delete course
    if ($action === 'delete_course') {
        $id = (int)$_POST['course_id'];
        $pdo->prepare("DELETE FROM course_enrollments WHERE course_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$id]);
        $success = "Course deleted successfully.";
    }

    // Add lesson to course
    if ($action === 'add_lesson') {
        $course_id   = (int)$_POST['course_id'];
        $lesson_title = trim($_POST['lesson_title'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $video_url   = trim($_POST['video_url'] ?? '');
        $sort_order  = (int)($_POST['sort_order'] ?? 0);

        if ($course_id && $lesson_title) {
            $pdo->prepare("
                INSERT INTO course_lessons (course_id, title, content, video_url, sort_order, created_at)
                VALUES (?,?,?,?,?,NOW())
            ")->execute([$course_id, $lesson_title, $content, $video_url, $sort_order]);
            $success = "Lesson added successfully.";
        } else {
            $error = "Course and lesson title are required.";
        }
    }
}

// ─── Fetch Data ─────────────────────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$cat_filter = trim($_GET['category'] ?? '');

$whereClause = "WHERE 1=1";
$params = [];
if ($search) {
    $whereClause .= " AND (c.title LIKE ? OR c.instructor_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($cat_filter) {
    $whereClause .= " AND c.category = ?";
    $params[] = $cat_filter;
}

$courses_stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(DISTINCT ce.id) AS enrollment_count,
           COUNT(DISTINCT cl.id) AS lesson_count
    FROM courses c
    LEFT JOIN course_enrollments ce ON c.id = ce.course_id
    LEFT JOIN course_lessons cl ON c.id = cl.course_id
    $whereClause
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$courses_stmt->execute($params);
$courses = $courses_stmt->fetchAll();

// Stats
$total_courses  = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$active_courses = $pdo->query("SELECT COUNT(*) FROM courses WHERE status='Active'")->fetchColumn();
$total_enrolls  = $pdo->query("SELECT COUNT(*) FROM course_enrollments")->fetchColumn();
$categories_row = $pdo->query("SELECT DISTINCT category FROM courses WHERE category IS NOT NULL AND category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Selected course for lesson management
$selected_course_id = (int)($_GET['lesson_mgmt'] ?? 0);
$lessons = [];
$sel_course = null;
if ($selected_course_id) {
    $sel_stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $sel_stmt->execute([$selected_course_id]);
    $sel_course = $sel_stmt->fetch();
    if ($sel_course) {
        $lessons_stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE course_id = ? ORDER BY sort_order ASC");
        $lessons_stmt->execute([$selected_course_id]);
        $lessons = $lessons_stmt->fetchAll();
    }
}
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
          <a href="/admin/dashboard.php"    class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
          <a href="/admin/users.php"        class="list-group-item list-group-item-action"><i class="bi bi-people me-2"></i>User Roles</a>
          <a href="/admin/books.php"        class="list-group-item list-group-item-action"><i class="bi bi-book me-2"></i>Manage Books</a>
          <a href="/admin/internships.php"  class="list-group-item list-group-item-action"><i class="bi bi-briefcase me-2"></i>Internship Apps</a>
          <a href="/admin/courses.php"      class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-laptop me-2"></i>LMS Courses</a>
          <a href="/admin/events.php"       class="list-group-item list-group-item-action"><i class="bi bi-calendar-event me-2"></i>Webinars</a>
          <a href="/admin/blog.php"         class="list-group-item list-group-item-action"><i class="bi bi-newspaper me-2"></i>Blog News</a>
          <a href="/admin/orders.php"       class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>Orders &amp; Receipts</a>
          <a href="/admin/inquiries.php"    class="list-group-item list-group-item-action"><i class="bi bi-envelope me-2"></i>Inquiries</a>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9">
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h2 class="font-title fw-bold text-dark h3 mb-0"><i class="bi bi-laptop me-2 text-primary"></i>LMS Course Manager</h2>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addCourseModal">
          <i class="bi bi-plus-circle me-1"></i> Add New Course
        </button>
      </div>

      <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 small" role="alert">
          <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small" role="alert">
          <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Stat Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Total Courses</span>
            <strong class="text-primary fs-4"><?php echo $total_courses; ?></strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Active Courses</span>
            <strong class="text-success fs-4"><?php echo $active_courses; ?></strong>
          </div>
        </div>
        <div class="col-md-4 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Total Enrollments</span>
            <strong class="text-info fs-4"><?php echo $total_enrolls; ?></strong>
          </div>
        </div>
      </div>

      <!-- Search & Filter -->
      <form method="GET" class="row g-2 mb-4 align-items-end">
        <div class="col-md-6">
          <input type="text" name="search" class="form-control rounded-pill" placeholder="Search by title or instructor…" value="<?php echo sanitize($search); ?>">
        </div>
        <div class="col-md-4">
          <select name="category" class="form-select rounded-pill">
            <option value="">All Categories</option>
            <?php foreach ($categories_row as $cat): ?>
              <option value="<?php echo sanitize($cat); ?>" <?php echo $cat_filter === $cat ? 'selected' : ''; ?>><?php echo sanitize($cat); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-primary rounded-pill w-100"><i class="bi bi-search"></i> Filter</button>
        </div>
      </form>

      <!-- Courses Table -->
      <div class="card border-0 shadow-sm mb-4" style="border-radius:15px;overflow:hidden;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>Course</th>
                <th>Category</th>
                <th>Level</th>
                <th>Lessons</th>
                <th>Enrollments</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($courses)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No courses found. Add your first course!</td></tr>
              <?php else: ?>
                <?php foreach ($courses as $i => $c): ?>
                  <tr>
                    <td class="fw-bold text-muted"><?php echo $i + 1; ?></td>
                    <td>
                      <div class="fw-semibold text-dark"><?php echo sanitize($c['title']); ?></div>
                      <small class="text-muted"><?php echo sanitize($c['instructor_name']); ?></small>
                    </td>
                    <td><span class="badge bg-secondary rounded-pill"><?php echo sanitize($c['category'] ?: '—'); ?></span></td>
                    <td><span class="badge bg-info text-dark rounded-pill"><?php echo sanitize($c['level']); ?></span></td>
                    <td class="text-center"><?php echo (int)$c['lesson_count']; ?></td>
                    <td class="text-center"><?php echo (int)$c['enrollment_count']; ?></td>
                    <td><?php echo $c['price'] > 0 ? '₹' . number_format($c['price'], 2) : '<span class="text-success">Free</span>'; ?></td>
                    <td>
                      <span class="badge rounded-pill <?php echo $c['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo sanitize($c['status']); ?>
                      </span>
                    </td>
                    <td>
                      <div class="d-flex gap-1 flex-wrap">
                        <a href="?lesson_mgmt=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill py-0 px-2" title="Manage Lessons">
                          <i class="bi bi-journals"></i>
                        </a>
                        <form method="POST" class="d-inline">
                          <input type="hidden" name="action" value="toggle_status">
                          <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                          <input type="hidden" name="current_status" value="<?php echo $c['status']; ?>">
                          <button class="btn btn-sm btn-outline-warning rounded-pill py-0 px-2" title="Toggle Status">
                            <i class="bi bi-toggle-<?php echo $c['status']==='Active'?'on':'off'; ?>"></i>
                          </button>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this course and all its lessons?')">
                          <input type="hidden" name="action" value="delete_course">
                          <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
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

      <!-- Lesson Management Panel -->
      <?php if ($sel_course): ?>
        <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius:15px;">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-journals me-2 text-primary"></i>Lessons — <?php echo sanitize($sel_course['title']); ?></h5>
            <a href="/admin/courses.php" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-x"></i> Close</a>
          </div>

          <!-- Add Lesson Form -->
          <form method="POST" class="row g-2 mb-4 bg-light p-3 rounded-3">
            <input type="hidden" name="action" value="add_lesson">
            <input type="hidden" name="course_id" value="<?php echo $sel_course['id']; ?>">
            <div class="col-md-5">
              <input type="text" name="lesson_title" class="form-control rounded-pill" placeholder="Lesson title *" required>
            </div>
            <div class="col-md-4">
              <input type="url" name="video_url" class="form-control rounded-pill" placeholder="YouTube/Video URL">
            </div>
            <div class="col-md-2">
              <input type="number" name="sort_order" class="form-control rounded-pill" placeholder="Order" min="1">
            </div>
            <div class="col-md-12">
              <textarea name="content" class="form-control rounded-3" rows="2" placeholder="Lesson content / notes"></textarea>
            </div>
            <div class="col-auto">
              <button class="btn btn-success rounded-pill px-4"><i class="bi bi-plus-circle me-1"></i>Add Lesson</button>
            </div>
          </form>

          <!-- Lessons list -->
          <?php if (empty($lessons)): ?>
            <p class="text-muted small">No lessons yet. Add the first lesson above.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle small mb-0">
                <thead class="table-light">
                  <tr><th>#</th><th>Lesson Title</th><th>Video</th><th>Content Preview</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($lessons as $idx => $ls): ?>
                    <tr>
                      <td><?php echo (int)$ls['sort_order'] ?: ($idx+1); ?></td>
                      <td class="fw-semibold"><?php echo sanitize($ls['title']); ?></td>
                      <td>
                        <?php if ($ls['video_url']): ?>
                          <a href="<?php echo sanitize($ls['video_url']); ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2">
                            <i class="bi bi-play-circle"></i> Watch
                          </a>
                        <?php else: echo '<span class="text-muted">—</span>'; ?>
                        <?php endif; ?>
                      </td>
                      <td class="text-muted text-truncate" style="max-width:260px;"><?php echo sanitize(mb_strimwidth($ls['content'] ?? '', 0, 100, '…')); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-primary text-white rounded-top-4">
        <h5 class="modal-title fw-bold"><i class="bi bi-laptop me-2"></i>Create New Course</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_course">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Course Title *</label>
              <input type="text" name="title" class="form-control rounded-3" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Level</label>
              <select name="level" class="form-select rounded-3">
                <option>Beginner</option>
                <option>Intermediate</option>
                <option>Advanced</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Instructor Name *</label>
              <input type="text" name="instructor" class="form-control rounded-3" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Category</label>
              <input type="text" name="category" class="form-control rounded-3" placeholder="e.g. Engineering">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Duration</label>
              <input type="text" name="duration" class="form-control rounded-3" placeholder="e.g. 6 Weeks">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Price (₹) — 0 for Free</label>
              <input type="number" name="price" class="form-control rounded-3" value="0" min="0" step="0.01">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" class="form-select rounded-3">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="Draft">Draft</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Thumbnail Image</label>
              <input type="file" name="thumbnail" class="form-control rounded-3" accept="image/*">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Description *</label>
              <textarea name="description" class="form-control rounded-3" rows="3" required></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-circle me-1"></i>Create Course</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
