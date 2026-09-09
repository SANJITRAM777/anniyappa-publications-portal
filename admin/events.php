<?php
$page_title = "Webinars & Events Management - Admin | Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Admin');

$success = $error = '';

// ─── Handle POST Actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_event') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $speaker     = trim($_POST['speaker'] ?? '');
        $event_date  = trim($_POST['event_date'] ?? '');
        $event_time  = trim($_POST['event_time'] ?? '');
        $venue       = trim($_POST['venue'] ?? '');
        $type        = trim($_POST['type'] ?? 'Webinar');
        $seats       = (int)($_POST['seats'] ?? 0);
        $fee         = (float)($_POST['fee'] ?? 0);
        $meet_link   = trim($_POST['meet_link'] ?? '');
        $status      = trim($_POST['status'] ?? 'Upcoming');

        if ($title && $event_date) {
            $banner = 'default_event.jpg';
            if (!empty($_FILES['banner']['name'])) {
                $ext   = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
                $allow = ['jpg','jpeg','png','webp'];
                if (in_array($ext, $allow)) {
                    $fname = 'event_' . time() . '.' . $ext;
                    $dest  = __DIR__ . '/../uploads/events/' . $fname;
                    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
                    if (move_uploaded_file($_FILES['banner']['tmp_name'], $dest)) $banner = $fname;
                }
            }
            $pdo->prepare("
                INSERT INTO events (title, description, speaker, event_date, event_time, venue, type, seats_available, registration_fee, meet_link, banner, status, created_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())
            ")->execute([$title, $description, $speaker, $event_date, $event_time, $venue, $type, $seats, $fee, $meet_link, $banner, $status]);
            $success = "Event <strong>" . sanitize($title) . "</strong> created successfully.";
        } else {
            $error = "Title and event date are required.";
        }
    }

    if ($action === 'update_status') {
        $id     = (int)$_POST['event_id'];
        $status = trim($_POST['new_status'] ?? '');
        $allowed = ['Upcoming','Ongoing','Completed','Cancelled'];
        if (in_array($status, $allowed)) {
            $pdo->prepare("UPDATE events SET status = ? WHERE id = ?")->execute([$status, $id]);
            $success = "Event status updated to <strong>" . sanitize($status) . "</strong>.";
        }
    }

    if ($action === 'delete_event') {
        $id = (int)$_POST['event_id'];
        $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
        $success = "Event deleted.";
    }
}

// ─── Fetch Data ─────────────────────────────────────────────────────────
$filter_status = trim($_GET['status'] ?? '');
$where  = $filter_status ? "WHERE e.status = ?" : "WHERE 1=1";
$params = $filter_status ? [$filter_status] : [];

