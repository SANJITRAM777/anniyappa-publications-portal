<?php
$page_title = "Sign In - Anniyappa Publications";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        if (attempt_login($email, $password, $pdo)) {
            // Check if redirect is set
            if (isset($_SESSION['login_redirect'])) {
                $redirect = $_SESSION['login_redirect'];
                unset($_SESSION['login_redirect']);
                header("Location: " . $redirect);
            } else {
                // Role based redirect
                $role = get_logged_in_role();
                if ($role === 'Admin') {
                    header("Location: /admin/dashboard.php");
                } elseif ($role === 'Faculty' || $role === 'Author') {
                    header("Location: /faculty/dashboard.php");
                } else {
                    header("Location: /student/dashboard.php");
                }
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<section class="section-padding bg-light d-flex align-items-center" style="min-height: 80vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
          <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #0f4c81 0%, #2563eb 100%);">
            <i class="bi bi-journal-bookmark-fill fs-1"></i>
            <h2 class="font-title mt-2 mb-0">Welcome Back</h2>
            <p class="text-white-50 small mb-0">Sign in to access your dashboard</p>
          </div>
          <div class="card-body p-4 p-lg-5 bg-white">
            
            <?php if (!empty($error)): ?>
              <?php echo get_alert($error, 'danger'); ?>
            <?php endif; ?>

            <form action="/login.php" method="POST">
              <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                  <input type="email" class="form-control bg-light border-start-0 ps-0" id="email" name="email" required placeholder="name@example.com" value="<?php echo isset($email) ? sanitize($email) : ''; ?>">
                </div>
              </div>

              <div class="mb-4">
                <div class="d-flex justify-content-between mb-1">
                  <label for="password" class="form-label mb-0">Password</label>
                  <a href="#" class="text-primary small text-decoration-none" onclick="alert('Password reset link has been simulated to your email.');">Forgot Password?</a>
                </div>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                  <input type="password" class="form-control bg-light border-start-0 ps-0" id="password" name="password" required placeholder="Enter password">
                </div>
              </div>

              <button type="submit" class="btn btn-primary-custom w-100 rounded-pill py-2.5">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
              </button>
            </form>

            <div class="text-center mt-4 pt-2 border-top">
              <p class="mb-0 text-muted small">Don't have an account? <a href="/register.php" class="text-primary fw-semibold text-decoration-none">Sign Up</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
