<?php
$page_title = "Faculty Dashboard - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Access Control: require Faculty or Author role
require_role(['Faculty', 'Author']);

$instructor_id = get_logged_in_user_id();

// Count courses
$coursesCountStmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ?");
$coursesCountStmt->execute([$instructor_id]);
$courses_count = $coursesCountStmt->fetchColumn();

// Count enrolled students
$studentsCountStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT ce.student_id) 
    FROM course_enrollments ce
    JOIN courses c ON ce.course_id = c.id
    WHERE c.instructor_id = ?
");
$studentsCountStmt->execute([$instructor_id]);
$students_count = $studentsCountStmt->fetchColumn();

// Count pending proposals
$proposalsStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM proposals p
    JOIN research_projects rp ON p.project_id = rp.id
    WHERE rp.faculty_id = ? AND p.status = 'Pending'
");
$proposalsStmt->execute([$instructor_id]);
$pending_proposals = $proposalsStmt->fetchColumn();

// Fetch assigned courses
$coursesStmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ? ORDER BY title ASC");
$coursesStmt->execute([$instructor_id]);
$my_courses = $coursesStmt->fetchAll();
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
  <div class="row g-4">
    <!-- Sidebar Navigation -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm p-4 text-center bg-light" style="border-radius:15px;">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 mx-auto mb-3" style="width: 70px; height: 70px;">
          <?php echo substr($_SESSION['user_name'], 0, 1); ?>
        </div>
        <h4 class="fw-bold text-dark h5 mb-1"><?php echo sanitize($_SESSION['user_name']); ?></h4>
        <small class="text-muted d-block mb-3"><?php echo sanitize($_SESSION['user_role']); ?></small>
        
        <hr class="my-3">
        
        <div class="list-group list-group-flush text-start small" style="border-radius: 10px; overflow:hidden;">
          <a href="/faculty/dashboard.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-speedometer2 me-2"></i>Overview</a>
          <a href="/faculty/manage_courses.php" class="list-group-item list-group-item-action"><i class="bi bi-laptop me-2"></i>Manage Courses</a>
          <a href="/faculty/grade_assignments.php" class="list-group-item list-group-item-action"><i class="bi bi-journal-check me-2"></i>Grade Assignments</a>
          <a href="/faculty/research.php" class="list-group-item list-group-item-action"><i class="bi bi-mortarboard me-2"></i>Research Projects</a>
          <a href="/faculty/profile.php" class="list-group-item list-group-item-action"><i class="bi bi-person me-2"></i>Edit Profile</a>
        </div>
      </div>
    </div>

    <!-- Main Content Panel -->
    <div class="col-lg-9">
      <h2 class="font-title fw-bold text-dark mb-4 h3">Faculty Overview</h2>
      
      <!-- Metrics Counters -->
      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Courses Assigned</span>
            <strong class="text-primary fs-3"><?php echo $courses_count; ?></strong>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Active Learners</span>
            <strong class="text-primary fs-3"><?php echo $students_count; ?></strong>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="p-3 bg-white border rounded-3 shadow-sm text-center">
            <span class="small text-muted d-block">Pending Proposals</span>
            <strong class="text-primary fs-3 text-danger"><?php echo $pending_proposals; ?></strong>
          </div>
        </div>
      </div>

      <!-- Courses List -->
      <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius:15px;">
        <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Assigned LMS Curriculum</h3>
        <?php if (empty($my_courses)): ?>
          <p class="text-muted small">No courses have been assigned to you yet.</p>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($my_courses as $c): ?>
              <div class="col-md-6">
                <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="fw-bold text-dark mb-1 small"><?php echo sanitize($c['title']); ?></h6>
                    <small class="text-muted">Registered in portal seeder</small>
                  </div>
                  <a href="/faculty/manage_courses.php?course_id=<?php echo $c['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1">Syllabus</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Quick Actions -->
      <div class="card border-0 shadow-sm p-4" style="border-radius:15px; background: linear-gradient(135deg, #0f4c81, #1e3a8a); color:#fff;">
        <h3 class="fw-bold h5 text-white mb-2">Pedagogical Checklist</h3>
        <p class="small text-white-50 mb-3">Ensure all course lecture files and notes are uploaded, student assignments are graded weekly, and incoming proposals are reviewed promptly.</p>
        <div class="d-inline-flex gap-2">
          <a href="/faculty/grade_assignments.php" class="btn btn-light rounded-pill btn-sm text-primary px-4 fw-semibold">Review Mark Sheets</a>
          <a href="/faculty/research.php" class="btn btn-outline-light rounded-pill btn-sm px-4">Evaluate Chapter Proposals</a>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
