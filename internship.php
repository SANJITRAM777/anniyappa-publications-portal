<?php
$page_title = "Internship & Training | Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$success = '';
$error = '';

// Handle internship application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_internship'])) {
    if (!is_logged_in()) {
        $_SESSION['login_redirect'] = "/internship.php";
        header("Location: /login.php");
        exit;
    }
    
    // Check if the user is a student
    if (!has_role('Student')) {
        $error = "Only students are authorized to apply for internships.";
    } else {
        $internship_id = (int)($_POST['internship_id'] ?? 0);
        $user_id = get_logged_in_user_id();
        
        if ($internship_id <= 0 || !isset($_FILES['resume'])) {
            $error = "Please fill in all required fields and upload your resume.";
        } else {
            // Check if already applied
            $check = $pdo->prepare("SELECT id FROM applications WHERE internship_id = ? AND student_id = ?");
            $check->execute([$internship_id, $user_id]);
            
            if ($check->fetch()) {
                $error = "You have already applied for this internship program.";
            } else {
                $file = $_FILES['resume'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if ($ext !== 'pdf' && $ext !== 'docx') {
                    $error = "Only PDF and DOCX documents are allowed.";
                } elseif ($file['size'] > 5000000) {
                    $error = "File size cannot exceed 5MB.";
                } else {
                    $filename = 'resume_' . $user_id . '_' . time() . '.' . $ext;
                    $dest = __DIR__ . '/uploads/resumes/' . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        try {
                            $stmt = $pdo->prepare("INSERT INTO applications (internship_id, student_id, resume_path, status) VALUES (?, ?, ?, 'Pending')");
                            $stmt->execute([$internship_id, $user_id, 'uploads/resumes/' . $filename]);
                            $success = "Your internship application has been submitted successfully! Check progress in your dashboard.";
                        } catch (PDOException $e) {
                            $error = "Database error: " . $e->getMessage();
                        }
                    } else {
                        $error = "Failed to upload resume file. Please try again.";
                    }
                }
            }
        }
    }
}

// Fetch all active internships
$internshipsStmt = $pdo->query("SELECT * FROM internships WHERE status = 'Active' ORDER BY title ASC");
$internships = $internshipsStmt->fetchAll();
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<!-- Page Header -->
<section class="hero-section text-center d-flex align-items-center" style="padding: 120px 0 80px; background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%); color: #fff;">
  <div class="container hero-content animate-up text-white">
    <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
      <i class="bi bi-mortarboard me-1"></i>Education & Development
    </span>
    <h1 class="hero-title mb-3 fs-2 text-white">Internships & Technical Training</h1>
    <p class="hero-subtitle mx-auto mb-0 text-white-50" style="max-width: 650px;">Develop hands-on industry competencies, collaborate with professional editorial boards, and master advanced academic typesetting and publishing tools.</p>
  </div>
</section>

