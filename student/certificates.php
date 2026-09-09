<?php
$page_title = "Certificates Hub - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Student');

$student_id = get_logged_in_user_id();

// Fetch certificates
$certsStmt = $pdo->prepare("SELECT * FROM certificates WHERE user_id = ? ORDER BY issue_date DESC");
$certsStmt->execute([$student_id]);
$certificates = $certsStmt->fetchAll();

$resolved_certs = [];
foreach ($certificates as $cert) {
    $title = '';
    $ref_id = $cert['reference_id'];
    
    if ($cert['type'] === 'Course') {
        $q = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
        $q->execute([$ref_id]);
        $title = $q->fetchColumn() ?: 'Academic Course';
    } elseif ($cert['type'] === 'Internship') {
        $q = $pdo->prepare("SELECT title FROM internships WHERE id = ?");
        $q->execute([$ref_id]);
        $title = $q->fetchColumn() ?: 'Domain Internship Program';
    } elseif ($cert['type'] === 'Event') {
        $q = $pdo->prepare("SELECT title FROM events WHERE id = ?");
        $q->execute([$ref_id]);
        $title = $q->fetchColumn() ?: 'Scholarly Webinar Summit';
    }
    
    $resolved_certs[] = [
        'id' => $cert['id'],
        'type' => $cert['type'],
        'title' => $title,
        'code' => $cert['certificate_code'],
        'date' => date('d-M-Y', strtotime($cert['issue_date'])),
    ];
}

$view_cert = null;
$view_id = (int)($_GET['view_id'] ?? 0);
if ($view_id > 0) {
    foreach ($resolved_certs as $rc) {
        if ($rc['id'] === $view_id) {
            $view_cert = $rc;
            break;
        }
    }
}
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
  <div class="row g-4">
    <!-- Sidebar Navigation -->
    <div class="col-lg-3 d-print-none">
      <div class="card border-0 shadow-sm p-4 text-center bg-light" style="border-radius:15px;">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 mx-auto mb-3" style="width: 70px; height: 70px;">
          <?php echo substr($_SESSION['user_name'], 0, 1); ?>
        </div>
        <h4 class="fw-bold text-dark h5 mb-1"><?php echo sanitize($_SESSION['user_name']); ?></h4>
        <small class="text-muted d-block mb-3">Student Learner</small>
        
        <hr class="my-3">
        
        <div class="list-group list-group-flush text-start small" style="border-radius: 10px; overflow:hidden;">
          <a href="/student/dashboard.php" class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Overview</a>
          <a href="/student/courses.php" class="list-group-item list-group-item-action"><i class="bi bi-laptop me-2"></i>My LMS Courses</a>
          <a href="/student/internships.php" class="list-group-item list-group-item-action"><i class="bi bi-briefcase me-2"></i>Internship Hub</a>
          <a href="/student/certificates.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-patch-check me-2"></i>Certificates</a>
          <a href="/student/profile.php" class="list-group-item list-group-item-action"><i class="bi bi-person me-2"></i>Edit Profile</a>
        </div>
      </div>
    </div>

    <!-- Main Content Panel -->
    <div class="col-lg-9">
      <?php if ($view_cert): ?>
        <!-- Single Certificate Render View -->
        <div class="d-flex align-items-center gap-2 mb-4 d-print-none">
          <a href="/student/certificates.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left"></i> Back to Hub</a>
          <button onclick="window.print();" class="btn btn-primary btn-sm rounded-pill px-3"><i class="bi bi-printer me-1"></i> Print Certificate</button>
        </div>

        <div class="py-4 bg-white rounded-3 shadow-sm border p-3">
          <?php 
          echo get_certificate_html(
              sanitize($_SESSION['user_name']),
              sanitize($view_cert['type']),
              sanitize($view_cert['title']),
              sanitize($view_cert['code']),
              sanitize($view_cert['date'])
          ); 
          ?>
        </div>

      <?php else: ?>
        <!-- Certificates List View -->
        <h2 class="font-title fw-bold text-dark mb-4 h3"><i class="bi bi-patch-check-fill me-2 text-primary"></i>My Verification Certificates</h2>
        
        <?php if (empty($resolved_certs)): ?>
          <div class="card text-center p-5 border-0 bg-light shadow-sm" style="border-radius: 15px;">
            <i class="bi bi-award display-3 text-muted"></i>
            <h4 class="mt-3">No Certificates Earned Yet</h4>
            <p class="text-muted">You will receive verifiable certificates upon completing LMS courses, webinars, or internship programs.</p>
          </div>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($resolved_certs as $rc): ?>
              <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:15px; border-left: 5px solid var(--primary-color) !important;">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-1.5 rounded-pill"><?php echo sanitize($rc['type']); ?></span>
                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?php echo $rc['date']; ?></small>
                  </div>
                  <h4 class="fw-bold h5 text-dark mb-2"><?php echo sanitize($rc['title']); ?></h4>
                  <p class="small text-muted mb-3">Verification ID: <strong class="text-dark font-monospace"><?php echo $rc['code']; ?></strong></p>
                  
                  <a href="/student/certificates.php?view_id=<?php echo $rc['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-4 align-self-start mt-2">
                    View Certificate &rarr;
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
