<?php
$page_title = "Sign Up - Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = get_logged_in_role();
    if ($role === 'Admin') {
        header("Location: /admin/dashboard.php");
    } elseif ($role === 'Faculty' || $role === 'Author') {
        header("Location: /faculty/dashboard.php");
    } else {
        header("Location: /student/dashboard.php");
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 3); // Default to Student (id=3)
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($email) || empty($full_name) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!in_array($role_id, [2, 3, 4])) { // Only allow registering Faculty, Student, Author (Admins created via DB/Seeder)
        $error = "Invalid registration role.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "An account with this email already exists.";
        } else {
            // Start transaction
            $pdo->beginTransaction();
            try {
                // Insert User
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $insertUser = $pdo->prepare("INSERT INTO users (email, password, role_id) VALUES (?, ?, ?)");
                $insertUser->execute([$email, $hashed_password, $role_id]);
                $user_id = $pdo->lastInsertId();

                // Insert Profile
                $insertProfile = $pdo->prepare("INSERT INTO user_profiles (user_id, full_name, phone, bio, profile_pic) VALUES (?, ?, ?, ?, 'default_avatar.png')");
                $role_name = ($role_id == 2) ? 'Faculty member' : (($role_id == 4) ? 'Author contributor' : 'Student enrolled');
                $insertProfile->execute([$user_id, $full_name, $phone, "New $role_name on Anniyappa Publications."]);

                // Special role mappings
                if ($role_id == 4) { // Author
                    $insertAuthor = $pdo->prepare("INSERT INTO authors (user_id, name, bio) VALUES (?, ?, ?)");
                    $insertAuthor->execute([$user_id, $full_name, "Registered portal author."]);
                }

                $pdo->commit();
                $success = "Registration successful! You can now sign in.";
                
                // Automatically log them in
                if (attempt_login($email, $password, $pdo)) {
                    if ($role_id == 2 || $role_id == 4) {
                        header("Location: /faculty/dashboard.php");
                    } else {
                        header("Location: /student/dashboard.php");
                    }
                    exit;
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Registration failed. Please try again. Details: " . $e->getMessage();
            }
        }
    }
}
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<section class="section-padding bg-light d-flex align-items-center" style="min-height: 80vh;">
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
          <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #0f4c81 0%, #2563eb 100%);">
            <i class="bi bi-person-plus-fill fs-1"></i>
            <h2 class="font-title mt-2 mb-0">Create Account</h2>
            <p class="text-white-50 small mb-0">Join the Anniyappa Publications portal today</p>
          </div>
          <div class="card-body p-4 p-lg-5 bg-white">
            
            <?php if (!empty($error)): ?>
              <?php echo get_alert($error, 'danger'); ?>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
              <?php echo get_alert($success, 'success'); ?>
            <?php endif; ?>

            <form action="/register.php" method="POST">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control bg-light" id="full_name" name="full_name" required placeholder="E.g. Rajesh Kumar" value="<?php echo isset($full_name) ? sanitize($full_name) : ''; ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="role_id" class="form-label">Account Role <span class="text-danger">*</span></label>
                  <select class="form-select bg-light" id="role_id" name="role_id" required>
                    <option value="3" <?php echo (isset($role_id) && $role_id == 3) ? 'selected' : ''; ?>>Student / Learner</option>
                    <option value="2" <?php echo (isset($role_id) && $role_id == 2) ? 'selected' : ''; ?>>Faculty / Instructor</option>
                    <option value="4" <?php echo (isset($role_id) && $role_id == 4) ? 'selected' : ''; ?>>Author / Researcher</option>
                  </select>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                  <input type="email" class="form-control bg-light" id="email" name="email" required placeholder="name@domain.com" value="<?php echo isset($email) ? sanitize($email) : ''; ?>">
                </div>

                <div class="col-md-6 mb-3">
                  <label for="phone" class="form-label">Phone Number</label>
                  <input type="tel" class="form-control bg-light" id="phone" name="phone" placeholder="E.g. +91 9988776655" value="<?php echo isset($phone) ? sanitize($phone) : ''; ?>">
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                  <input type="password" class="form-control bg-light" id="password" name="password" required placeholder="At least 6 chars">
                </div>

                <div class="col-md-6 mb-4">
                  <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                  <input type="password" class="form-control bg-light" id="confirm_password" name="confirm_password" required placeholder="Retype password">
                </div>
              </div>

              <button type="submit" class="btn btn-primary-custom w-100 rounded-pill py-2.5">
                <i class="bi bi-check-circle me-2"></i>Register Now
              </button>
            </form>

            <div class="text-center mt-4 pt-2 border-top">
              <p class="mb-0 text-muted small">Already have an account? <a href="/login.php" class="text-primary fw-semibold text-decoration-none">Sign In</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
