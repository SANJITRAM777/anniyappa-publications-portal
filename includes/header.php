<?php
// Include core dependencies
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Calculate cart count dynamically if logged in
$cart_count = 0;
if (is_logged_in() && isset($pdo)) {
    $cartStmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $cartStmt->execute([get_logged_in_user_id()]);
    $cart_res = $cartStmt->fetch();
    $cart_count = (int)($cart_res['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title ?? 'Anniyappa Publications | Academic Publishing & Educational Services'; ?></title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
  
  <!-- Google Fonts Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="/assets/css/style.css">
  
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg fixed-top shadow-sm" id="mainNavbar">
    <div class="container">
      <a class="navbar-brand" href="/index.php">
        <i class="bi bi-journal-bookmark-fill me-2 text-primary"></i>Anniyappa <span style="color: var(--accent-color);">Publications</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item"><a class="nav-link" href="/index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="/about.php">About</a></li>
          <li class="nav-item"><a class="nav-link" href="/bookshelf.php">Bookshelf</a></li>
          <li class="nav-item"><a class="nav-link" href="/upcoming.php">Upcoming</a></li>
          <li class="nav-item"><a class="nav-link" href="/internship.php">Internships</a></li>
          <li class="nav-item"><a class="nav-link" href="/research.php">Research</a></li>
          <li class="nav-item"><a class="nav-link" href="/events.php">Events</a></li>
          <li class="nav-item"><a class="nav-link" href="/blog.php">Blog</a></li>
          <li class="nav-item"><a class="nav-link" href="/gallery.php">Gallery</a></li>
          <li class="nav-item"><a class="nav-link" href="/contact.php">Contact</a></li>

          <!-- Cart Icon -->
          <li class="nav-item mx-lg-2">
            <a class="nav-link position-relative py-2" href="/cart.php" aria-label="Cart">
              <i class="bi bi-cart3 fs-5"></i>
              <?php if ($cart_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                  <?php echo $cart_count; ?>
                </span>
              <?php endif; ?>
            </a>
          </li>

          <!-- User Authentication Dropdown -->
          <?php if (is_logged_in()): ?>
            <li class="nav-item dropdown ms-lg-2">
              <a class="nav-link dropdown-toggle btn btn-outline-primary btn-sm rounded-pill text-dark px-3 py-1 mt-2 mt-lg-0" href="#" id="authDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1 text-primary"></i><?php echo sanitize($_SESSION['user_name']); ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="authDropdown" style="border-radius: 12px;">
                <li>
                  <div class="dropdown-header text-muted">
                    Signed in as <br><strong class="text-dark"><?php echo sanitize($_SESSION['user_role']); ?></strong>
                  </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <?php if (has_role('Admin')): ?>
                  <li><a class="dropdown-item" href="/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                <?php elseif (has_role('Faculty')): ?>
                  <li><a class="dropdown-item" href="/faculty/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Faculty Dashboard</a></li>
                <?php elseif (has_role('Student')): ?>
                  <li><a class="dropdown-item" href="/student/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Student Dashboard</a></li>
                <?php elseif (has_role('Author')): ?>
                  <li><a class="dropdown-item" href="/faculty/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Author Dashboard</a></li>
                <?php endif; ?>
                
                <?php if (has_role('Student')): ?>
                  <li><a class="dropdown-item" href="/student/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <?php elseif (has_role('Faculty') || has_role('Author')): ?>
                  <li><a class="dropdown-item" href="/faculty/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <?php endif; ?>
                
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item ms-lg-2">
              <a class="btn btn-primary-custom btn-sm rounded-pill px-4 mt-2 mt-lg-0" href="/login.php">
                <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Content Padding for fixed Navbar -->
  <div style="padding-top: 76px;"></div>
