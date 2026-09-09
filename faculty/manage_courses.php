<?php
$page_title = "Manage Courses - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(['Faculty', 'Author']);

$instructor_id = get_logged_in_user_id();
$course_id = (int)($_GET['course_id'] ?? 0);
$success = '';
$error = '';

// Fetch assigned courses
$coursesStmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ? ORDER BY title ASC");
$coursesStmt->execute([$instructor_id]);
$courses = $coursesStmt->fetchAll();

// If course selected, verify ownership & load details
$active_course = null;
$lessons = [];
$quizzes = [];

if ($course_id > 0) {
    $cStmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $cStmt->execute([$course_id, $instructor_id]);
    $active_course = $cStmt->fetch();
    
    if ($active_course) {
        // Fetch lessons
        $lesStmt = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_no ASC");
        $lesStmt->execute([$course_id]);
        $lessons = $lesStmt->fetchAll();
        
        // Fetch quizzes
        $qzStmt = $pdo->prepare("SELECT * FROM quizzes WHERE course_id = ?");
        $qzStmt->execute([$course_id]);
        $quizzes = $qzStmt->fetchAll();
    } else {
        header("Location: /faculty/manage_courses.php");
        exit;
    }
}

// Process Add Lesson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lesson'])) {
    $title = trim($_POST['title'] ?? '');
    $order_no = (int)($_POST['order_no'] ?? 1);
    $video_url = trim($_POST['video_url'] ?? '');
    $content_text = trim($_POST['content_text'] ?? '');
    
    if (empty($title)) {
        $error = "Lesson title is required.";
    } else {
        try {
            $insLes = $pdo->prepare("
                INSERT INTO lessons (course_id, title, video_url, content_text, order_no) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insLes->execute([$course_id, $title, $video_url, $content_text, $order_no]);
            $lesson_id = $pdo->lastInsertId();
            
            // Handle optional PDF material upload
            if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['material_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'material_' . $lesson_id . '_' . time() . '.' . $ext;
                $dest = __DIR__ . '/../uploads/books/' . $filename; // reuse upload directory
                
                if (!is_dir(__DIR__ . '/../uploads/books/')) {
                    mkdir(__DIR__ . '/../uploads/books/', 0777, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $insMat = $pdo->prepare("INSERT INTO materials (lesson_id, title, file_path, type) VALUES (?, ?, ?, ?)");
                    $insMat->execute([$lesson_id, $title . " Reading PDF", 'uploads/books/' . $filename, strtoupper($ext)]);
                }
            }
            
            $success = "Lesson added successfully to the syllabus!";
            // Refresh
            $lesStmt->execute([$course_id]);
            $lessons = $lesStmt->fetchAll();
        } catch (PDOException $e) {
            $error = "Failed to add lesson: " . $e->getMessage();
        }
    }
}

// Process Add Quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_quiz'])) {
    $quiz_title = trim($_POST['quiz_title'] ?? '');
    
    if (empty($quiz_title)) {
        $error = "Quiz title is required.";
    } else {
        try {
            $insQz = $pdo->prepare("INSERT INTO quizzes (course_id, title) VALUES (?, ?)");
            $insQz->execute([$course_id, $quiz_title]);
            $success = "Quiz created successfully! Now configure questions below.";
            // Refresh
            $qzStmt->execute([$course_id]);
            $quizzes = $qzStmt->fetchAll();
        } catch (PDOException $e) {
            $error = "Failed to create quiz: " . $e->getMessage();
        }
    }
}

