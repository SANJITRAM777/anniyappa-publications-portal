<?php
$page_title = "My LMS Courses - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Student');

$student_id = get_logged_in_user_id();
$course_id = (int)($_GET['course_id'] ?? 0);
$lesson_id = (int)($_GET['lesson_id'] ?? 0);

// Fetch enrolled courses
$enrollStmt = $pdo->prepare("
    SELECT ce.*, c.title, c.description, c.thumbnail 
    FROM course_enrollments ce
    JOIN courses c ON ce.course_id = c.id
    WHERE ce.student_id = ?
");
$enrollStmt->execute([$student_id]);
$enrolled_courses = $enrollStmt->fetchAll();

// If course selected, fetch syllabus & quizzes
$active_course = null;
$lessons = [];
$quizzes = [];
$active_lesson = null;
$materials = [];

if ($course_id > 0) {
    // Verify enrollment
    $verifyStmt = $pdo->prepare("SELECT id FROM course_enrollments WHERE course_id = ? AND student_id = ?");
    $verifyStmt->execute([$course_id, $student_id]);
    
    if ($verifyStmt->fetch()) {
        // Fetch course info
        $cStmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $cStmt->execute([$course_id]);
        $active_course = $cStmt->fetch();
        
        // Fetch lessons from course_lessons
        $lesStmt = $pdo->prepare("SELECT * FROM course_lessons WHERE course_id = ? ORDER BY sort_order ASC");
        $lesStmt->execute([$course_id]);
        $lessons = $lesStmt->fetchAll();
        
        // Fetch quizzes
        $qzStmt = $pdo->prepare("SELECT * FROM quizzes WHERE course_id = ?");
        $qzStmt->execute([$course_id]);
        $quizzes = $qzStmt->fetchAll();
        
        // If lesson selected, fetch lesson details & materials
        if ($lesson_id > 0) {
            $lesDetStmt = $pdo->prepare("SELECT * FROM course_lessons WHERE id = ? AND course_id = ?");
            $lesDetStmt->execute([$lesson_id, $course_id]);
            $active_lesson = $lesDetStmt->fetch();
            
            if ($active_lesson) {
                // Fetch materials from course_materials
                $matStmt = $pdo->prepare("SELECT * FROM course_materials WHERE lesson_id = ?");
                $matStmt->execute([$lesson_id]);
                $materials = $matStmt->fetchAll();
            }
        }
    } else {
        // Force redirect if not enrolled
        header("Location: /student/courses.php");
        exit;
    }
}
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
  <div class="row g-4">
    <!-- Navigation Sidebar -->
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
          <a href="/student/courses.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-laptop me-2"></i>My LMS Courses</a>
          <a href="/student/internships.php" class="list-group-item list-group-item-action"><i class="bi bi-briefcase me-2"></i>Internship Hub</a>
          <a href="/student/certificates.php" class="list-group-item list-group-item-action"><i class="bi bi-patch-check me-2"></i>Certificates</a>
          <a href="/student/profile.php" class="list-group-item list-group-item-action"><i class="bi bi-person me-2"></i>Edit Profile</a>
        </div>
      </div>
    </div>

    <!-- Main Content Panel -->
    <div class="col-lg-9">
      <?php if ($course_id === 0): ?>
        <!-- Courses List View -->
        <h2 class="font-title fw-bold text-dark mb-4 h3"><i class="bi bi-laptop me-2 text-primary"></i>My Enrolled Courses</h2>
        
        <?php if (empty($enrolled_courses)): ?>
          <div class="card text-center p-5 border-0 bg-light">
            <i class="bi bi-mortarboard display-3 text-muted"></i>
            <h4 class="mt-3">No Courses Found</h4>
            <p class="text-muted">You are not registered in any learning courses yet.</p>
            <a href="/contact.php" class="btn btn-primary rounded-pill px-4 mt-2">Inquire Training Programs</a>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($enrolled_courses as $c): ?>
              <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius:15px;">
                  <div class="bg-primary text-white p-4 text-center" style="background: linear-gradient(135deg, #0f4c81, #2563eb) !important;">
                    <i class="bi bi-journal-text display-4 text-warning"></i>
                  </div>
                  <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                      <h4 class="fw-bold h5 text-dark mb-2"><?php echo sanitize($c['title']); ?></h4>
                      <p class="text-muted small"><?php echo sanitize($c['description']); ?></p>
                      
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted small">Progress</small>
                        <small class="fw-bold text-primary small"><?php echo $c['progress_percent']; ?>%</small>
                      </div>
                      <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $c['progress_percent']; ?>%;" aria-valuenow="<?php echo $c['progress_percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </div>
                    
                    <a href="/student/courses.php?course_id=<?php echo $c['course_id']; ?>" class="btn btn-primary btn-sm rounded-pill py-2 w-100 mt-2">
                      Access Course <i class="bi bi-play-fill"></i>
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <!-- Single Course View -->
        <div class="d-flex align-items-center gap-2 mb-4">
          <a href="/student/courses.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left"></i> Back</a>
          <h2 class="font-title fw-bold text-dark mb-0 h3"><?php echo sanitize($active_course['title']); ?></h2>
        </div>

        <div class="row g-4">
          <!-- Syllabus list -->
          <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-light" style="border-radius:12px;">
              <h5 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Course syllabus</h5>
              
              <!-- Lessons list -->
              <div class="list-group list-group-flush mb-4 text-start small" style="border-radius: 8px;">
                <?php foreach ($lessons as $index => $les): ?>
                  <a href="/student/courses.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $les['id']; ?>" class="list-group-item list-group-item-action <?php echo $lesson_id == $les['id'] ? 'active bg-primary text-white' : ''; ?>">
                    <strong><?php echo $index + 1; ?>.</strong> <?php echo sanitize($les['title']); ?>
                  </a>
                <?php endforeach; ?>
              </div>
              
              <!-- Quizzes list -->
              <h5 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Quiz / Assessment</h5>
              <div class="list-group list-group-flush text-start small" style="border-radius: 8px;">
                <?php foreach ($quizzes as $qz): ?>
                  <a href="/student/quiz.php?quiz_id=<?php echo $qz['id']; ?>" class="list-group-item list-group-item-action text-danger fw-semibold">
                    <i class="bi bi-question-diamond me-2 text-danger"></i>Take: <?php echo sanitize($qz['title']); ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Video playback & Material area -->
          <div class="col-md-8">
            <?php if ($active_lesson): ?>
              <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius:15px;">
                <h3 class="fw-bold h5 text-dark mb-3"><?php echo sanitize($active_lesson['title']); ?></h3>
                
                <!-- Video Embed -->
                <?php if (!empty($active_lesson['video_url'])): ?>
                  <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm mb-4">
                    <iframe src="<?php echo sanitize($active_lesson['video_url']); ?>" title="Lecture Video" allowfullscreen></iframe>
                  </div>
                <?php endif; ?>
                
                <h4 class="fw-bold h6 text-dark border-bottom pb-2 mb-3">Lecture Transcript / Content</h4>
                <p class="text-dark small" style="line-height: 1.6;"><?php echo nl2br(sanitize($active_lesson['content'])); ?></p>
                
                <!-- Notes / PDF materials -->
                <h4 class="fw-bold h6 text-dark border-bottom pb-2 mt-4 mb-3">Study Notes & Materials</h4>
                <?php if (empty($materials)): ?>
                  <p class="text-muted small">No reading files uploaded for this lesson.</p>
                <?php else: ?>
                  <div class="list-group list-group-flush">
                    <?php foreach ($materials as $mat): ?>
                      <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent py-2.5">
                        <div class="d-flex align-items-center gap-2">
                          <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                          <div>
                            <strong class="text-dark small d-block"><?php echo sanitize($mat['title']); ?></strong>
                            <small class="text-muted" style="font-size:0.75rem;">Format: <?php echo sanitize($mat['type']); ?></small>
                          </div>
                        </div>
                        <a href="/download.php?type=material&id=<?php echo $mat['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 font-monospace" style="font-size:0.75rem;">Download</a>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div class="card text-center p-5 border-0 bg-light">
                <i class="bi bi-youtube display-3 text-muted"></i>
                <h4 class="mt-3">Start Studying</h4>
                <p class="text-muted">Select a lesson title from the left syllabus sidebar to begin lecture sessions.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
