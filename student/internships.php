<?php
$page_title = "Internship Hub - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Student');

$student_id = get_logged_in_user_id();
$success = '';
$error = '';

// Handle Daily Attendance Check-In
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_attendance'])) {
    try {
        $today = date('Y-m-d');
        $check = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND date = ?");
        $check->execute([$student_id, $today]);
        
        if ($check->fetch()) {
            $error = "You have already logged your attendance for today.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?, ?, 'Present')");
            $stmt->execute([$student_id, $today]);
            $success = "Attendance logged successfully as Present for today!";
        }
    } catch (PDOException $e) {
        $error = "Attendance log failed: " . $e->getMessage();
    }
}

// Handle Assignment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $internship_id = (int)($_POST['internship_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($internship_id <= 0 || empty($title) || !isset($_FILES['assignment_file'])) {
        $error = "Please fill in all required fields and upload an assignment document.";
    } else {
        $file = $_FILES['assignment_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['pdf', 'zip', 'docx'])) {
            $error = "Only PDF, ZIP, and DOCX files are allowed.";
        } elseif ($file['size'] > 10000000) { // 10MB limit
            $error = "File size cannot exceed 10MB.";
        } else {
            $filename = 'assignment_' . $student_id . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../uploads/assignments/' . $filename;
            
            // Create folder if not exists
            if (!is_dir(__DIR__ . '/../uploads/assignments/')) {
                mkdir(__DIR__ . '/../uploads/assignments/', 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO assignments (internship_id, student_id, title, description, file_path, status, submitted_at) 
                        VALUES (?, ?, ?, ?, ?, 'Submitted', CURRENT_TIMESTAMP)
                    ");
                    $stmt->execute([$internship_id, $student_id, $title, $description, 'uploads/assignments/' . $filename]);
                    $success = "Assignment submitted successfully! Faculty will evaluate and assign grades.";
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            } else {
                $error = "Failed to upload assignment file. Please verify folder permissions.";
            }
        }
    }
}

