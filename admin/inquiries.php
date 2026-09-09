<?php
$page_title = "Inquiries & Contact Management - Admin | Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Admin');

$success = $error = '';

// ─── POST Actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Mark as Read
    if ($action === 'mark_read') {
        $id = (int)$_POST['inquiry_id'];
        $pdo->prepare("UPDATE inquiries SET status = 'Read', updated_at = NOW() WHERE id = ?")
            ->execute([$id]);
        $success = "Inquiry marked as read.";
    }

    // Mark as Resolved
    if ($action === 'mark_resolved') {
        $id = (int)$_POST['inquiry_id'];
        $pdo->prepare("UPDATE inquiries SET status = 'Resolved', updated_at = NOW() WHERE id = ?")
            ->execute([$id]);
        $success = "Inquiry marked as <strong>Resolved</strong>.";
    }

    // Send Reply (store reply + mark resolved)
    if ($action === 'send_reply') {
        $id    = (int)$_POST['inquiry_id'];
        $reply = trim($_POST['reply_text'] ?? '');
        if ($id && $reply) {
            $pdo->prepare("
                UPDATE inquiries
                SET admin_reply = ?, status = 'Resolved', replied_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ")->execute([$reply, $id]);
            $success = "Reply saved and inquiry resolved.";
        } else {
            $error = "Reply text cannot be empty.";
        }
    }

    // Delete inquiry
    if ($action === 'delete_inquiry') {
        $id = (int)$_POST['inquiry_id'];
        $pdo->prepare("DELETE FROM inquiries WHERE id = ?")->execute([$id]);
        $success = "Inquiry deleted.";
    }

    // Bulk action
    if ($action === 'bulk_action') {
        $bulk_type = trim($_POST['bulk_type'] ?? '');
        $ids       = array_map('intval', $_POST['selected_ids'] ?? []);
        if (!empty($ids) && $bulk_type) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if ($bulk_type === 'resolve') {
                $pdo->prepare("UPDATE inquiries SET status='Resolved', updated_at=NOW() WHERE id IN ($placeholders)")
                    ->execute($ids);
                $success = count($ids) . " inquiries marked as Resolved.";
            } elseif ($bulk_type === 'delete') {
                $pdo->prepare("DELETE FROM inquiries WHERE id IN ($placeholders)")->execute($ids);
                $success = count($ids) . " inquiries deleted.";
            }
        }
    }
}

// ─── View single inquiry detail ────────────────────────────────────────
$view_id = (int)($_GET['view'] ?? 0);
$viewing = null;
if ($view_id) {
    $vs = $pdo->prepare("SELECT * FROM inquiries WHERE id = ?");
    $vs->execute([$view_id]);
    $viewing = $vs->fetch();
    // Auto-mark as Read
    if ($viewing && $viewing['status'] === 'New') {
        $pdo->prepare("UPDATE inquiries SET status='Read', updated_at=NOW() WHERE id = ?")
            ->execute([$view_id]);
        $viewing['status'] = 'Read';
    }
}