<!-- Overview Section -->
<section class="section-padding bg-white">
  <div class="container">
    <div class="row align-items-center">
      
      <!-- Left Column: Tabs -->
      <div class="col-lg-6 mb-5 mb-lg-0 animate-up">
        <div class="section-title">
          <span class="text-primary fw-semibold text-uppercase">Academic Initiative</span>
          <h2 class="mt-2">Empowering Students & Researchers</h2>
        </div>
        <p class="lead text-dark">Our joint initiative with the SB Institute bridges the gap between classroom theory and professional academic publishing workflows.</p>
        <p class="mb-4">We offer structured 4-to-12 week hybrid internships as well as specialized, hands-on certification training courses. Participants collaborate with international reviewers and typesetters, learning how scientific text and research metrics are built.</p>
        
        <!-- Tab Navigation for Benefits & Eligibility -->
        <ul class="nav nav-tabs border-bottom mb-4" id="internshipTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-dark border-0 border-bottom" id="benefits-tab" data-bs-toggle="tab" data-bs-target="#benefits" type="button" role="tab" aria-controls="benefits" aria-selected="true">Program Benefits</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark border-0 border-bottom" id="eligibility-tab" data-bs-toggle="tab" data-bs-target="#eligibility" type="button" role="tab" aria-controls="eligibility" aria-selected="false">Eligibility Criteria</button>
          </li>
        </ul>
        
        <div class="tab-content" id="internshipTabContent">
          <!-- Tab 1: Benefits -->
          <div class="tab-pane fade show active" id="benefits" role="tabpanel" aria-labelledby="benefits-tab">
            <div class="row g-3 small text-muted">
              <div class="col-sm-6 d-flex">
                <i class="bi bi-patch-check-fill text-primary me-2 mt-1"></i>
                <span>Verified Training/Internship Certificate</span>
              </div>
              <div class="col-sm-6 d-flex">
                <i class="bi bi-patch-check-fill text-primary me-2 mt-1"></i>
                <span>One-on-One Editorial Mentorship</span>
              </div>
              <div class="col-sm-6 d-flex">
                <i class="bi bi-patch-check-fill text-primary me-2 mt-1"></i>
                <span>Letter of Recommendation (Performance-based)</span>
              </div>
              <div class="col-sm-6 d-flex">
                <i class="bi bi-patch-check-fill text-primary me-2 mt-1"></i>
                <span>Practical Exposure to indexing standards</span>
              </div>
            </div>
          </div>
          <!-- Tab 2: Eligibility -->
          <div class="tab-pane fade" id="eligibility" role="tabpanel" aria-labelledby="eligibility-tab">
            <div class="row g-3 small text-muted">
              <div class="col-sm-6 d-flex">
                <i class="bi bi-check-square-fill text-primary me-2 mt-1"></i>
                <span>Pursuing or completed UG/PG Degree in CS, English, or Science</span>
              </div>
              <div class="col-sm-6 d-flex">
                <i class="bi bi-check-square-fill text-primary me-2 mt-1"></i>
                <span>Strong written English and formatting abilities</span>
              </div>
              <div class="col-sm-6 d-flex">
                <i class="bi bi-check-square-fill text-primary me-2 mt-1"></i>
                <span>Basic knowledge of Web tech, LaTeX, or editing suites</span>
              </div>
              <div class="col-sm-6 d-flex">
                <i class="bi bi-check-square-fill text-primary me-2 mt-1"></i>
                <span>Availability for 10-15 hours per week (flexible schedules)</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Visual Box -->
      <div class="col-lg-6 animate-up" style="animation-delay: 0.2s;">
        <div class="p-5 rounded-4 text-white text-center" style="background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%);">
          <i class="bi bi-mortarboard-fill display-3 mb-3 d-block text-warning"></i>
          <h3 class="font-title mb-2 text-white">Build Your Credentials</h3>
          <p class="text-white-50 px-md-4 mb-4">Learn manuscript compilation, digital typesetting rules, citation styles, indexing methodologies, and technical writing protocols.</p>
          <a href="#activeInternshipsSection" class="btn btn-light rounded-pill text-primary fw-semibold px-4 py-2">
            <i class="bi bi-file-earmark-text me-2"></i>Apply For Open Internships
          </a>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Active Internship Openings Section -->