// Fetch active approved internships
$myInternsStmt = $pdo->prepare("
    SELECT a.internship_id, i.title, i.domain, i.duration_weeks
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    WHERE a.student_id = ? AND a.status = 'Approved'
");
$myInternsStmt->execute([$student_id]);
$my_internships = $myInternsStmt->fetchAll();

// Fetch assignments submitted
$assignmentsStmt = $pdo->prepare("
    SELECT a.*, i.title AS intern_title 
    FROM assignments a
    JOIN internships i ON a.internship_id = i.id
    WHERE a.student_id = ?
    ORDER BY a.submitted_at DESC
");
$assignmentsStmt->execute([$student_id]);
$submitted_assignments = $assignmentsStmt->fetchAll();

// Fetch recent attendance logs
$attendanceStmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC LIMIT 10");
$attendanceStmt->execute([$student_id]);
$attendance_logs = $attendanceStmt->fetchAll();
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
        <small class="text-muted d-block mb-3">Student Learner</small>
        
        <hr class="my-3">
        
        <div class="list-group list-group-flush text-start small" style="border-radius: 10px; overflow:hidden;">
          <a href="/student/dashboard.php" class="list-group-item list-group-item-action"><i class="bi bi-speedometer2 me-2"></i>Overview</a>
          <a href="/student/courses.php" class="list-group-item list-group-item-action"><i class="bi bi-laptop me-2"></i>My LMS Courses</a>
          <a href="/student/internships.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-briefcase me-2"></i>Internship Hub</a>
          <a href="/student/certificates.php" class="list-group-item list-group-item-action"><i class="bi bi-patch-check me-2"></i>Certificates</a>
          <a href="/student/profile.php" class="list-group-item list-group-item-action"><i class="bi bi-person me-2"></i>Edit Profile</a>
        </div>
      </div>
    </div>

    <!-- Main Content Panel -->
    <div class="col-lg-9">
      <h2 class="font-title fw-bold text-dark mb-4 h3"><i class="bi bi-briefcase me-2 text-primary"></i>My Internship Hub</h2>
      
      <?php if (!empty($success)): ?>
        <?php echo get_alert($success, 'success'); ?>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <?php echo get_alert($error, 'danger'); ?>
      <?php endif; ?>

      <?php if (empty($my_internships)): ?>
        <div class="card text-center p-5 border-0 bg-light shadow-sm mb-4" style="border-radius:15px;">
          <i class="bi bi-patch-exclamation display-4 text-muted mb-2"></i>
          <h4 class="fw-bold text-dark">No Active Internship Found</h4>
          <p class="text-muted">You must apply for an internship opening and get approval from the editorial board before accessing dashboard tools.</p>
          <a href="/internship.php" class="btn btn-primary rounded-pill px-4 align-self-center mt-2">Explore Internship Programs</a>
        </div>
      <?php else: ?>
        <!-- Quick Check-in & Submissions -->
        <div class="row g-4 mb-4">
          <!-- Attendance Card -->
          <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white" style="border-radius:15px;">
              <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Daily Attendance Check-in</h3>
              <p class="small text-muted mb-4">You are required to log attendance daily during the training period. Log entry registers immediately.</p>
              
              <form action="/student/internships.php" method="POST">
                <button type="submit" name="log_attendance" class="btn btn-success rounded-pill px-4 w-100 py-2.5 fw-bold">
                  <i class="bi bi-clock-history me-1"></i>Log Present (<?php echo date('d-M'); ?>)
                </button>
              </form>
            </div>
          </div>

          <!-- Submit Assignment Card -->
          <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white" style="border-radius:15px;">
              <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Submit Assignment</h3>
              <p class="small text-muted mb-4">Upload research outlines, review briefs, or typesetting ZIP codes assigned by faculty mentors.</p>
              
              <button class="btn btn-primary-custom rounded-pill w-100 py-2.5 fw-bold" data-bs-toggle="modal" data-bs-target="#assignmentModal">
                <i class="bi bi-upload me-1"></i>Upload Assignment File
              </button>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <!-- Attendance Logs -->
          <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-light" style="border-radius:12px;">
              <h4 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Attendance Logs (Last 10)</h4>
              <?php if (empty($attendance_logs)): ?>
                <p class="text-muted small mb-0">No logs recorded.</p>
              <?php else: ?>
                <ul class="list-group list-group-flush mb-0 small">
                  <?php foreach ($attendance_logs as $log): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent py-2">
                      <span class="text-dark"><?php echo date('M d, Y', strtotime($log['date'])); ?></span>
                      <span class="badge bg-success bg-opacity-10 text-success">Present</span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>

          <!-- Assignment Submissions Grid -->
          <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius:15px;">
              <h4 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">My Submissions</h4>
              <?php if (empty($submitted_assignments)): ?>
                <p class="text-muted small mb-0">You have not submitted any assignments yet.</p>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table align-middle small">
                    <thead>
                      <tr class="text-muted">
                        <th>Title</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Grade</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($submitted_assignments as $assign): 
                        $status_badge = 'bg-warning text-dark';
                        if ($assign['status'] === 'Graded') $status_badge = 'bg-success text-white';
                      ?>
                        <tr>
                          <td>
                            <strong class="text-dark small d-block"><?php echo sanitize($assign['title']); ?></strong>
                            <small class="text-muted" style="font-size:0.75rem;"><?php echo sanitize($assign['intern_title']); ?></small>
                          </td>
                          <td class="text-muted"><?php echo date('M d, Y', strtotime($assign['submitted_at'])); ?></td>
                          <td><span class="badge rounded-pill <?php echo $status_badge; ?>" style="font-size:0.65rem;"><?php echo sanitize($assign['status']); ?></span></td>
                          <td class="fw-bold text-primary"><?php echo sanitize($assign['grade'] ?: '-'); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Assignment Submission Modal -->
        <div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; overflow:hidden;">
              <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title font-title fw-bold" id="assignmentModalLabel">Submit Assignment File</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <form action="/student/internships.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4 bg-light">
                  
                  <div class="mb-3">
                    <label for="internship_id" class="form-label small text-muted">Select Active Internship <span class="text-danger">*</span></label>
                    <select name="internship_id" id="internship_id" class="form-select bg-white" required>
                      <?php foreach ($my_internships as $intern): ?>
                        <option value="<?php echo $intern['internship_id']; ?>"><?php echo sanitize($intern['title']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label for="assignTitle" class="form-label small text-muted">Assignment Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="assignTitle" class="form-control bg-white" required placeholder="E.g., LaTeX Manuscript Typesetting Part 1">
                  </div>

                  <div class="mb-3">
                    <label for="assignDesc" class="form-label small text-muted">Description / Comments</label>
                    <textarea name="description" id="assignDesc" rows="3" class="form-control bg-white" placeholder="Add details or notes for the faculty reviewer..."></textarea>
                  </div>

                  <div class="mb-3">
                    <label for="assignFile" class="form-label small text-muted">Assignment Document (PDF, ZIP, DOCX) <span class="text-danger">*</span></label>
                    <input type="file" name="assignment_file" id="assignFile" class="form-control bg-white" accept=".pdf,.zip,.docx" required>
                    <small class="text-muted d-block mt-1">Submit your completed file (Max 10MB).</small>
                  </div>

                </div>
                <div class="modal-footer border-0 bg-white">
                  <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#assignmentModal">Close</button>
                  <button type="submit" name="submit_assignment" class="btn btn-primary rounded-pill px-4 fw-bold">Upload Submission</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
