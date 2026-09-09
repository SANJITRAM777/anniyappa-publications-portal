<?php
$page_title = "Grade Assignments - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(['Faculty', 'Author']);

$instructor_id = get_logged_in_user_id();
$success = '';
$error = '';

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_assignment'])) {
    $assignment_id = (int)($_POST['assignment_id'] ?? 0);
    $grade = trim($_POST['grade'] ?? '');
    
    if ($assignment_id <= 0 || empty($grade)) {
        $error = "Assignment ID and grade value are required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE assignments SET grade = ?, status = 'Graded' WHERE id = ?");
            $stmt->execute([$grade, $assignment_id]);
            
            // Check if student completed all assignments to auto-issue Internship Certificate
            // For simplicity, if they get an Approved grade (A, B, C) on at least one assignment, we can issue certificate.
            // Let's check student ID and internship ID first
            $infoStmt = $pdo->prepare("SELECT student_id, internship_id FROM assignments WHERE id = ?");
            $infoStmt->execute([$assignment_id]);
            $info = $infoStmt->fetch();
            
            if ($info && in_array($grade, ['A', 'B', 'C'])) {
                $student_id = $info['student_id'];
                $internship_id = $info['internship_id'];
                
                // Issue Internship Certificate if not already issued
                $chkCert = $pdo->prepare("SELECT id FROM certificates WHERE user_id = ? AND type = 'Internship' AND reference_id = ?");
                $chkCert->execute([$student_id, $internship_id]);
                
                if (!$chkCert->fetch()) {
                    $cert_code = 'CERT-INT-' . $internship_id . '-' . strtoupper(dechex(time())) . rand(10, 99);
                    $insCert = $pdo->prepare("INSERT INTO certificates (user_id, type, reference_id, certificate_code, issue_date) VALUES (?, 'Internship', ?, ?, CURDATE())");
                    $insCert->execute([$student_id, $internship_id, $cert_code]);
                }
            }
            
            $success = "Assignment graded successfully! Certificate will be auto-issued if passed.";
        } catch (PDOException $e) {
            $error = "Grading update failed: " . $e->getMessage();
        }
    }
}

// Fetch submitted assignments
$submissionsStmt = $pdo->prepare("
    SELECT a.*, i.title AS intern_title, prof.full_name AS student_name, u.email AS student_email
    FROM assignments a
    JOIN internships i ON a.internship_id = i.id
    JOIN user_profiles prof ON a.student_id = prof.user_id
    JOIN users u ON a.student_id = u.id
    ORDER BY a.submitted_at DESC
");
$submissionsStmt->execute();
$submissions = $submissionsStmt->fetchAll();
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
          <a href="/faculty/dashboard.php" class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Overview</a>
          <a href="/faculty/manage_courses.php" class="list-group-item list-group-item-action"><i class="bi bi-laptop me-2"></i>Manage Courses</a>
          <a href="/faculty/grade_assignments.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-journal-check me-2"></i>Grade Assignments</a>
          <a href="/faculty/research.php" class="list-group-item list-group-item-action"><i class="bi bi-mortarboard me-2"></i>Research Projects</a>
          <a href="/faculty/profile.php" class="list-group-item list-group-item-action"><i class="bi bi-person me-2"></i>Edit Profile</a>
        </div>
      </div>
    </div>

    <!-- Main Content Panel -->
    <div class="col-lg-9">
      <h2 class="font-title fw-bold text-dark mb-4 h3"><i class="bi bi-journal-check me-2 text-primary"></i>Internship Submissions Checklist</h2>
      
      <?php if (!empty($success)): ?>
        <?php echo get_alert($success, 'success'); ?>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <?php echo get_alert($error, 'danger'); ?>
      <?php endif; ?>

      <div class="card border-0 shadow-sm p-4" style="border-radius:15px;">
        <h3 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Student Assignment Submissions</h3>
        
        <?php if (empty($submissions)): ?>
          <p class="text-muted small mb-0">No internship assignments have been submitted by students yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle small">
              <thead>
                <tr class="text-muted">
                  <th>Student Info</th>
                  <th>Assignment Info</th>
                  <th>Submitted At</th>
                  <th>File Link</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($submissions as $sub): 
                  $status_badge = 'bg-warning text-dark';
                  if ($sub['status'] === 'Graded') $status_badge = 'bg-success text-white';
                ?>
                  <tr>
                    <td>
                      <strong class="text-dark small d-block"><?php echo sanitize($sub['student_name']); ?></strong>
                      <span class="text-muted font-monospace" style="font-size:0.75rem;"><?php echo sanitize($sub['student_email']); ?></span>
                    </td>
                    <td>
                      <strong class="text-dark small d-block"><?php echo sanitize($sub['title']); ?></strong>
                      <small class="text-muted d-block" style="font-size:0.75rem;"><?php echo sanitize($sub['intern_title']); ?></small>
                    </td>
                    <td class="text-muted"><?php echo date('M d, Y', strtotime($sub['submitted_at'])); ?></td>
                    <td>
                      <a href="/book_details.php?download=1" class="btn btn-outline-secondary btn-sm p-1.5 border-0 rounded-circle" title="Download Document" target="_blank">
                        <i class="bi bi-file-earmark-arrow-down fs-5"></i>
                      </a>
                    </td>
                    <td>
                      <span class="badge rounded-pill <?php echo $status_badge; ?>" style="font-size:0.65rem;"><?php echo sanitize($sub['status']); ?></span>
                    </td>
                    <td>
                      <form action="/faculty/grade_assignments.php" method="POST" class="d-flex align-items-center gap-1">
                        <input type="hidden" name="assignment_id" value="<?php echo $sub['id']; ?>">
                        <select name="grade" class="form-select form-select-sm bg-light py-1" style="width: 75px; border-radius: 5px;" required>
                          <option value="">--</option>
                          <option value="A" <?php echo $sub['grade'] === 'A' ? 'selected' : ''; ?>>A (Exec)</option>
                          <option value="B" <?php echo $sub['grade'] === 'B' ? 'selected' : ''; ?>>B (Good)</option>
                          <option value="C" <?php echo $sub['grade'] === 'C' ? 'selected' : ''; ?>>C (Pass)</option>
                          <option value="F" <?php echo $sub['grade'] === 'F' ? 'selected' : ''; ?>>F (Fail)</option>
                        </select>
                        <button type="submit" name="grade_assignment" class="btn btn-primary btn-sm rounded-3 py-1.5"><i class="bi bi-check-lg"></i></button>
                      </form>
                    </td>
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

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