<section class="section-padding bg-light border-top" id="activeInternshipsSection">
  <div class="container">
    <div class="section-title text-center mb-5">
      <span class="text-primary fw-semibold text-uppercase">Careers & Exposure</span>
      <h2>Active Internship Programs</h2>
    </div>

    <?php if (!empty($success)): ?>
      <?php echo get_alert($success, 'success'); ?>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <?php echo get_alert($error, 'danger'); ?>
    <?php endif; ?>

    <div class="row g-4">
      <?php if (empty($internships)): ?>
        <div class="col-12 text-center py-5">
          <p class="text-muted">No active internship openings available at this time. Please check back later.</p>
        </div>
      <?php else: ?>
        <?php foreach ($internships as $intern): ?>
          <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 15px;">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="badge bg-primary px-3 py-1.5 rounded-pill"><?php echo sanitize($intern['domain']); ?></span>
                <span class="text-muted small fw-semibold"><i class="bi bi-clock me-1"></i><?php echo $intern['duration_weeks']; ?> Weeks</span>
              </div>
              <h3 class="fw-bold h4 text-dark mb-2"><?php echo sanitize($intern['title']); ?></h3>
              <p class="text-muted small flex-grow-1"><?php echo nl2br(sanitize($intern['description'])); ?></p>
              
              <hr class="my-3">
              
              <?php if (is_logged_in()): ?>
                <?php if (has_role('Student')): ?>
                  <button class="btn btn-outline-primary btn-sm rounded-pill px-4 align-self-start" data-bs-toggle="collapse" data-bs-target="#applyCollForm<?php echo $intern['id']; ?>">
                    Apply Now &rarr;
                  </button>
                  
                  <!-- Application file form collapse -->
                  <div class="collapse mt-3" id="applyCollForm<?php echo $intern['id']; ?>">
                    <form action="/internship.php#activeInternshipsSection" method="POST" enctype="multipart/form-data" class="p-3 bg-light rounded-3 border">
                      <input type="hidden" name="internship_id" value="<?php echo $intern['id']; ?>">
                      <h5 class="fw-bold text-dark h6 mb-2">Upload Resume Details</h5>
                      <div class="mb-3">
                        <label class="form-label small text-muted">Upload Resume (PDF/DOCX) <span class="text-danger">*</span></label>
                        <input type="file" name="resume" class="form-control form-control-sm bg-white" accept=".pdf,.docx" required>
                        <small class="text-muted d-block mt-1">Submit your academic resume (Max 5MB).</small>
                      </div>
                      <button type="submit" name="apply_internship" class="btn btn-primary btn-sm rounded-pill px-4">
                        Submit Application
                      </button>
                    </form>
                  </div>
                <?php else: ?>
                  <div class="alert alert-warning py-2 px-3 small rounded-3 mb-0">
                    <i class="bi bi-exclamation-circle me-1"></i>Only Student role is permitted to apply.
                  </div>
                <?php endif; ?>
              <?php else: ?>
                <div class="alert alert-warning py-2 px-3 small rounded-3 mb-0">
                  <i class="bi bi-exclamation-circle me-1"></i>Please <a href="/login.php" class="alert-link">Sign In</a> to apply.
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Technical Courses (Static info) -->
<section class="section-padding bg-white border-top">
  <div class="container">
    <div class="section-title text-center mb-5">
      <span class="text-primary fw-semibold text-uppercase">Academic Workshops</span>
      <h2>Technical Certification Courses</h2>
    </div>
    
    <div class="row g-4 justify-content-center">
      <!-- Course 1 -->
      <div class="col-lg-4 col-md-6">
        <div class="p-4 rounded-4 border bg-light h-100 d-flex flex-column shadow-xs">
          <span class="badge bg-primary align-self-start mb-3">4 Weeks</span>
          <h4 class="fw-bold h5">LaTeX Typesetting</h4>
          <p class="small text-muted flex-grow-1">Learn to compile equations, design tables, write custom class files, and construct publications-grade reports in LaTeX/Overleaf.</p>
          <hr>
          <a href="/contact.php" class="btn btn-outline-primary btn-sm rounded-pill w-100">Inquire Details</a>
        </div>
      </div>
      <!-- Course 2 -->
      <div class="col-lg-4 col-md-6">
        <div class="p-4 rounded-4 border bg-light h-100 d-flex flex-column shadow-xs">
          <span class="badge bg-primary align-self-start mb-3">6 Weeks</span>
          <h4 class="fw-bold h5">Research Methodology</h4>
          <p class="small text-muted flex-grow-1">Establish academic methodology frameworks, design hypotheses, organize literature catalogs, and apply analytical software protocols.</p>
          <hr>
          <a href="/contact.php" class="btn btn-outline-primary btn-sm rounded-pill w-100">Inquire Details</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
