<?php
$page_title = "Event & Webinar Management | Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$success = '';
$error = '';

// Handle event registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    if (!is_logged_in()) {
        $_SESSION['login_redirect'] = "/events.php";
        header("Location: /login.php");
        exit;
    }
    
    $event_id = (int)($_POST['event_id'] ?? 0);
    $user_id = get_logged_in_user_id();
    
    if ($event_id > 0) {
        try {
            // Check if already registered
            $check = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
            $check->execute([$event_id, $user_id]);
            
            if ($check->fetch()) {
                $error = "You have already registered for this event.";
            } else {
                $pdo->beginTransaction();
                
                // Fetch user name for registration record
                $profStmt = $pdo->prepare("SELECT full_name, phone FROM user_profiles WHERE user_id = ?");
                $profStmt->execute([$user_id]);
                $userProf = $profStmt->fetch();
                $attendee_name = $userProf['full_name'] ?? $_SESSION['user_name'] ?? 'Attendee';
                $phone = $userProf['phone'] ?? NULL;

                // Insert registration
                $insReg = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, attendee_name, phone) VALUES (?, ?, ?, ?)");
                $insReg->execute([$event_id, $user_id, $attendee_name, $phone]);
                
                $pdo->commit();
                $success = "Registration successful! You have secured your slot. The meeting URL is now active.";
            }
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

// Fetch events
$eventsStmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
$events = $eventsStmt->fetchAll();

// Get registered event IDs for current user to display links
$my_registrations = [];
if (is_logged_in()) {
    $myRegsStmt = $pdo->prepare("SELECT event_id FROM event_registrations WHERE user_id = ?");
    $myRegsStmt->execute([get_logged_in_user_id()]);
    $my_registrations = $myRegsStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<!-- Page Header -->
<section class="hero-section text-center d-flex align-items-center" style="padding: 120px 0 80px; background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%); color: #fff;">
  <div class="container hero-content animate-up text-white">
    <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
      <i class="bi bi-calendar-event me-1"></i>Webinars & Summits
    </span>
    <h1 class="hero-title mb-3 fs-2 text-white">Event & Webinar Management</h1>
    <p class="hero-subtitle mx-auto mb-0 text-white-50" style="max-width: 650px;">Participate in professional writing workshops, journal indexing seminars, and scientific meets led by senior researchers.</p>
  </div>
</section>

<div class="container py-5">
  <div class="row g-4">
    <!-- Events Grid -->
    <div class="col-lg-8">
      <h2 class="font-title fw-bold text-dark mb-4 h3"><i class="bi bi-calendar-check-fill me-2 text-primary"></i>Scheduled Webinars</h2>
      
      <?php if (!empty($success)): ?>
        <?php echo get_alert($success, 'success'); ?>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <?php echo get_alert($error, 'danger'); ?>
      <?php endif; ?>

      <?php if (empty($events)): ?>
        <div class="card text-center p-5 border-0 bg-light">
          <i class="bi bi-calendar-x display-4 text-muted"></i>
          <h4 class="mt-3">No Events Scheduled</h4>
          <p class="text-muted">There are no upcoming events or training webinars scheduled at this time.</p>
        </div>
      <?php else: ?>
        <?php foreach ($events as $event): 
          $is_registered = in_array($event['id'], $my_registrations);
        ?>
          <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius:15px;">
            <div class="row g-3">
              <div class="col-md-3 text-center border-end">
                <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 mb-2">
                  <h3 class="fw-bold font-title mb-0" style="font-size: 2rem;"><?php echo date('d', strtotime($event['event_date'])); ?></h3>
                  <small class="fw-semibold text-uppercase" style="font-size:0.75rem; letter-spacing:1px;"><?php echo date('M Y', strtotime($event['event_date'])); ?></small>
                </div>
                <span class="badge bg-secondary rounded-pill text-uppercase px-3 font-monospace" style="font-size:0.7rem;"><?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
              </div>
              <div class="col-md-9 d-flex flex-column justify-content-between ps-md-4">
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-warning text-dark small"><?php echo sanitize($event['type']); ?></span>
                  </div>
                  <h3 class="fw-bold h4 text-dark mb-2"><?php echo sanitize($event['title']); ?></h3>
                  <p class="text-dark small mb-3"><?php echo nl2br(sanitize($event['description'])); ?></p>
                </div>

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border-top pt-3">
                  <?php if ($is_registered): ?>
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-success py-2 px-3 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Registered</span>
                      <a href="<?php echo sanitize($event['meet_link'] ?: '#'); ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-play-circle-fill me-1"></i>Join Google Meet
                      </a>
                    </div>
                  <?php else: ?>
                    <form action="/events.php" method="POST">
                      <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                      <button type="submit" name="register_event" class="btn btn-primary btn-sm rounded-pill px-4">
                        Register Slot <i class="bi bi-arrow-right-short ms-1"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Free entry for portal members</small>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Right Column Sidebar -->
    <div class="col-lg-4">
      <div class="card border-0 bg-light p-4 mb-4" style="border-radius: 15px;">
        <h4 class="fw-bold text-dark mb-3">Participation Certs</h4>
        <p class="small text-muted mb-3">All attendees who log attendance in webinars receive a verifiable digital participation certificate issued by Anniyappa Publications.</p>
        <ul class="list-unstyled small text-muted mb-0">
          <li class="mb-2"><i class="bi bi-patch-check-fill text-primary me-2"></i>Verifiable code reference</li>
          <li class="mb-2"><i class="bi bi-patch-check-fill text-primary me-2"></i>Direct PDF download link</li>
          <li><i class="bi bi-patch-check-fill text-primary me-2"></i>Automatic academic records alignment</li>
        </ul>
      </div>

      <div class="card border-0 bg-white border p-4 text-center shadow-sm" style="border-radius:15px;">
        <i class="bi bi-clock-history fs-1 text-primary d-block mb-3"></i>
        <h4 class="fw-bold text-dark font-title">Need a Special Workshop?</h4>
        <p class="small text-muted mb-4">We host customized technical training, LaTeX labs, and publishing alignment seminars for universities.</p>
        <a href="/contact.php" class="btn btn-outline-primary w-100 rounded-pill py-2">Submit Workshop Query</a>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
