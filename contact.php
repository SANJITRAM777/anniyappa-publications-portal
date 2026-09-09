<?php
$page_title = "Contact Us | Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_msg = "Please fill in all the fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            $success_msg = "Your inquiry has been submitted successfully! Our editorial coordinators will get back to you shortly.";
            // Clear post fields
            $name = $email = $subject = $message = '';
        } catch (PDOException $e) {
            $error_msg = "Failed to submit inquiry. Please try again. Error: " . $e->getMessage();
        }
    }
}
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<!-- Page Header -->
<section class="hero-section text-center d-flex align-items-center" style="padding: 120px 0 80px; background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%); color: #fff;">
  <div class="container hero-content animate-up text-white">
    <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
      <i class="bi bi-envelope me-1"></i>Connect With Us
    </span>
    <h1 class="hero-title mb-3 fs-2 text-white">Contact Our Editorial Office</h1>
    <p class="hero-subtitle mx-auto mb-0 text-white-50" style="max-width: 650px;">Connect with our editorial support team for manuscript reviews, training details, bookshelf inquiries, or organization support.</p>
  </div>
</section>

<!-- Contact Body Section -->
<section class="section-padding bg-white">
  <div class="container">
    <div class="row g-4 mb-5">
      
      <!-- Left Side: Contact details cards -->
      <div class="col-lg-5 animate-up">
        <div class="contact-info-box p-4 border rounded shadow-sm bg-light">
          <h3 class="font-title mb-4">Office Information</h3>
          <p class="mb-4">Feel free to visit our editorial headquarters during business hours or reach out directly via our query desks.</p>
          
          <!-- Contact Row 1: Address -->
          <div class="d-flex mb-4">
            <div class="fs-4 text-primary me-3"><i class="bi bi-geo-alt-fill"></i></div>
            <div>
              <h5 class="fw-bold mb-1">Our Office</h5>
              <p class="text-muted small mb-0">No. 45, Second Floor, Academic Avenue, Knowledge Park, Bangalore - 560001, India</p>
            </div>
          </div>
          
          <!-- Contact Row 2: Phone -->
          <div class="d-flex mb-4">
            <div class="fs-4 text-primary me-3"><i class="bi bi-telephone-fill"></i></div>
            <div>
              <h5 class="fw-bold mb-1">Phone Numbers</h5>
              <p class="text-muted small mb-0"><strong>Help Desk:</strong> +91 80 4912 3456<br><strong>Editorial:</strong> +91 80 4912 7890</p>
            </div>
          </div>
          
          <!-- Contact Row 3: Email -->
          <div class="d-flex mb-4">
            <div class="fs-4 text-primary me-3"><i class="bi bi-envelope-fill"></i></div>
            <div>
              <h5 class="fw-bold mb-1">Support Emails</h5>
              <p class="text-muted small mb-0"><strong>General:</strong> contact@anniyappapublications.com<br><strong>Editor:</strong> editor@anniyappapublications.com</p>
            </div>
          </div>

          <!-- Contact Row 4: Working Hours -->
          <div class="d-flex">
            <div class="fs-4 text-primary me-3"><i class="bi bi-clock-fill"></i></div>
            <div>
              <h5 class="fw-bold mb-1">Office Hours</h5>
              <p class="text-muted small mb-0">Monday - Saturday: 09:00 AM - 06:00 PM (IST)</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Side: Contact Form -->
      <div class="col-lg-7 animate-up" style="animation-delay: 0.1s;">
        <div class="custom-form-card shadow-sm p-4 p-lg-5 border rounded bg-white">
          <h3 class="font-title mb-3">Send a Message</h3>
          <p class="text-muted mb-4">Please fill out the contact form below. Our academic coordinators will respond within 24 business hours.</p>
          
          <?php if (!empty($success_msg)): ?>
            <?php echo get_alert($success_msg, 'success'); ?>
          <?php endif; ?>

          <?php if (!empty($error_msg)): ?>
            <?php echo get_alert($error_msg, 'danger'); ?>
          <?php endif; ?>

          <form action="/contact.php" method="POST">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="conName" class="form-label">Full Name</label>
                <input type="text" class="form-control bg-light" id="conName" name="name" placeholder="e.g., Alice Green" required value="<?php echo isset($name) ? sanitize($name) : ''; ?>">
              </div>
              <div class="col-md-6">
                <label for="conEmail" class="form-label">Email Address</label>
                <input type="email" class="form-control bg-light" id="conEmail" name="email" placeholder="alice.green@example.com" required value="<?php echo isset($email) ? sanitize($email) : ''; ?>">
              </div>
              <div class="col-12">
                <label for="conSubject" class="form-label">Subject</label>
                <input type="text" class="form-control bg-light" id="conSubject" name="subject" placeholder="e.g., Manuscript Submission Help" required value="<?php echo isset($subject) ? sanitize($subject) : ''; ?>">
              </div>
              <div class="col-12">
                <label for="conMessage" class="form-label">Message Content</label>
                <textarea class="form-control bg-light" id="conMessage" name="message" rows="5" placeholder="Enter your detailed query here..." required><?php echo isset($message) ? sanitize($message) : ''; ?></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary-custom rounded-pill w-100 py-3 fw-bold">
                  Send Message <i class="bi bi-send-fill ms-2"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
