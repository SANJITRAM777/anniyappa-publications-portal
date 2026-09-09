<?php
$page_title = "Manage Faculty Profile - Anniyappa Publications";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(['Faculty', 'Author']);

$user_id = get_logged_in_user_id();
$success = '';
$error = '';

// Fetch profile
$profStmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$profStmt->execute([$user_id]);
$profile = $profStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (empty($full_name)) {
        $error = "Full Name is required.";
    } else {
        $pic_filename = $profile['profile_pic'] ?? 'default_avatar.png';
        
        // Handle avatar upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $new_filename = 'avatar_fac_' . $user_id . '_' . time() . '.' . $ext;
                $dest = __DIR__ . '/../uploads/profile_pics/' . $new_filename;
                
                if (!is_dir(__DIR__ . '/../uploads/profile_pics/')) {
                    mkdir(__DIR__ . '/../uploads/profile_pics/', 0777, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $pic_filename = $new_filename;
                    $_SESSION['user_pic'] = $new_filename;
                }
            } else {
                $error = "Only PNG, JPG, and JPEG avatars are allowed.";
            }
        }
        
        if (empty($error)) {
            try {
                $upStmt = $pdo->prepare("
                    UPDATE user_profiles 
                    SET full_name = ?, phone = ?, address = ?, bio = ?, profile_pic = ? 
                    WHERE user_id = ?
                ");
                $upStmt->execute([$full_name, $phone, $address, $bio, $pic_filename, $user_id]);
                
                $_SESSION['user_name'] = $full_name;
                
                // If author, sync name in authors table as well
                if (has_role('Author')) {
                    $upAuthor = $pdo->prepare("UPDATE authors SET name = ?, bio = ?, profile_pic = ? WHERE user_id = ?");
                    $upAuthor->execute([$full_name, $bio, $pic_filename, $user_id]);
                }
                
                $success = "Profile details updated successfully!";
                
                // Refresh profile data
                $profStmt->execute([$user_id]);
                $profile = $profStmt->fetch();
            } catch (PDOException $e) {
                $error = "Failed to update profile: " . $e->getMessage();
            }
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
          <a href="/faculty/manage_courses.php" class="list-group-item list-group-item-action"><i class="bi bi-laptop me-2"></i>Manage Courses</a>
          <a href="/faculty/grade_assignments.php" class="list-group-item list-group-item-action"><i class="bi bi-journal-check me-2"></i>Grade Assignments</a>
          <a href="/faculty/research.php" class="list-group-item list-group-item-action"><i class="bi bi-mortarboard me-2"></i>Research Projects</a>
          <a href="/faculty/profile.php" class="list-group-item list-group-item-action active text-white bg-primary"><i class="bi bi-person me-2"></i>Edit Profile</a>
        </div>
      </div>
    </div>

    <!-- Main Content Panel -->
    <div class="col-lg-9">
      <h2 class="font-title fw-bold text-dark mb-4 h3"><i class="bi bi-person-workspace me-2 text-primary"></i>Profile Settings</h2>
      
      <div class="card border-0 shadow-sm p-4 p-lg-5" style="border-radius:15px;">
        
        <?php if (!empty($success)): ?>
          <?php echo get_alert($success, 'success'); ?>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
          <?php echo get_alert($error, 'danger'); ?>
        <?php endif; ?>

        <form action="/faculty/profile.php" method="POST" enctype="multipart/form-data">
          
          <div class="row mb-4 align-items-center">
            <div class="col-md-3 text-center text-md-start">
              <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold fs-2 shadow-sm" style="width:100px; height:100px;">
                <?php echo substr($profile['full_name'] ?? 'F', 0, 1); ?>
              </div>
            </div>
            <div class="col-md-9 mt-3 mt-md-0">
              <label for="profile_pic" class="form-label small text-muted">Upload Avatar Photo (PNG/JPG)</label>
              <input type="file" name="profile_pic" id="profile_pic" class="form-control bg-light" accept=".png,.jpg,.jpeg">
            </div>
          </div>
          
          <hr class="my-4">
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="fullName" class="form-label small text-muted">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="full_name" id="fullName" class="form-control bg-light" required placeholder="Alice Green" value="<?php echo sanitize($profile['full_name']); ?>">
            </div>
            
            <div class="col-md-6 mb-3">
              <label for="phone" class="form-label small text-muted">Contact Phone</label>
              <input type="tel" name="phone" id="phone" class="form-control bg-light" placeholder="+91 ..." value="<?php echo sanitize($profile['phone']); ?>">
            </div>
          </div>

          <div class="mb-3">
            <label for="address" class="form-label small text-muted">Office / Department Address</label>
            <textarea name="address" id="address" rows="3" class="form-control bg-light" placeholder="Enter department office room number, building address..."><?php echo sanitize($profile['address']); ?></textarea>
          </div>

          <div class="mb-4">
            <label for="bio" class="form-label small text-muted">Biography & Editorial Bio</label>
            <textarea name="bio" id="bio" rows="4" class="form-control bg-light" placeholder="Describe your publications list, indexing achievements, teaching experience, etc..."><?php echo sanitize($profile['bio']); ?></textarea>
          </div>

          <button type="submit" name="save_profile" class="btn btn-primary-custom rounded-pill px-5 py-2.5 fw-bold">
            <i class="bi bi-shield-check me-2"></i>Save Settings
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