$events_stmt = $pdo->prepare("
    SELECT e.*, COUNT(er.id) AS registered_count
    FROM events e
    LEFT JOIN event_registrations er ON e.id = er.event_id
    $where
    GROUP BY e.id
    ORDER BY e.event_date DESC
");
$events_stmt->execute($params);
$events = $events_stmt->fetchAll();

// Stats
$stats = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(status='Upcoming') AS upcoming,
        SUM(status='Completed') AS completed,
        SUM(status='Cancelled') AS cancelled
    FROM events
")->fetch();
$total_regs = $pdo->query("SELECT COUNT(*) FROM event_registrations")->fetchColumn();
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
          <a href="/admin/courses.php"      class="list-group-item list-group-item-action"><i class="bi bi-laptop me-2"></i>LMS Courses</a>
          <a href="/admin/events.php"       class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-calendar-event me-2"></i>Webinars</a>
          <a href="/admin/blog.php"         class="list-group-item list-group-item-action"><i class="bi bi-newspaper me-2"></i>Blog News</a>
          <a href="/admin/orders.php"       class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>Orders &amp; Receipts</a>
          <a href="/admin/inquiries.php"    class="list-group-item list-group-item-action"><i class="bi bi-envelope me-2"></i>Inquiries</a>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9">
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h2 class="font-title fw-bold text-dark h3 mb-0"><i class="bi bi-calendar-event me-2 text-primary"></i>Webinars &amp; Events</h2>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addEventModal">
          <i class="bi bi-plus-circle me-1"></i> Schedule Event
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

      <!-- Stats -->
      <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Total Events</span>
            <strong class="text-primary fs-4"><?php echo $stats['total'] ?? 0; ?></strong>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Upcoming</span>
            <strong class="text-warning fs-4"><?php echo $stats['upcoming'] ?? 0; ?></strong>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Completed</span>
            <strong class="text-success fs-4"><?php echo $stats['completed'] ?? 0; ?></strong>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Registrations</span>
            <strong class="text-info fs-4"><?php echo $total_regs; ?></strong>
          </div>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="d-flex gap-2 mb-4 flex-wrap">
        <?php
        $tab_statuses = ['' => 'All', 'Upcoming' => 'Upcoming', 'Ongoing' => 'Ongoing', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled'];
        foreach ($tab_statuses as $val => $label):
        ?>
          <a href="?status=<?php echo urlencode($val); ?>" 
             class="btn btn-sm rounded-pill <?php echo $filter_status === $val ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            <?php echo $label; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Events Table -->
      <div class="card border-0 shadow-sm" style="border-radius:15px;overflow:hidden;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>Event</th>
                <th>Type</th>
                <th>Date &amp; Time</th>
                <th>Speaker</th>
                <th>Regs</th>
                <th>Fee</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($events)): ?>
                <tr><td colspan="9" class="text-center py-4 text-muted">No events found. Schedule one!</td></tr>
              <?php else: ?>
                <?php foreach ($events as $i => $ev): ?>
                  <?php
                  $badge_class = match($ev['status']) {
                      'Upcoming'  => 'bg-warning text-dark',
                      'Ongoing'   => 'bg-primary',
                      'Completed' => 'bg-success',
                      'Cancelled' => 'bg-danger',
                      default     => 'bg-secondary'
                  };
                  ?>
                  <tr>
                    <td class="fw-bold text-muted"><?php echo $i + 1; ?></td>
                    <td>
                      <div class="fw-semibold text-dark"><?php echo sanitize($ev['title']); ?></div>
                      <small class="text-muted"><?php echo sanitize($ev['venue'] ?: 'Online'); ?></small>
                    </td>
                    <td><span class="badge bg-secondary rounded-pill"><?php echo sanitize($ev['type']); ?></span></td>
                    <td>
                      <div><?php echo date('d M Y', strtotime($ev['event_date'])); ?></div>
                      <small class="text-muted"><?php echo $ev['event_time'] ? date('h:i A', strtotime($ev['event_time'])) : '—'; ?></small>
                    </td>
                    <td><?php echo sanitize($ev['speaker'] ?: '—'); ?></td>
                    <td class="text-center"><?php echo (int)$ev['registered_count']; ?> / <?php echo $ev['seats_available'] > 0 ? $ev['seats_available'] : '∞'; ?></td>
                    <td><?php echo $ev['registration_fee'] > 0 ? '₹' . number_format($ev['registration_fee'], 2) : '<span class="text-success">Free</span>'; ?></td>
                    <td><span class="badge rounded-pill <?php echo $badge_class; ?>"><?php echo sanitize($ev['status']); ?></span></td>
                    <td>
                      <div class="d-flex gap-1 flex-wrap">
                        <!-- Status Change Dropdown -->
                        <div class="dropdown">
                          <button class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2 dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 130px;">
                            <?php foreach (['Upcoming','Ongoing','Completed','Cancelled'] as $ns): ?>
                              <li>
                                <form method="POST" class="px-2 py-1">
                                  <input type="hidden" name="action" value="update_status">
                                  <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                                  <input type="hidden" name="new_status" value="<?php echo $ns; ?>">
                                  <button class="dropdown-item rounded-2 small py-1 <?php echo $ev['status']===$ns?'active':''; ?>"><?php echo $ns; ?></button>
                                </form>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        </div>
                        <!-- Registrations Modal Trigger -->
                        <button class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#regsModal<?php echo $ev['id']; ?>"
                                title="View Registrations">
                          <i class="bi bi-people"></i>
                        </button>
                        <!-- Delete -->
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this event?')">
                          <input type="hidden" name="action" value="delete_event">
                          <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                          <button class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2"><i class="bi bi-trash"></i></button>
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
    </div>
  </div>
</div>

<!-- Registrations Modals (per event) -->
<?php foreach ($events as $ev):
  $regs_stmt = $pdo->prepare("
      SELECT er.*, u.email
      FROM event_registrations er
      JOIN users u ON er.user_id = u.id
      WHERE er.event_id = ?
      ORDER BY er.created_at ASC
  ");
  $regs_stmt->execute([$ev['id']]);
  $regs = $regs_stmt->fetchAll();
?>
<div class="modal fade" id="regsModal<?php echo $ev['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-info text-white rounded-top-4">
        <h5 class="modal-title fw-bold"><i class="bi bi-people me-2"></i>Registrations — <?php echo sanitize($ev['title']); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <?php if (empty($regs)): ?>
          <p class="text-muted text-center py-3">No registrations yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle small mb-0">
              <thead class="table-light">
                <tr><th>#</th><th>Email</th><th>Name</th><th>Phone</th><th>Registered On</th></tr>
              </thead>
              <tbody>
                <?php foreach ($regs as $ri => $reg): ?>
                  <tr>
                    <td><?php echo $ri + 1; ?></td>
                    <td><?php echo sanitize($reg['email']); ?></td>
                    <td><?php echo sanitize($reg['attendee_name'] ?? '—'); ?></td>
                    <td><?php echo sanitize($reg['phone'] ?? '—'); ?></td>
                    <td><?php echo date('d M Y, h:i A', strtotime($reg['created_at'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-primary text-white rounded-top-4">
        <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus me-2"></i>Schedule New Event / Webinar</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_event">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Event Title *</label>
              <input type="text" name="title" class="form-control rounded-3" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Type</label>
              <select name="type" class="form-select rounded-3">
                <option>Webinar</option>
                <option>Workshop</option>
                <option>Seminar</option>
                <option>Conference</option>
                <option>Launch Event</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Speaker / Host</label>
              <input type="text" name="speaker" class="form-control rounded-3">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Event Date *</label>
              <input type="date" name="event_date" class="form-control rounded-3" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Time</label>
              <input type="time" name="event_time" class="form-control rounded-3">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Venue / Platform</label>
              <input type="text" name="venue" class="form-control rounded-3" placeholder="e.g. Google Meet / Chennai Hall">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Seats (0 = Unlimited)</label>
              <input type="number" name="seats" class="form-control rounded-3" value="0" min="0">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Registration Fee (₹)</label>
              <input type="number" name="fee" class="form-control rounded-3" value="0" min="0" step="0.01">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Meeting Link</label>
              <input type="url" name="meet_link" class="form-control rounded-3" placeholder="https://…">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" class="form-select rounded-3">
                <option>Upcoming</option>
                <option>Ongoing</option>
                <option>Completed</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Banner Image</label>
              <input type="file" name="banner" class="form-control rounded-3" accept="image/*">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Description</label>
              <textarea name="description" class="form-control rounded-3" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-calendar-check me-1"></i>Schedule Event</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
