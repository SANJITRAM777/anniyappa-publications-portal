<?php
$page_title = "Anniyappa Publications | Academic Publishing & Educational Services";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch metrics
$total_books_query = $pdo->query("SELECT COUNT(*) FROM books");
$total_books = $total_books_query->fetchColumn();

$total_courses_query = $pdo->query("SELECT COUNT(*) FROM courses");
$total_courses = $total_courses_query->fetchColumn();

// Fetch featured books
$featuredStmt = $pdo->query("
    SELECT b.*, GROUP_CONCAT(a.name SEPARATOR ', ') AS author_names 
    FROM books b
    LEFT JOIN book_authors ba ON b.id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.id
    WHERE b.is_featured = TRUE
    GROUP BY b.id
");
$featured_books = $featuredStmt->fetchAll();
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section d-flex align-items-center" id="heroSection">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 hero-content animate-up">
        <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
          <i class="bi bi-stars me-1"></i>Empowering Education & Research
        </span>
        <h1 class="hero-title">Shaping the Future of <span class="gradient-text" style="-webkit-text-fill-color: #fbbf24;">Knowledge</span></h1>
        <p class="hero-subtitle">
          Anniyappa Publications is a leading academic publishing house dedicated to producing quality research articles, scientific books, and curriculum materials — while cultivating future talent through internships and technical training.
        </p>
        <div class="hero-btn-group">
          <a href="/bookshelf.php" class="btn btn-primary-custom rounded-pill">
            <i class="bi bi-book me-2"></i>Explore Bookshelf
          </a>
          <a href="/contact.php" class="btn btn-outline-custom rounded-pill">
            <i class="bi bi-envelope me-2"></i>Contact Us
          </a>
        </div>
      </div>
      <div class="col-lg-6 hero-image-container d-none d-lg-flex animate-up" style="animation-delay: 0.2s;">
        <svg viewBox="0 0 500 450" width="100%" height="auto" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="circleGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.4"/>
              <stop offset="100%" stop-color="#0f4c81" stop-opacity="0.1"/>
            </linearGradient>
            <linearGradient id="bookGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#3b82f6"/>
              <stop offset="100%" stop-color="#0f4c81"/>
            </linearGradient>
          </defs>
          <circle cx="250" cy="225" r="180" fill="url(#circleGrad)" />
          <path d="M120 320 L280 250 L380 290 L220 360 Z" fill="#1e293b" />
          <path d="M220 360 L380 290 L380 310 L220 380 Z" fill="#0f172a" />
          <path d="M120 320 L220 360 L220 380 L120 340 Z" fill="#3b82f6" />
          <path d="M140 280 L300 210 L400 250 L240 320 Z" fill="url(#bookGrad)" />
          <path d="M240 320 L400 250 L400 270 L240 340 Z" fill="#0a3459" />
          <path d="M140 280 L240 320 L240 340 L140 300 Z" fill="#60a5fa" />
          <path d="M160 240 L320 170 L420 210 L260 280 Z" fill="#f8fafc" />
          <path d="M260 280 L420 210 L420 225 L260 295 Z" fill="#cbd5e1" />
          <path d="M160 240 L260 280 L260 295 L160 255 Z" fill="#0284c7" />
          <rect x="250" y="80" width="100" height="130" rx="8" fill="#ffffff" filter="drop-shadow(0px 8px 16px rgba(0,0,0,0.1))" transform="rotate(-15 300 145)"/>
          <line x1="270" y1="120" x2="330" y2="105" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" transform="rotate(-15 300 145)"/>
          <line x1="270" y1="140" x2="320" y2="128" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" transform="rotate(-15 300 145)"/>
          <line x1="270" y1="160" x2="340" y2="142" stroke="#3b82f6" stroke-width="4" stroke-linecap="round" transform="rotate(-15 300 145)"/>
          <line x1="270" y1="180" x2="310" y2="170" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" transform="rotate(-15 300 145)"/>
          <path d="M150 120 L200 95 L250 120 L200 145 Z" fill="#0f172a" />
          <rect x="185" y="130" width="30" height="15" fill="#0f172a" rx="2" />
          <path d="M170 125 L170 160 L165 160" stroke="#fbbf24" stroke-width="3" stroke-linecap="round" />
          <circle cx="165" cy="160" r="3" fill="#fbbf24" />
          <path d="M80 250 C 120 180, 220 150, 300 100" stroke="#fbbf24" stroke-width="4" stroke-dasharray="8 6" stroke-linecap="round" />
          <path d="M300 100 L285 98 M300 100 L298 115" stroke="#fbbf24" stroke-width="4" stroke-linecap="round" />
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- Quick Metrics -->
<section class="py-4 bg-white border-bottom" style="box-shadow: 0 4px 30px rgba(0,0,0,0.04);">
  <div class="container">
    <div class="row text-center gy-4">
      <div class="col-md-3 col-6">
        <div class="p-2">
          <h4 class="fw-bold text-primary mb-0 font-title" id="counter-books"><?php echo max(150, $total_books * 20); ?>+</h4>
          <small class="text-muted">Books Published</small>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="p-2">
          <h4 class="fw-bold text-primary mb-0 font-title">10+</h4>
          <small class="text-muted">Peer Journals</small>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="p-2">
          <h4 class="fw-bold text-primary mb-0 font-title">1200+</h4>
          <small class="text-muted">Trained Interns</small>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="p-2">
          <h4 class="fw-bold text-primary mb-0 font-title"><?php echo max(50, $total_courses * 25); ?>+</h4>
          <small class="text-muted">Academic Courses</small>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- About Overview Section -->
<section class="section-padding bg-light">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 mb-5 mb-lg-0">
        <div class="about-image-wrapper pe-lg-4">
          <div class="card border-0 overflow-hidden shadow-lg" style="border-radius: 20px;">
            <div class="card-body p-0 d-flex align-items-center justify-content-center text-white text-center" style="height: 400px; background: linear-gradient(135deg, #0f4c81 0%, #2563eb 50%, #0ea5e9 100%);">
              <div class="p-4">
                <i class="bi bi-award fs-1 d-block mb-3 text-warning"></i>
                <h3 class="font-title mb-2">Established Excellence</h3>
                <p class="text-white-50 px-md-4 mb-3">Disseminating state-of-the-art research papers, reference books, and journals to advance scientific thinking worldwide.</p>
                <a href="/about.php" class="btn btn-light btn-sm rounded-pill px-4 text-primary fw-semibold">Learn More</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="section-title">
          <span class="text-primary fw-semibold text-uppercase tracking-wider" style="font-size: 0.85rem; letter-spacing: 2px;">About Our Organization</span>
          <h2 class="mt-2">Promoting Knowledge & Learning Globally</h2>
        </div>
        <p class="lead text-dark">Anniyappa Publications is a trusted publishing house for research journals, textbooks, and professional development training modules.</p>
        <p class="mb-4">We act as a catalyst for scholarly progress. By coordinating peer-reviewed scientific journals and offering customized educational courses, we help authors publish high-quality studies and students acquire crucial industry competencies.</p>
        <div class="row gy-3 mb-4">
          <div class="col-sm-6 d-flex align-items-center">
            <i class="bi bi-check-circle-fill text-primary me-3"></i>
            <span class="fw-medium text-dark">Refereed Research Journals</span>
          </div>
          <div class="col-sm-6 d-flex align-items-center">
            <i class="bi bi-check-circle-fill text-primary me-3"></i>
            <span class="fw-medium text-dark">Direct Skill-based Training</span>
          </div>
          <div class="col-sm-6 d-flex align-items-center">
            <i class="bi bi-check-circle-fill text-primary me-3"></i>
            <span class="fw-medium text-dark">Hands-on Internship Exposure</span>
          </div>
          <div class="col-sm-6 d-flex align-items-center">
            <i class="bi bi-check-circle-fill text-primary me-3"></i>
            <span class="fw-medium text-dark">Global Indexing Support</span>
          </div>
        </div>
        <a href="/about.php" class="btn btn-primary-custom rounded-pill px-4 py-2">Learn More About Us</a>
      </div>
    </div>
  </div>
</section>

<!-- Mission & Vision -->
<section class="py-5 bg-white border-top border-bottom">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-6 animate-up">
        <div class="mission-vision-box h-100">
          <div class="d-flex align-items-center mb-3">
            <div class="card-icon-wrapper me-3 mb-0" style="width: 50px; height: 50px; font-size: 1.3rem;">
              <i class="bi bi-eye"></i>
            </div>
            <h3 class="font-title mb-0 h4">Our Vision</h3>
          </div>
          <p class="text-dark mb-0">To establish a premium, globally integrated academic publishing platform that elevates knowledge distribution, creates ethical scientific literature, and prepares young scholars to confidently face industry challenges.</p>
        </div>
      </div>
      <div class="col-md-6 animate-up" style="animation-delay: 0.15s;">
        <div class="mission-vision-box h-100" style="border-left-color: var(--accent-color);">
          <div class="d-flex align-items-center mb-3">
            <div class="card-icon-wrapper me-3 mb-0" style="width: 50px; height: 50px; font-size: 1.3rem; color: var(--accent-color); background: rgba(14, 165, 233, 0.1);">
              <i class="bi bi-bullseye"></i>
            </div>
            <h3 class="font-title mb-0 h4">Our Mission</h3>
          </div>
          <p class="text-dark mb-0">To support researchers with rigorous peer-review and indexing standards, offer students industry-aligned hands-on internship programs, and supply accessible educational materials that promote scientific awareness across all fields.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Featured Books Slider -->
<section class="section-padding bg-light">
  <div class="container">
    <div class="section-title text-center">
      <span class="text-primary fw-semibold text-uppercase" style="letter-spacing: 2px; font-size: 0.85rem;">Our Collection</span>
      <h2>Featured Books</h2>
    </div>
    <div class="book-slider-wrapper" id="bookSlider">
      <button class="slider-btn slider-prev" id="sliderPrev" aria-label="Previous">
        <i class="bi bi-chevron-left"></i>
      </button>
      <div class="book-slider-track" id="sliderTrack">
        <?php 
        $color_gradients = [
          'linear-gradient(135deg, #1e3a8a, #0d9488)',
          'linear-gradient(135deg, #7c3aed, #db2777)',
          'linear-gradient(135deg, #ea580c, #b45309)',
          'linear-gradient(135deg, #0f766e, #111827)',
          'linear-gradient(135deg, #4338ca, #1e40af)',
          'linear-gradient(135deg, #15803d, #166534)'
        ];
        $i = 0;
        foreach ($featured_books as $book): 
          $grad = $color_gradients[$i % count($color_gradients)];
          $i++;
        ?>
          <div class="book-slide">
            <div class="book-mini-card" onclick="window.location.href='/book_details.php?id=<?php echo $book['id']; ?>'" style="cursor: pointer;">
              <div class="book-mini-cover" style="background: <?php echo $grad; ?>; padding: 15px; color: #fff; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                <?php echo sanitize($book['title']); ?>
              </div>
              <div class="book-mini-info">
                <h6><?php echo sanitize($book['title']); ?></h6>
                <small><?php echo sanitize($book['author_names'] ?: 'Dr. R. Anniyappa'); ?></small>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <button class="slider-btn slider-next" id="sliderNext" aria-label="Next">
        <i class="bi bi-chevron-right"></i>
      </button>
    </div>
    <div class="text-center mt-4">
      <a href="/bookshelf.php" class="btn btn-primary-custom rounded-pill px-4">
        View Full Bookshelf <i class="bi bi-arrow-right ms-2"></i>
      </a>
    </div>
  </div>
</section>

<!-- Core Services Section -->
<section class="section-padding bg-white">
  <div class="container">
    <div class="section-title text-center">
      <span class="text-primary fw-semibold text-uppercase" style="letter-spacing: 2px; font-size: 0.85rem;">What We Offer</span>
      <h2>Core Services & Programs</h2>
    </div>
    <div class="row g-4">
      <div class="col-lg-3 col-md-6">
        <div class="custom-card">
          <div class="card-icon-wrapper"><i class="bi bi-book"></i></div>
          <h3>Publications</h3>
          <p>Peer-reviewed scientific journals, textbooks, conference proceedings, and open access journals with full DOIs.</p>
          <a href="/bookshelf.php" class="card-link text-primary">Browse Catalog <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="custom-card">
          <div class="card-icon-wrapper"><i class="bi bi-laptop"></i></div>
          <h3>Internships</h3>
          <p>Practical online and physical training internships for college students in content curation, editing, and operations.</p>
          <a href="/internship.php" class="card-link text-primary">Apply Now <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="custom-card">
          <div class="card-icon-wrapper"><i class="bi bi-journal-check"></i></div>
          <h3>Training</h3>
          <p>Tailored professional development courses in academic writing, LaTeX, typesetting, research methodology, and tools.</p>
          <a href="/internship.php#trainingSection" class="card-link text-primary">Register Today <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="custom-card">
          <div class="card-icon-wrapper"><i class="bi bi-gear-wide-connected"></i></div>
          <h3>Research Support</h3>
          <p>Providing editing, formatting, and indexing assistance to scholars, research teams, and doctoral candidates.</p>
          <a href="/contact.php" class="card-link text-primary">Get Support <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Internship Highlights -->
<section class="section-padding bg-light border-top">
  <div class="container">
    <div class="section-title text-center">
      <span class="text-primary fw-semibold text-uppercase" style="letter-spacing: 2px; font-size: 0.85rem;">Build Your Career</span>
      <h2>Internship Highlights</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="custom-card text-center">
          <div class="card-icon-wrapper mx-auto"><i class="bi bi-calendar-check"></i></div>
          <h4 class="fw-bold h5">Flexible Duration</h4>
          <p class="small">4 to 12 week programs with online, offline, and hybrid options available year-round.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="custom-card text-center">
          <div class="card-icon-wrapper mx-auto"><i class="bi bi-patch-check-fill"></i></div>
          <h4 class="fw-bold h5">Certified Program</h4>
          <p class="small">Receive an authorized internship certificate and letter of recommendation upon completion.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="custom-card text-center">
          <div class="card-icon-wrapper mx-auto"><i class="bi bi-people"></i></div>
          <h4 class="fw-bold h5">Expert Mentorship</h4>
          <p class="small">Work directly under experienced editors and industry professionals throughout your program.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="custom-card text-center">
          <div class="card-icon-wrapper mx-auto"><i class="bi bi-code-slash"></i></div>
          <h4 class="fw-bold h5">Multiple Domains</h4>
          <p class="small">Choose from Content Editing, Research Writing, Web Development, and Editorial Design.</p>
        </div>
      </div>
    </div>
    <div class="text-center mt-5">
      <a href="/internship.php" class="btn btn-primary-custom rounded-pill px-5 py-3">
        <i class="bi bi-rocket-takeoff me-2"></i>Explore Internship Programs
      </a>
    </div>
  </div>
</section>

<!-- CTA Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #0f4c81 0%, #1d4ed8 50%, #0ea5e9 100%);">
  <div class="container text-center py-4">
    <h2 class="text-white font-title mb-3 fs-1">Looking to Publish Your Research?</h2>
    <p class="text-white-50 mx-auto mb-4 fs-5" style="max-width: 600px;">Get indexed in leading global scholarly databases. Our academic editorial team supports you from submission to publication.</p>
    <a href="/contact.php" class="btn btn-light rounded-pill px-5 py-3 fw-semibold text-primary shadow">
      <i class="bi bi-send me-2"></i>Submit Your Manuscript Now
    </a>
  </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
