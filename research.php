<?php
$page_title = "Research Collaboration Portal | Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$success = '';
$error = '';

// Process research proposal submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_proposal'])) {
    if (!is_logged_in()) {
        $_SESSION['login_redirect'] = "/research.php";
        header("Location: /login.php");
        exit;
    }

    $project_id = (int)($_POST['project_id'] ?? 0);
    $proposal_title = trim($_POST['proposal_title'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
    
    // Check file upload
    if (empty($proposal_title) || empty($abstract) || !isset($_FILES['proposal_file'])) {
        $error = "Please fill in all fields and select a proposal PDF.";
    } else {
        $file = $_FILES['proposal_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($ext !== 'pdf') {
            $error = "Only PDF documents are allowed.";
        } elseif ($file['size'] > 5000000) { // 5MB limit
            $error = "File size cannot exceed 5MB.";
        } else {
            // Safe upload filename
            $filename = 'proposal_' . time() . '_' . rand(1000, 9999) . '.pdf';
            $dest = __DIR__ . '/uploads/proposals/' . $filename;
            
            // Create folder if not exists
            if (!is_dir(__DIR__ . '/uploads/proposals/')) {
                mkdir(__DIR__ . '/uploads/proposals/', 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO proposals (project_id, author_id, proposal_title, abstract, file_path, status) 
                        VALUES (?, ?, ?, ?, ?, 'Pending')
                    ");
                    $stmt->execute([$project_id, get_logged_in_user_id(), $proposal_title, $abstract, 'uploads/proposals/' . $filename]);
                    $success = "Your chapter proposal has been submitted successfully! Reviewers will assess the details.";
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            } else {
                $error = "Failed to upload proposal document file. Please ensure upload permissions are set.";
            }
        }
    }
}

// Fetch all research projects
$projectsStmt = $pdo->query("
    SELECT p.*, prof.full_name AS instructor_name 
    FROM research_projects p
    JOIN user_profiles prof ON p.faculty_id = prof.user_id
    ORDER BY p.created_at DESC
");
$projects = $projectsStmt->fetchAll();
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<!-- Page Header -->
<section class="hero-section text-center d-flex align-items-center" style="padding: 120px 0 80px; background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%); color: #fff;">
  <div class="container hero-content animate-up text-white">
    <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
      <i class="bi bi-mortarboard me-1"></i>Academic Collaboration
    </span>
    <h1 class="hero-title mb-3 fs-2 text-white">Research Collaboration Portal</h1>
    <p class="hero-subtitle mx-auto mb-0 text-white-50" style="max-width: 650px;">Partner with academic faculty, submit chapter proposals, and collaborate on cutting-edge monographs in scientific fields.</p>
  </div>
</section>

<div class="container py-5">
  <div class="row g-4">
    <!-- Active Projects List -->
    <div class="col-lg-8">
      <h2 class="font-title fw-bold text-dark mb-4 h3"><i class="bi bi-folder-symlink me-2 text-primary"></i>Active Research Initiatives</h2>
      
      <?php if (!empty($success)): ?>
        <?php echo get_alert($success, 'success'); ?>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <?php echo get_alert($error, 'danger'); ?>
      <?php endif; ?>

      <?php if (empty($projects)): ?>
        <div class="card text-center p-5 border-0 bg-light">
          <i class="bi bi-clipboard-x display-4 text-muted"></i>
          <h4 class="mt-3">No Open Projects</h4>
          <p class="text-muted">There are no academic project proposals open for collaboration at this time.</p>
        </div>
      <?php else: ?>
        <?php foreach ($projects as $proj): ?>
          <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 15px;">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge bg-success bg-opacity-10 text-success px-3 py-1.5 rounded-pill"><?php echo sanitize($proj['status']); ?></span>
              <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?php echo date('M d, Y', strtotime($proj['created_at'])); ?></small>
            </div>
            <h3 class="fw-bold h4 text-dark mb-2"><?php echo sanitize($proj['title']); ?></h3>
            <p class="text-muted small mb-3">Project Lead: <strong><?php echo sanitize($proj['instructor_name']); ?></strong></p>
            <p class="text-dark small mb-4"><?php echo nl2br(sanitize($proj['description'])); ?></p>
            
            <?php if (is_logged_in()): ?>
              <button class="btn btn-primary btn-sm rounded-pill px-4 align-self-start" data-bs-toggle="collapse" data-bs-target="#proposalForm<?php echo $proj['id']; ?>">
                <i class="bi bi-send me-1"></i>Submit Chapter Proposal
              </button>
              
              <!-- Proposal Form Collapse -->
              <div class="collapse mt-4 pt-3 border-top" id="proposalForm<?php echo $proj['id']; ?>">
                <form action="/research.php" method="POST" enctype="multipart/form-data" class="p-3 bg-light rounded-3">
                  <input type="hidden" name="project_id" value="<?php echo $proj['id']; ?>">
                  <h5 class="fw-bold text-dark h6 mb-3">Submit Chapter Details</h5>
                  
                  <div class="mb-3">
                    <label class="form-label small text-muted">Proposal Title <span class="text-danger">*</span></label>
                    <input type="text" name="proposal_title" class="form-control form-control-sm bg-white" required placeholder="E.g., Deep Learning Methods in Brain Image Alignment">
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label small text-muted">Abstract Outline <span class="text-danger">*</span></label>
                    <textarea name="abstract" rows="4" class="form-control form-control-sm bg-white" required placeholder="Summarize your chapter contribution (max 500 words)..."></textarea>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label small text-muted">Proposal PDF Document <span class="text-danger">*</span></label>
                    <input type="file" name="proposal_file" class="form-control form-control-sm bg-white" accept=".pdf" required>
                    <small class="text-muted d-block mt-1">Upload a comprehensive CV/Abstract in PDF format (Max 5MB).</small>
                  </div>

                  <button type="submit" name="submit_proposal" class="btn btn-success btn-sm rounded-pill px-4">
                    Send Proposal <i class="bi bi-arrow-right-short ms-1"></i>
                  </button>
                </form>
              </div>
            <?php else: ?>
              <div class="alert alert-warning py-2 px-3 small rounded-3 mb-0">
                <i class="bi bi-exclamation-circle me-1"></i>Please <a href="/login.php" class="alert-link">Sign In</a> to submit research chapter proposals.
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
      <div class="card border-0 bg-light p-4 mb-4" style="border-radius:15px;">
        <h4 class="fw-bold text-dark mb-3">Who Can Collaborate?</h4>
        <p class="small text-muted mb-3">Anniyappa Publications portal invites scholars from registered universities to contribute chapters for reference series.</p>
        <ul class="list-unstyled small text-muted mb-0">
          <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Senior Faculty Instructors</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Postdoc Researchers</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Doctoral Candidates</li>
          <li><i class="bi bi-check-circle-fill text-primary me-2"></i>Registered Industry Authors</li>
        </ul>
      </div>

      <div class="card border-0 p-4 text-white text-center" style="background: linear-gradient(135deg, #0f4c81 0%, #2563eb 100%); border-radius:15px;">
        <i class="bi bi-award fs-1 text-warning d-block mb-3"></i>
        <h4 class="fw-bold font-title">Indexing Standards</h4>
        <p class="small text-white-50 mb-0">All collaborative works are assigned Crossref DOI numbers and optimized for index listing in Scopus, Web of Science, and Google Scholar.</p>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
