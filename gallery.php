<?php
$page_title = "Gallery | Anniyappa Publications";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<!-- Page Header -->
<section class="hero-section text-center d-flex align-items-center" style="padding: 120px 0 80px; background: linear-gradient(135deg, #0f4c81 0%, #1e3a8a 100%); color: #fff;">
  <div class="container hero-content animate-up text-white">
    <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
      <i class="bi bi-images me-1"></i>Activity Log
    </span>
    <h1 class="hero-title mb-3 fs-2 text-white">Our Photo Gallery</h1>
    <p class="hero-subtitle mx-auto mb-0 text-white-50" style="max-width: 650px;">A visual record of our academic programs, technical seminars, student batch training workshops, and book launch events.</p>
  </div>
</section>

<!-- Gallery Content -->
<section class="section-padding bg-white">
  <div class="container">
    
    <!-- Gallery Filters -->
    <div class="row mb-4 justify-content-center animate-up">
      <div class="col-12">
        <div class="gallery-filter-group text-center">
          <button class="btn btn-outline-primary rounded-pill btn-sm mx-1 category-btn active" data-gallery-filter="all">All Photos</button>
          <button class="btn btn-outline-primary rounded-pill btn-sm mx-1 category-btn" data-gallery-filter="internship">Student Internships</button>
          <button class="btn btn-outline-primary rounded-pill btn-sm mx-1 category-btn" data-gallery-filter="workshop">Workshops & Seminars</button>
          <button class="btn btn-outline-primary rounded-pill btn-sm mx-1 category-btn" data-gallery-filter="event">Academic Events</button>
        </div>
      </div>
    </div>

    <!-- Gallery Grid -->
    <div class="row g-4 animate-up mt-2" style="animation-delay: 0.1s;">

      <!-- Card 1: Internship -->
      <div class="col-md-6 col-lg-4 gallery-item-col" data-gallery-cat="internship">
        <div class="gallery-card card border-0 shadow-sm overflow-hidden" style="cursor: pointer; border-radius: 15px;">
          <div class="gallery-placeholder text-center p-5 text-white" style="background: linear-gradient(135deg, #0f4c81 0%, #2563eb 100%); height: 250px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <i class="bi bi-people-fill text-warning display-4 mb-2"></i>
            <span class="fw-bold fs-5">Summer Batch 2025</span>
          </div>
          <div class="card-body p-3">
            <h5 class="fw-bold mb-1 h6">Summer Internship Batch 2025</h5>
            <p class="small text-muted mb-0">Active manuscript reviewing brainstorming sessions</p>
          </div>
        </div>
      </div>

      <!-- Card 2: Workshop -->
      <div class="col-md-6 col-lg-4 gallery-item-col" data-gallery-cat="workshop">
        <div class="gallery-card card border-0 shadow-sm overflow-hidden" style="cursor: pointer; border-radius: 15px;">
          <div class="gallery-placeholder text-center p-5 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #0d9488 100%); height: 250px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <i class="bi bi-file-earmark-code text-warning display-4 mb-2"></i>
            <span class="fw-bold fs-5">LaTeX Typesetting Lab</span>
          </div>
          <div class="card-body p-3">
            <h5 class="fw-bold mb-1 h6">LaTeX Typesetting Workshop</h5>
            <p class="small text-muted mb-0">Scholars compile their first scientific article structures</p>
          </div>
        </div>
      </div>

      <!-- Card 3: Event -->
      <div class="col-md-6 col-lg-4 gallery-item-col" data-gallery-cat="event">
        <div class="gallery-card card border-0 shadow-sm overflow-hidden" style="cursor: pointer; border-radius: 15px;">
          <div class="gallery-placeholder text-center p-5 text-white" style="background: linear-gradient(135deg, #4338ca 0%, #7c3aed 100%); height: 250px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <i class="bi bi-award text-warning display-4 mb-2"></i>
            <span class="fw-bold fs-5">Board Summit</span>
          </div>
          <div class="card-body p-3">
            <h5 class="fw-bold mb-1 h6">Annual Academic Board Meet</h5>
            <p class="small text-muted mb-0">Editorial members discuss journal indexing expansion</p>
          </div>
        </div>
      </div>

      <!-- Card 4: Event -->
      <div class="col-md-6 col-lg-4 gallery-item-col" data-gallery-cat="event">
        <div class="gallery-card card border-0 shadow-sm overflow-hidden" style="cursor: pointer; border-radius: 15px;">
          <div class="gallery-placeholder text-center p-5 text-white" style="background: linear-gradient(135deg, #b91c1c 0%, #ea580c 100%); height: 250px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <i class="bi bi-journal-check text-warning display-4 mb-2"></i>
            <span class="fw-bold fs-5">AI Book Release</span>
          </div>
          <div class="card-body p-3">
            <h5 class="fw-bold mb-1 h6">AI Book Series Launch</h5>
            <p class="small text-muted mb-0">Unveiling of the Generative AI handbook series</p>
          </div>
        </div>
      </div>

      <!-- Card 5: Internship -->
      <div class="col-md-6 col-lg-4 gallery-item-col" data-gallery-cat="internship">
        <div class="gallery-card card border-0 shadow-sm overflow-hidden" style="cursor: pointer; border-radius: 15px;">
          <div class="gallery-placeholder text-center p-5 text-white" style="background: linear-gradient(135deg, #15803d 0%, #166534 100%); height: 250px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <i class="bi bi-code-slash text-warning display-4 mb-2"></i>
            <span class="fw-bold fs-5">Editorial Coding Lab</span>
          </div>
          <div class="card-body p-3">
            <h5 class="fw-bold mb-1 h6">Editorial Coding Lab</h5>
            <p class="small text-muted mb-0">Interns compiling XML tools for digital bookshelf modules</p>
          </div>
        </div>
      </div>

      <!-- Card 6: Workshop -->
      <div class="col-md-6 col-lg-4 gallery-item-col" data-gallery-cat="workshop">
        <div class="gallery-card card border-0 shadow-sm overflow-hidden" style="cursor: pointer; border-radius: 15px;">
          <div class="gallery-placeholder text-center p-5 text-white" style="background: linear-gradient(135deg, #0f766e 0%, #111827 100%); height: 250px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <i class="bi bi-shield-check text-warning display-4 mb-2"></i>
            <span class="fw-bold fs-5">Peer Review Meet</span>
          </div>
          <div class="card-body p-3">
            <h5 class="fw-bold mb-1 h6">Peer Review Training</h5>
            <p class="small text-muted mb-0">Peer reviewers reviewing plagiarism report parameters</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
