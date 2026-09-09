<?php
$page_title = "Upcoming Books | Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<!-- Page Header -->
<section class="hero-section text-center d-flex align-items-center" style="padding: 120px 0 80px; background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%); color: #fff;">
  <div class="container hero-content animate-up text-white">
    <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
      <i class="bi bi-rocket-takeoff me-1"></i>Books Under Production
    </span>
    <h1 class="hero-title mb-3 fs-2 text-white">Upcoming Publications</h1>
    <p class="hero-subtitle mx-auto mb-0 text-white-50" style="max-width: 650px;">A sneak peek into our academic pipeline. Check out upcoming textbooks and references slated for release in the coming months.</p>
  </div>
</section>

<!-- Upcoming Books Content -->
<section class="section-padding bg-white">
  <div class="container">
    <div class="row g-4 justify-content-center">

      <!-- Card 1: AI -->
      <div class="col-lg-4 col-md-6 animate-up">
        <div class="coming-soon-card card border-0 shadow-sm overflow-hidden h-100" style="border-radius:15px;">
          <div class="coming-soon-cover text-white p-4" style="background: linear-gradient(135deg, #7c3aed, #db2777); height: 220px; display:flex; flex-direction:column; justify-content:space-between;">
            <span class="small text-white-50">Academic Reference</span>
            <h3 class="font-title fs-4 mb-2 text-white">Deep Learning:<br>Foundations & Apps</h3>
            <span class="small">Dr. R. Anniyappa</span>
          </div>
          <div class="coming-soon-body p-4">
            <span class="badge bg-warning text-dark mb-2">
              <i class="bi bi-calendar-event me-1"></i> Slated Release: Sept 2026
            </span>
            <h4 class="fw-bold h5 mb-2 text-dark">Deep Learning Systems</h4>
            <p class="small text-muted mb-0">A rigorous mathematical and algorithmic exploration of deep neural network architectures, transformers, and large generative models.</p>
          </div>
        </div>
      </div>

      <!-- Card 2: IoT -->
      <div class="col-lg-4 col-md-6 animate-up" style="animation-delay: 0.1s;">
        <div class="coming-soon-card card border-0 shadow-sm overflow-hidden h-100" style="border-radius:15px;">
          <div class="coming-soon-cover text-white p-4" style="background: linear-gradient(135deg, #15803d, #166534); height: 220px; display:flex; flex-direction:column; justify-content:space-between;">
            <span class="small text-white-50">Engineering Guide</span>
            <h3 class="font-title fs-4 mb-2 text-white">Edge IoT Systems &<br>Computation</h3>
            <span class="small">Prof. A. K. Sen</span>
          </div>
          <div class="coming-soon-body p-4">
            <span class="badge bg-warning text-dark mb-2">
              <i class="bi bi-calendar-event me-1"></i> Slated Release: Oct 2026
            </span>
            <h4 class="fw-bold h5 mb-2 text-dark">Edge Computing in IoT</h4>
            <p class="small text-muted mb-0">Focuses on deploying lightweight models, handling MQTT/CoAP architectures, and building gateway processing pipelines.</p>
          </div>
        </div>
      </div>

      <!-- Card 3: Web Tech -->
      <div class="col-lg-4 col-md-6 animate-up" style="animation-delay: 0.2s;">
        <div class="coming-soon-card card border-0 shadow-sm overflow-hidden h-100" style="border-radius:15px;">
          <div class="coming-soon-cover text-white p-4" style="background: linear-gradient(135deg, #ea580c, #b45309); height: 220px; display:flex; flex-direction:column; justify-content:space-between;">
            <span class="small text-white-50">Course Textbook</span>
            <h3 class="font-title fs-4 mb-2 text-white">Next-Gen Web<br>Architectures</h3>
            <span class="small">Prof. S. Kumar</span>
          </div>
          <div class="coming-soon-body p-4">
            <span class="badge bg-warning text-dark mb-2">
              <i class="bi bi-calendar-event me-1"></i> Slated Release: Nov 2026
            </span>
            <h4 class="fw-bold h5 mb-2 text-dark">Modern Web Design</h4>
            <p class="small text-muted mb-0">Covers serverless backends, edge-rendering architectures, WebAssembly scripts, and highly scalable reactive layouts.</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Author Call-to-Action Section -->
<section class="section-padding bg-light border-top">
  <div class="container text-center">
    <div class="row justify-content-center">
      <div class="col-lg-8 animate-up">
        <i class="bi bi-people-fill text-primary display-4 mb-3 d-block"></i>
        <h2 class="font-title mb-3">Call for Authors & Research Editors</h2>
        <p class="lead text-muted mb-4">Are you working on an academic manuscript, handbook, or textbook proposal? Partner with Anniyappa Publications to reach classrooms and laboratories worldwide.</p>
        <p class="text-muted mb-5">We offer professional typesetting, DOI indexing, layout review, and Global Academic distribution services. We welcome submissions in Computer Science, Machine Learning, Data Analytics, Cybersecurity, and Information Systems.</p>
        <div class="d-inline-flex flex-wrap gap-3 justify-content-center">
          <a href="/contact.php" class="btn btn-primary-custom rounded-pill px-4 py-3 fw-bold">
            <i class="bi bi-journal-arrow-up me-2"></i>Submit Proposal
          </a>
          <a href="/about.php" class="btn btn-outline-custom rounded-pill px-4 py-3">
            <i class="bi bi-info-circle me-2"></i>Our Contributions
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
