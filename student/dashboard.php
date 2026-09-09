<?php
$page_title = "Student Dashboard - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Access Control: require Student role
require_role('Student');

$student_id = get_logged_in_user_id();

// Fetch enrollments
$enrollmentsStmt = $pdo->prepare("
    SELECT ce.*, c.title, c.thumbnail 
    FROM course_enrollments ce
    JOIN courses c ON ce.course_id = c.id
    WHERE ce.student_id = ?
");
$enrollmentsStmt->execute([$student_id]);
$enrollments = $enrollmentsStmt->fetchAll();

// Fetch internship applications
$appsStmt = $pdo->prepare("
    SELECT a.*, i.title AS intern_title, i.domain 
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    WHERE a.student_id = ?
");
$appsStmt->execute([$student_id]);
$applications = $appsStmt->fetchAll();

// Fetch recent quiz scores
$scoresStmt = $pdo->prepare("
    SELECT r.*, q.title AS quiz_title 
    FROM results r
    JOIN quizzes q ON r.quiz_id = q.id
    WHERE r.student_id = ?
    ORDER BY r.completed_at DESC
");
$scoresStmt->execute([$student_id]);
$scores = $scoresStmt->fetchAll();
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
  <div class="row g-4">
    <!-- Left Sidebar: Navigation Panel -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm p-4 text-center bg-light" style="border-radius:15px;">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 mx-auto mb-3" style="width: 70px; height: 70px;">
          <?php echo substr($_SESSION['user_name'], 0, 1); ?>
        </div>
        <h4 class="fw-bold text-dark h5 mb-1"><?php echo sanitize($_SESSION['user_name']); ?></h4>
        <small class="text-muted d-block mb-3">Student Learner</small>
        
        <hr class="my-3">
        
        <div class="list-group list-group-flush text-start small" style="border-radius: 10px; overflow:hidden;">
          <a href="/student/dashboard.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-speedometer2 me-2"></i>Overview</a>
          <a href="/student/courses.php" class="list-group-item list-group-item-action"><i class="bi bi-laptop me-2"></i>My LMS Courses</a>
          <a href="/student/internships.php" class="list-group-item list-group-item-action"><i class="bi bi-briefcase me-2"></i>Internship Hub</a>
          <a href="/student/certificates.php" class="list-group-item list-group-item-action"><i class="bi bi-patch-check me-2"></i>Certificates</a>
          <a href="/student/profile.php" class="list-group-item list-group-item-action"><i class="bi bi-person me-2"></i>Edit Profile</a>
        </div>
      </div>
    </div>

    <!-- Right Side: Content Area -->
    <div class="col-lg-9">
      <h2 class="font-title fw-bold text-dark mb-4 h3">Student Overview</h2>
      
      <!-- Metrics Counters Row -->
      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Enrolled Courses</span>
            <strong class="text-primary fs-3"><?php echo count($enrollments); ?></strong>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Internship Applications</span>
            <strong class="text-primary fs-3"><?php echo count($applications); ?></strong>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Completed Quizzes</span>
            <strong class="text-primary fs-3"><?php echo count($scores); ?></strong>
          </div>
        </div>
      </div>

      <!-- LMS Enrollments -->
      <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 15px;">
        <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Active Course Progress</h3>
        <?php if (empty($enrollments)): ?>
          <p class="text-muted small">You are not enrolled in any LMS courses at this time.</p>
        <?php else: ?>
          <?php foreach ($enrollments as $ce): ?>
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-semibold text-dark small"><?php echo sanitize($ce['title']); ?></span>
                <span class="small text-muted fw-bold"><?php echo $ce['progress_percent']; ?>% Complete</span>
              </div>
              <div class="progress mb-2" style="height: 8px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $ce['progress_percent']; ?>%;" aria-valuenow="<?php echo $ce['progress_percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <a href="/student/courses.php?course_id=<?php echo $ce['course_id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1">Resume Study</a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="row g-4">
        <!-- Internship Applications Status -->
        <div class="col-md-6">
          <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:15px;">
            <h3 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Internship Status</h3>
            <?php if (empty($applications)): ?>
              <p class="text-muted small mb-0">No active applications.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush mb-0">
                <?php foreach ($applications as $app): 
                  $status_class = 'bg-warning text-dark';
                  if ($app['status'] === 'Approved') $status_class = 'bg-success text-white';
                  if ($app['status'] === 'Rejected') $status_class = 'bg-danger text-white';
                ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                    <div>
                      <h6 class="fw-bold text-dark mb-0 small"><?php echo sanitize($app['intern_title']); ?></h6>
                      <small class="text-muted" style="font-size:0.75rem;">Applied: <?php echo date('M d, Y', strtotime($app['applied_at'])); ?></small>
                    </div>
                    <span class="badge rounded-pill <?php echo $status_class; ?> px-3 py-1.5" style="font-size: 0.7rem;"><?php echo sanitize($app['status']); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quiz Scores -->
        <div class="col-md-6">
          <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:15px;">
            <h3 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Recent Quiz Grades</h3>
            <?php if (empty($scores)): ?>
              <p class="text-muted small mb-0">No quizzes completed yet.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush mb-0">
                <?php foreach ($scores as $sc): 
                  $perc = round(($sc['score'] / $sc['max_score']) * 100);
                  $badge_class = ($perc >= 70) ? 'text-success bg-success bg-opacity-10' : 'text-danger bg-danger bg-opacity-10';
                ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                    <div>
                      <h6 class="fw-bold text-dark mb-0 small"><?php echo sanitize($sc['quiz_title']); ?></h6>
                      <small class="text-muted" style="font-size:0.75rem;">Score: <?php echo $sc['score']; ?>/<?php echo $sc['max_score']; ?></small>
                    </div>
                    <span class="badge rounded-pill <?php echo $badge_class; ?> px-2.5 py-1.5" style="font-size:0.7rem;"><?php echo $perc; ?>%</span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
