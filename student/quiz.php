<?php
$page_title = "LMS Quiz - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Student');

$student_id = get_logged_in_user_id();
$quiz_id = (int)($_GET['quiz_id'] ?? 0);

// Fetch quiz info
$quizStmt = $pdo->prepare("SELECT q.*, c.title AS course_title, c.id AS course_id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ?");
$quizStmt->execute([$quiz_id]);
$quiz = $quizStmt->fetch();

if (!$quiz) {
    header("Location: /student/courses.php");
    exit;
}

// Fetch questions
$questionsStmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
$questionsStmt->execute([$quiz_id]);
$questions = $questionsStmt->fetchAll();

$score = 0;
$max_score = count($questions);
$submitted = false;
$results_recorded = false;
$certificate_issued = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $answers = $_POST['answers'] ?? [];
    
    foreach ($questions as $q) {
        $q_id = $q['id'];
        $user_ans = $answers[$q_id] ?? '';
        if ($user_ans === $q['correct_option']) {
            $score++;
        }
    }
    
    $submitted = true;
    
    // Save quiz result
    try {
        $insResult = $pdo->prepare("INSERT INTO results (quiz_id, student_id, score, max_score) VALUES (?, ?, ?, ?)");
        $insResult->execute([$quiz_id, $student_id, $score, $max_score]);
        $results_recorded = true;
        
        // Calculate passing threshold (70%)
        $passing_score = ceil($max_score * 0.7);

        if ($score >= $passing_score) {
            // Update enrollment progress to 100% only if passed
            $upProg = $pdo->prepare("UPDATE course_enrollments SET progress_percent = 100, completed_at = CURRENT_TIMESTAMP WHERE course_id = ? AND student_id = ?");
            $upProg->execute([$quiz['course_id'], $student_id]);
            
            $cert_code = 'CERT-LMS-' . $quiz_id . '-' . strtoupper(dechex(time())) . rand(10, 99);
            
            // Check if certificate already issued
            $chkCert = $pdo->prepare("SELECT id FROM certificates WHERE user_id = ? AND type = 'Course' AND reference_id = ?");
            $chkCert->execute([$student_id, $quiz['course_id']]);
            
            if (!$chkCert->fetch()) {
                $insCert = $pdo->prepare("INSERT INTO certificates (user_id, type, reference_id, certificate_code, issue_date) VALUES (?, 'Course', ?, ?, CURDATE())");
                $insCert->execute([$student_id, $quiz['course_id'], $cert_code]);
                $certificate_issued = true;
            }
        } else {
            // Update partial progress without setting completed_at
            $calcProgress = min(90, max(50, round(($score / ($max_score ?: 1)) * 100)));
            $upProg = $pdo->prepare("UPDATE course_enrollments SET progress_percent = GREATEST(progress_percent, ?) WHERE course_id = ? AND student_id = ?");
            $upProg->execute([$calcProgress, $quiz['course_id'], $student_id]);
        }
    } catch (PDOException $e) {
        $error = "Failed to record results: " . $e->getMessage();
    }
}
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <!-- Back button -->
      <a href="/student/courses.php?course_id=<?php echo $quiz['course_id']; ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 mb-4"><i class="bi bi-arrow-left"></i> Return to Course</a>
      
      <div class="card border-0 shadow-sm p-4 p-lg-5" style="border-radius:15px; overflow:hidden;">
        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1.5 rounded-pill mb-3 align-self-start">LMS Assessment</span>
        <h1 class="font-title text-dark fw-bold mb-1" style="font-size: 1.8rem;"><?php echo sanitize($quiz['title']); ?></h1>
        <p class="text-muted small mb-4">Course: <strong><?php echo sanitize($quiz['course_title']); ?></strong></p>

        <?php if ($submitted): ?>
          <!-- Results View -->
          <div class="text-center py-4 border-bottom mb-4">
            <h2 class="fw-bold text-dark mb-2">Quiz Finished</h2>
            <div class="display-4 fw-bold text-primary mb-2"><?php echo $score; ?> / <?php echo $max_score; ?></div>
            
            <?php 
            $pct = $max_score > 0 ? round(($score / $max_score) * 100) : 0; 
            if ($pct >= 70):
            ?>
              <div class="badge bg-success py-2 px-4 rounded-pill fs-6 mb-3"><i class="bi bi-patch-check-fill me-1"></i>Passed (<?php echo $pct; ?>%)</div>
              <?php if ($certificate_issued): ?>
                <div class="alert alert-success py-2 px-3 small rounded-3 mt-2">
                  <i class="bi bi-award-fill me-1"></i>Congratulations! You passed the criteria. A course completion certificate has been generated in <a href="/student/certificates.php" class="alert-link">Certificates Hub</a>.
                </div>
              <?php endif; ?>
            <?php else: ?>
              <div class="badge bg-danger py-2 px-4 rounded-pill fs-6 mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i>Failed (<?php echo $pct; ?>%)</div>
              <p class="text-muted small">Required score is 70% to earn the certificate. Try reviewing course lessons and attempts again.</p>
            <?php endif; ?>
          </div>

          <!-- Questions review -->
          <h4 class="fw-bold h5 text-dark mb-4">Question Checklist Review</h4>
          <?php foreach ($questions as $index => $q): ?>
            <div class="p-3 bg-light rounded-3 mb-3 border">
              <strong class="text-dark small d-block mb-2"><?php echo $index + 1; ?>. <?php echo sanitize($q['question_text']); ?></strong>
              <div class="row g-2 small">
                <div class="col-sm-6">A: <?php echo sanitize($q['option_a']); ?></div>
                <div class="col-sm-6">B: <?php echo sanitize($q['option_b']); ?></div>
                <div class="col-sm-6">C: <?php echo sanitize($q['option_c']); ?></div>
                <div class="col-sm-6">D: <?php echo sanitize($q['option_d']); ?></div>
              </div>
              <div class="mt-2 pt-2 border-top text-muted small">
                Correct Option: <strong class="text-success"><?php echo $q['correct_option']; ?></strong>
              </div>
            </div>
          <?php endforeach; ?>

        <?php else: ?>
          <!-- Question Form -->
          <form action="/student/quiz.php?quiz_id=<?php echo $quiz_id; ?>" method="POST">
            <?php foreach ($questions as $index => $q): ?>
              <div class="mb-4 p-4 border rounded-3 bg-light">
                <h5 class="fw-bold text-dark h6 mb-3"><strong>Question <?php echo $index + 1; ?>:</strong> <?php echo sanitize($q['question_text']); ?></h5>
                
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="optA<?php echo $q['id']; ?>" value="A" required>
                  <label class="form-check-label small text-dark" for="optA<?php echo $q['id']; ?>"><?php echo sanitize($q['option_a']); ?></label>
                </div>
                
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="optB<?php echo $q['id']; ?>" value="B">
                  <label class="form-check-label small text-dark" for="optB<?php echo $q['id']; ?>"><?php echo sanitize($q['option_b']); ?></label>
                </div>
                
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="optC<?php echo $q['id']; ?>" value="C">
                  <label class="form-check-label small text-dark" for="optC<?php echo $q['id']; ?>"><?php echo sanitize($q['option_c']); ?></label>
                </div>
                
                <div class="form-check mb-0">
                  <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="optD<?php echo $q['id']; ?>" value="D">
                  <label class="form-check-label small text-dark" for="optD<?php echo $q['id']; ?>"><?php echo sanitize($q['option_d']); ?></label>
                </div>
              </div>
            <?php endforeach; ?>

            <button type="submit" name="submit_quiz" class="btn btn-danger w-100 rounded-pill py-3 fw-bold mt-2">
              Submit Answers & Finish <i class="bi bi-send-fill ms-2"></i>
            </button>
          </form>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
