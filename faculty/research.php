<?php
$page_title = "Research Collaboration - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(['Faculty', 'Author']);

$instructor_id = get_logged_in_user_id();
$success = '';
$error = '';

// Handle creating research project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title) || empty($description)) {
        $error = "Project title and description are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO research_projects (title, description, faculty_id, status) VALUES (?, ?, ?, 'Open')");
            $stmt->execute([$title, $description, $instructor_id]);
            $success = "Research collaboration project opened successfully!";
        } catch (PDOException $e) {
            $error = "Failed to create project: " . $e->getMessage();
        }
    }
}

// Handle updating proposal status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_proposal_status'])) {
    $proposal_id = (int)($_POST['proposal_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($proposal_id <= 0 || empty($status)) {
        $error = "Proposal ID and status are required.";
    } else {
        try {
            // Verify ownership
            $checkStmt = $pdo->prepare("
                SELECT p.id 
                FROM proposals p
                JOIN research_projects rp ON p.project_id = rp.id
                WHERE p.id = ? AND rp.faculty_id = ?
            ");
            $checkStmt->execute([$proposal_id, $instructor_id]);
            
            if ($checkStmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE proposals SET status = ? WHERE id = ?");
                $stmt->execute([$status, $proposal_id]);
                
                // If proposal accepted, also issue a certificate of collaboration if needed
                // For simplicity, we just save the status.
                $success = "Proposal status updated to '$status' successfully!";
            } else {
                $error = "Unauthorized action. You are not the lead of this project.";
            }
        } catch (PDOException $e) {
            $error = "Failed to update status: " . $e->getMessage();
        }
    }
}

// Fetch faculty research projects
$projectsStmt = $pdo->prepare("SELECT * FROM research_projects WHERE faculty_id = ? ORDER BY created_at DESC");
$projectsStmt->execute([$instructor_id]);
$my_projects = $projectsStmt->fetchAll();

// Fetch scholar chapter proposals
$proposalsStmt = $pdo->prepare("
    SELECT p.*, rp.title AS project_title, prof.full_name AS author_name, u.email AS author_email
    FROM proposals p
    JOIN research_projects rp ON p.project_id = rp.id
    JOIN user_profiles prof ON p.author_id = prof.user_id
    JOIN users u ON p.author_id = u.id
    WHERE rp.faculty_id = ?
    ORDER BY p.submitted_at DESC
");
$proposalsStmt->execute([$instructor_id]);
$scholar_proposals = $proposalsStmt->fetchAll();
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
          <a href="/faculty/grade_assignments.php" class="list-group-item list-group-item-action"><i class="bi bi-journal-check me-2"></i>Grade Assignments</a>
          <a href="/faculty/research.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-mortarboard me-2"></i>Research Projects</a>
          <a href="/faculty/profile.php" class="list-group-item list-group-item-action"><i class="bi bi-person me-2"></i>Edit Profile</a>
        </div>
      </div>
    </div>

    <!-- Main Content Panel -->
    <div class="col-lg-9">
      <h2 class="font-title fw-bold text-dark mb-4 h3">Research Project Manager</h2>
      
      <?php if (!empty($success)): ?>
        <?php echo get_alert($success, 'success'); ?>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <?php echo get_alert($error, 'danger'); ?>
      <?php endif; ?>

      <div class="row g-4">
        <!-- Projects List & New Call Form -->
        <div class="col-md-6">
          <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius:15px;">
            <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Create Collaborative Call</h3>
            <form action="/faculty/research.php" method="POST">
              <div class="mb-3">
                <label for="pTitle" class="form-label small text-muted">Project Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="pTitle" class="form-control bg-light" required placeholder="E.g., Quantum Cryptographic Signatures in IoT">
              </div>
              <div class="mb-3">
                <label for="pDesc" class="form-label small text-muted">Project Scope & Guidelines <span class="text-danger">*</span></label>
                <textarea name="description" id="pDesc" rows="5" class="form-control bg-light" required placeholder="Outline the research scope, chapter proposals deadline, etc..."></textarea>
              </div>
              <button type="submit" name="create_project" class="btn btn-primary rounded-pill px-4 btn-sm">Open Project Call</button>
            </form>
          </div>

          <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius:15px;">
            <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">My Open Projects</h3>
            <?php if (empty($my_projects)): ?>
              <p class="text-muted small mb-0">No research project calls opened yet.</p>
            <?php else: ?>
              <div class="list-group list-group-flush mb-0 small">
                <?php foreach ($my_projects as $proj): ?>
                  <div class="list-group-item px-0 bg-transparent py-2">
                    <strong class="text-dark d-block"><?php echo sanitize($proj['title']); ?></strong>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill mt-1" style="font-size:0.65rem;"><?php echo sanitize($proj['status']); ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Proposals List -->
        <div class="col-md-6">
          <div class="card border-0 shadow-sm p-4 bg-white h-100" style="border-radius:15px;">
            <h3 class="fw-bold text-dark h5 mb-3 border-bottom pb-2">Chapter Proposals Received</h3>
            
            <?php if (empty($scholar_proposals)): ?>
              <p class="text-muted small mb-0">No proposals submitted for your projects yet.</p>
            <?php else: ?>
              <?php foreach ($scholar_proposals as $prop): 
                $status_color = 'bg-warning text-dark';
                if ($prop['status'] === 'Accepted') $status_color = 'bg-success text-white';
                if ($prop['status'] === 'Rejected') $status_color = 'bg-danger text-white';
              ?>
                <div class="border rounded-3 p-3 bg-light mb-3">
                  <div class="d-flex justify-content-between mb-1">
                    <strong class="text-dark small d-block"><?php echo sanitize($prop['proposal_title']); ?></strong>
                    <span class="badge rounded-pill <?php echo $status_color; ?> px-2 py-1" style="font-size: 0.6rem; align-self: flex-start;"><?php echo sanitize($prop['status']); ?></span>
                  </div>
                  <small class="text-muted d-block" style="font-size:0.75rem;">Lead: <?php echo sanitize($prop['project_title']); ?></small>
                  <small class="text-muted d-block" style="font-size:0.75rem;">Submitted by: <strong><?php echo sanitize($prop['author_name']); ?></strong> (<?php echo sanitize($prop['author_email']); ?>)</small>
                  <p class="text-muted mt-2 small" style="font-size:0.75rem;"><?php echo sanitize($prop['abstract']); ?></p>
                  
                  <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                    <a href="/download.php?type=proposal&id=<?php echo $prop['id']; ?>" class="btn btn-outline-secondary btn-sm px-2.5 rounded-pill font-monospace" style="font-size: 0.7rem;" target="_blank"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                    
                    <form action="/faculty/research.php" method="POST" class="d-flex align-items-center gap-1">
                      <input type="hidden" name="proposal_id" value="<?php echo $prop['id']; ?>">
                      <select name="status" class="form-select form-select-sm bg-white py-1" style="width: 100px; font-size: 0.7rem; border-radius: 5px;" required>
                        <option value="Pending" <?php echo $prop['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Under_Review" <?php echo $prop['status'] === 'Under_Review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="Accepted" <?php echo $prop['status'] === 'Accepted' ? 'selected' : ''; ?>>Accept</option>
                        <option value="Rejected" <?php echo $prop['status'] === 'Rejected' ? 'selected' : ''; ?>>Reject</option>
                      </select>
                      <button type="submit" name="update_proposal_status" class="btn btn-primary btn-sm rounded-3 py-1 px-2"><i class="bi bi-check-lg" style="font-size: 0.7rem;"></i></button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