// Process Add Quiz Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $quiz_id = (int)($_POST['quiz_id'] ?? 0);
    $q_text = trim($_POST['question_text'] ?? '');
    $opt_a = trim($_POST['opt_a'] ?? '');
    $opt_b = trim($_POST['opt_b'] ?? '');
    $opt_c = trim($_POST['opt_c'] ?? '');
    $opt_d = trim($_POST['opt_d'] ?? '');
    $correct = $_POST['correct_option'] ?? 'A';
    
    if ($quiz_id <= 0 || empty($q_text) || empty($opt_a) || empty($opt_b) || empty($opt_c) || empty($opt_d)) {
        $error = "All question fields and option details are required.";
    } else {
        try {
            $insQQ = $pdo->prepare("
                INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insQQ->execute([$quiz_id, $q_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct]);
            $success = "Quiz question added successfully!";
        } catch (PDOException $e) {
            $error = "Failed to add question: " . $e->getMessage();
        }
    }
}
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
          <a href="/faculty/manage_courses.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-laptop me-2"></i>Manage Courses</a>
          <a href="/faculty/grade_assignments.php" class="list-group-item list-group-item-action"><i class="bi bi-journal-check me-2"></i>Grade Assignments</a>
          <a href="/faculty/research.php" class="list-group-item list-group-item-action"><i class="bi bi-mortarboard me-2"></i>Research Projects</a>
          <a href="/faculty/profile.php" class="list-group-item list-group-item-action"><i class="bi bi-person me-2"></i>Edit Profile</a>
        </div>
      </div>
    </div>

    <!-- Main Content Panel -->
    <div class="col-lg-9">
      <h2 class="font-title fw-bold text-dark mb-4 h3">Manage LMS Courses</h2>
      
      <?php if (!empty($success)): ?>
        <?php echo get_alert($success, 'success'); ?>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <?php echo get_alert($error, 'danger'); ?>
      <?php endif; ?>

      <!-- Course Selector -->
      <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius:15px;">
        <h3 class="fw-bold text-dark h6 mb-3 border-bottom pb-2">Select Assigned Course</h3>
        <div class="row">
          <div class="col-md-6">
            <select class="form-select bg-light rounded-pill" onchange="window.location.href='/faculty/manage_courses.php?course_id=' + this.value">
              <option value="0" <?php echo $course_id === 0 ? 'selected' : ''; ?>>-- Choose Course --</option>
              <?php foreach ($courses as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $course_id === $c['id'] ? 'selected' : ''; ?>><?php echo sanitize($c['title']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <?php if ($active_course): ?>
        <!-- Lessons & Syllabus manager -->
        <div class="row g-4">
          <!-- Syllabus list -->
          <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius:15px;">
              <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Syllabus Outline</h3>
              <?php if (empty($lessons)): ?>
                <p class="text-muted small">No lesson chapters defined yet.</p>
              <?php else: ?>
                <div class="list-group list-group-flush mb-4 small">
                  <?php foreach ($lessons as $les): ?>
                    <div class="list-group-item px-0 bg-transparent py-2.5 d-flex justify-content-between align-items-center">
                      <div>
                        <strong class="text-dark d-block">Chapter <?php echo $les['order_no']; ?>: <?php echo sanitize($les['title']); ?></strong>
                        <span class="text-muted" style="font-size:0.75rem;">Video: <?php echo $les['video_url'] ? 'Yes' : 'No'; ?></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              
              <!-- Quiz lists -->
              <h3 class="fw-bold text-dark h5 mt-4 mb-3 border-bottom pb-2">Assessments / Quizzes</h3>
              <?php if (empty($quizzes)): ?>
                <p class="text-muted small">No quizzes configured yet.</p>
              <?php else: ?>
                <div class="list-group list-group-flush mb-0 small">
                  <?php foreach ($quizzes as $qz): ?>
                    <div class="list-group-item px-0 bg-transparent py-2.5 d-flex justify-content-between align-items-center">
                      <strong class="text-dark"><i class="bi bi-question-diamond-fill me-1 text-danger"></i><?php echo sanitize($qz['title']); ?></strong>
                      <button class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 font-monospace" style="font-size:0.75rem;" data-bs-toggle="collapse" data-bs-target="#questionCollapse<?php echo $qz['id']; ?>">Add Question</button>
                    </div>
                    
                    <!-- Question builder collapse -->
                    <div class="collapse border rounded-3 p-3 bg-light mt-2 mb-3" id="questionCollapse<?php echo $qz['id']; ?>">
                      <form action="/faculty/manage_courses.php?course_id=<?php echo $course_id; ?>" method="POST">
                        <input type="hidden" name="quiz_id" value="<?php echo $qz['id']; ?>">
                        <h6 class="fw-bold text-dark mb-3">Add MC Question</h6>
                        
                        <div class="mb-2">
                          <label class="form-label small text-muted">Question Text</label>
                          <input type="text" name="question_text" class="form-control form-control-sm bg-white" required placeholder="E.g., What does LaTeX compile to?">
                        </div>
                        <div class="row g-2 mb-2">
                          <div class="col-6">
                            <input type="text" name="opt_a" class="form-control form-control-sm bg-white" required placeholder="Option A">
                          </div>
                          <div class="col-6">
                            <input type="text" name="opt_b" class="form-control form-control-sm bg-white" required placeholder="Option B">
                          </div>
                          <div class="col-6">
                            <input type="text" name="opt_c" class="form-control form-control-sm bg-white" required placeholder="Option C">
                          </div>
                          <div class="col-6">
                            <input type="text" name="opt_d" class="form-control form-control-sm bg-white" required placeholder="Option D">
                          </div>
                        </div>
                        <div class="mb-3">
                          <label class="form-label small text-muted">Correct Option</label>
                          <select name="correct_option" class="form-select form-select-sm bg-white">
                            <option value="A">Option A</option>
                            <option value="B">Option B</option>
                            <option value="C">Option C</option>
                            <option value="D">Option D</option>
                          </select>
                        </div>
                        <button type="submit" name="add_question" class="btn btn-danger btn-sm rounded-pill px-4">Submit Question</button>
                      </form>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Add Lesson / Create Quiz Forms -->
          <div class="col-md-6">
            <!-- Add Lesson Card -->
            <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius:15px;">
              <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Add New Lesson</h3>
              
              <form action="/faculty/manage_courses.php?course_id=<?php echo $course_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="mb-2">
                  <label class="form-label small text-muted">Lesson Title <span class="text-danger">*</span></label>
                  <input type="text" name="title" class="form-control bg-light" required placeholder="E.g., Typesetting Matrices in LaTeX">
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label small text-muted">Order Number</label>
                    <input type="number" name="order_no" class="form-control bg-light" required value="<?php echo count($lessons) + 1; ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label small text-muted">YouTube Video URL</label>
                    <input type="url" name="video_url" class="form-control bg-light" placeholder="https://youtube.com/...">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label small text-muted">Content / Brief Transcript</label>
                  <textarea name="content_text" rows="4" class="form-control bg-light" placeholder="Enter lesson notes, description, coding instructions..."></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label small text-muted">Reference PDF Attachment (Optional)</label>
                  <input type="file" name="material_file" class="form-control bg-light" accept=".pdf">
                </div>
                <button type="submit" name="add_lesson" class="btn btn-primary rounded-pill px-4 py-2 small">Add Lesson Chapter</button>
              </form>
            </div>

            <!-- Create Quiz Card -->
            <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius:15px;">
              <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Create Quiz</h3>
              <form action="/faculty/manage_courses.php?course_id=<?php echo $course_id; ?>" method="POST">
                <div class="mb-3">
                  <label class="form-label small text-muted">Quiz Title <span class="text-danger">*</span></label>
                  <input type="text" name="quiz_title" class="form-control bg-light" required placeholder="E.g., LaTeX Equations Assessment">
                </div>
                <button type="submit" name="add_quiz" class="btn btn-danger rounded-pill px-4 py-2 small">Create Quiz Module</button>
              </form>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