// ─── Fetch / Filter Inquiries ──────────────────────────────────────────
$filter_status = trim($_GET['status'] ?? '');
$filter_type   = trim($_GET['type']   ?? '');
$search        = trim($_GET['search'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 15;
$offset        = ($page - 1) * $per_page;

$where  = "WHERE 1=1";
$params = [];
if ($filter_status) {
    $where   .= " AND status = ?";
    $params[] = $filter_status;
}
if ($filter_type) {
    $where   .= " AND inquiry_type = ?";
    $params[] = $filter_type;
}
if ($search) {
    $where   .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM inquiries $where");
$count_stmt->execute($params);
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = (int)ceil($total_rows / $per_page);

$inq_stmt = $pdo->prepare("
    SELECT * FROM inquiries
    $where
    ORDER BY FIELD(status,'New','Read','Resolved'), created_at DESC
    LIMIT $per_page OFFSET $offset
");
$inq_stmt->execute($params);
$inquiries = $inq_stmt->fetchAll();

// Stats
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='New') AS new_count,
        SUM(status='Read') AS read_count,
        SUM(status='Resolved') AS resolved_count
    FROM inquiries
")->fetch();

$types = $pdo->query("SELECT DISTINCT inquiry_type FROM inquiries WHERE inquiry_type IS NOT NULL ORDER BY inquiry_type")
              ->fetchAll(PDO::FETCH_COLUMN);
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
          <a href="/admin/orders.php"      class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>Orders &amp; Receipts</a>
          <a href="/admin/inquiries.php"   class="list-group-item list-group-item-action active text-white bg-primary">
            <i class="bi bi-envelope me-2"></i>Inquiries
            <?php if (($stats['new_count'] ?? 0) > 0): ?>
              <span class="badge bg-danger ms-1"><?php echo $stats['new_count']; ?></span>
            <?php endif; ?>
          </a>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9">

      <?php if ($viewing): ?>
        <!-- ── Single Inquiry Detail View ────────────────────────── -->
        <div class="d-flex align-items-center gap-3 mb-4">
          <a href="/admin/inquiries.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to List
          </a>
          <h2 class="font-title fw-bold text-dark h4 mb-0">
            <i class="bi bi-envelope-open me-2 text-primary"></i>Inquiry Detail
          </h2>
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

        <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius:15px;">
          <div class="row g-3">
            <div class="col-md-6">
              <p class="mb-1 small text-muted">Sender</p>
              <p class="fw-semibold text-dark mb-0"><?php echo sanitize($viewing['name']); ?></p>
            </div>
            <div class="col-md-6">
              <p class="mb-1 small text-muted">Email</p>
              <a href="mailto:<?php echo sanitize($viewing['email']); ?>" class="text-primary text-decoration-none">
                <?php echo sanitize($viewing['email']); ?>
              </a>
            </div>
            <?php if ($viewing['phone']): ?>
            <div class="col-md-6">
              <p class="mb-1 small text-muted">Phone</p>
              <p class="fw-semibold mb-0"><?php echo sanitize($viewing['phone']); ?></p>
            </div>
            <?php endif; ?>
            <div class="col-md-6">
              <p class="mb-1 small text-muted">Type</p>
              <span class="badge bg-secondary rounded-pill"><?php echo sanitize($viewing['inquiry_type'] ?? 'General'); ?></span>
            </div>
            <div class="col-md-6">
              <p class="mb-1 small text-muted">Received</p>
              <p class="mb-0 small"><?php echo date('d M Y, h:i A', strtotime($viewing['created_at'])); ?></p>
            </div>
            <div class="col-md-6">
              <p class="mb-1 small text-muted">Status</p>
              <?php
              $s_badge = match($viewing['status']) {
                  'New'      => 'bg-danger',
                  'Read'     => 'bg-warning text-dark',
                  'Resolved' => 'bg-success',
                  default    => 'bg-secondary'
              };
              ?>
              <span class="badge rounded-pill <?php echo $s_badge; ?>"><?php echo sanitize($viewing['status']); ?></span>
            </div>
            <div class="col-12">
              <p class="mb-1 small text-muted">Subject</p>
              <p class="fw-semibold text-dark mb-0"><?php echo sanitize($viewing['subject']); ?></p>
            </div>
            <div class="col-12">
              <p class="mb-2 small text-muted">Message</p>
              <div class="bg-light rounded-3 p-3 small text-dark" style="white-space:pre-wrap;line-height:1.7;">
                <?php echo nl2br(sanitize($viewing['message'])); ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Reply Form -->
        <?php if ($viewing['status'] !== 'Resolved' || $viewing['admin_reply']): ?>
        <div class="card border-0 shadow-sm p-4" style="border-radius:15px;">
          <h6 class="fw-bold text-dark mb-3">
            <i class="bi bi-reply me-2 text-primary"></i>
            <?php echo $viewing['admin_reply'] ? 'Previous Reply' : 'Send Reply'; ?>
          </h6>
          <?php if ($viewing['admin_reply']): ?>
            <div class="alert alert-success small rounded-3 mb-3">
              <strong>Reply sent on <?php echo $viewing['replied_at'] ? date('d M Y', strtotime($viewing['replied_at'])) : '—'; ?>:</strong><br>
              <?php echo nl2br(sanitize($viewing['admin_reply'])); ?>
            </div>
          <?php endif; ?>

          <?php if ($viewing['status'] !== 'Resolved'): ?>
          <form method="POST">
            <input type="hidden" name="action" value="send_reply">
            <input type="hidden" name="inquiry_id" value="<?php echo $viewing['id']; ?>">
            <div class="mb-3">
              <textarea name="reply_text" class="form-control rounded-3" rows="5"
                        placeholder="Type your reply here…" required></textarea>
              <small class="text-muted">Note: Saving the reply will also mark this inquiry as Resolved.</small>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-send me-1"></i> Send &amp; Resolve
              </button>
            </div>
          </form>
          <?php else: ?>
            <p class="text-muted small mb-0">This inquiry has been resolved.</p>
          <?php endif; ?>
        </div>
        <?php endif; ?>

      <?php else: ?>
        <!-- ── Inquiry List View ─────────────────────────────────── -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
          <h2 class="font-title fw-bold text-dark h3 mb-0">
            <i class="bi bi-envelope me-2 text-primary"></i>Contact Inquiries
            <?php if (($stats['new_count'] ?? 0) > 0): ?>
              <span class="badge bg-danger ms-2 fs-6"><?php echo $stats['new_count']; ?> New</span>
            <?php endif; ?>
          </h2>
        </div>

        <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show small rounded-3">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row g-3 mb-4">
          <div class="col-md-3 col-6">
            <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
              <span class="small text-muted d-block">Total</span>
              <strong class="text-primary fs-4"><?php echo $stats['total'] ?? 0; ?></strong>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
              <span class="small text-muted d-block">New (Unread)</span>
              <strong class="text-danger fs-4"><?php echo $stats['new_count'] ?? 0; ?></strong>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
              <span class="small text-muted d-block">Read</span>
              <strong class="text-warning fs-4"><?php echo $stats['read_count'] ?? 0; ?></strong>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
              <span class="small text-muted d-block">Resolved</span>
              <strong class="text-success fs-4"><?php echo $stats['resolved_count'] ?? 0; ?></strong>
            </div>
          </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" class="row g-2 mb-3 align-items-end">
          <div class="col-md-4">
            <input type="text" name="search" class="form-control rounded-pill"
                   placeholder="Name, email, subject…" value="<?php echo sanitize($search); ?>">
          </div>
          <div class="col-md-3">
            <select name="status" class="form-select rounded-pill">
              <option value="">All Statuses</option>
              <option value="New"      <?php echo $filter_status==='New'?'selected':''; ?>>New</option>
              <option value="Read"     <?php echo $filter_status==='Read'?'selected':''; ?>>Read</option>
              <option value="Resolved" <?php echo $filter_status==='Resolved'?'selected':''; ?>>Resolved</option>
            </select>
          </div>
          <div class="col-md-3">
            <select name="type" class="form-select rounded-pill">
              <option value="">All Types</option>
              <?php foreach ($types as $t): ?>
                <option value="<?php echo sanitize($t); ?>" <?php echo $filter_type===$t?'selected':''; ?>><?php echo sanitize($t); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-primary rounded-pill w-100"><i class="bi bi-search"></i></button>
          </div>
        </form>

        <!-- Bulk Action Form -->
        <form method="POST" id="bulkForm">
          <input type="hidden" name="action" value="bulk_action">
          <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <select name="bulk_type" class="form-select form-select-sm rounded-pill" style="max-width:180px;">
              <option value="">Bulk Action…</option>
              <option value="resolve">Mark as Resolved</option>
              <option value="delete">Delete Selected</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                    onclick="return confirm('Apply bulk action to selected inquiries?')">Apply</button>
            <small class="text-muted ms-auto"><?php echo $total_rows; ?> total result(s)</small>
          </div>

          <!-- Inquiries Table -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius:15px;overflow:hidden;">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 small">
                <thead class="table-dark">
                  <tr>
                    <th><input type="checkbox" id="selectAll" title="Select All"></th>
                    <th>#</th>
                    <th>Sender</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($inquiries)): ?>
                    <tr>
                      <td colspan="8" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox me-2"></i>No inquiries found.
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($inquiries as $ii => $inq): ?>
                      <?php
                      $is_new  = $inq['status'] === 'New';
                      $row_cls = $is_new ? 'table-warning' : '';
                      $s_badge = match($inq['status']) {
                          'New'      => 'bg-danger',
                          'Read'     => 'bg-warning text-dark',
                          'Resolved' => 'bg-success',
                          default    => 'bg-secondary'
                      };
                      ?>
                      <tr class="<?php echo $row_cls; ?>">
                        <td>
                          <input type="checkbox" name="selected_ids[]" value="<?php echo $inq['id']; ?>" class="row-checkbox">
                        </td>
                        <td class="fw-bold text-muted"><?php echo $offset + $ii + 1; ?></td>
                        <td>
                          <div class="fw-semibold <?php echo $is_new ? 'text-danger' : 'text-dark'; ?>">
                            <?php echo $is_new ? '<i class="bi bi-dot text-danger fs-5 align-middle"></i> ' : ''; ?>
                            <?php echo sanitize($inq['name']); ?>
                          </div>
                          <small class="text-muted"><?php echo sanitize($inq['email']); ?></small>
                        </td>
                        <td class="text-truncate" style="max-width:200px;">
                          <?php echo sanitize($inq['subject']); ?>
                        </td>
                        <td>
                          <span class="badge bg-secondary rounded-pill"><?php echo sanitize($inq['inquiry_type'] ?? 'General'); ?></span>
                        </td>
                        <td style="white-space:nowrap;"><?php echo date('d M Y', strtotime($inq['created_at'])); ?></td>
                        <td><span class="badge rounded-pill <?php echo $s_badge; ?>"><?php echo sanitize($inq['status']); ?></span></td>
                        <td>
                          <div class="d-flex gap-1 flex-wrap">
                            <!-- View & Reply -->
                            <a href="?view=<?php echo $inq['id']; ?>"
                               class="btn btn-sm btn-outline-primary rounded-pill py-0 px-2" title="View &amp; Reply">
                              <i class="bi bi-envelope-open"></i>
                            </a>
                            <!-- Mark Resolved -->
                            <?php if ($inq['status'] !== 'Resolved'): ?>
                            <form method="POST" class="d-inline">
                              <input type="hidden" name="action" value="mark_resolved">
                              <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
                              <button class="btn btn-sm btn-outline-success rounded-pill py-0 px-2" title="Mark Resolved">
                                <i class="bi bi-check2-circle"></i>
                              </button>
                            </form>
                            <?php endif; ?>
                            <!-- Delete -->
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this inquiry?')">
                              <input type="hidden" name="action" value="delete_inquiry">
                              <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
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
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <nav>
            <ul class="pagination pagination-sm justify-content-center gap-1">
              <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                  <a class="page-link rounded-pill"
                     href="?page=<?php echo $p; ?>&status=<?php echo urlencode($filter_status); ?>&type=<?php echo urlencode($filter_type); ?>&search=<?php echo urlencode($search); ?>">
                    <?php echo $p; ?>
                  </a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>

      <?php endif; // end list vs view ?>

    </div><!-- /col -->
  </div><!-- /row -->
</div><!-- /container -->

<script>
// Select All checkbox
const selectAll = document.getElementById('selectAll');
if (selectAll) {
    selectAll.addEventListener('change', () => {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = selectAll.checked);
    });
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
