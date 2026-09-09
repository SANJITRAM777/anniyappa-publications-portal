/**
 * Anniyappa Publications - Premium JavaScript File
 * Handles:
 * 1. Sticky Navbar scroll effect
 * 2. Active navbar link highlighting
 * 3. Bookshelf category filtering and search
 * 4. Home page featured books slider navigation
 * 5. Gallery category filters
 * 6. Responsive gallery lightbox (with invisible bounds filtering & keyboard)
 * 7. Scroll-to-top smooth action & visibility
 * 8. Combined Internship/Training form dynamic field toggles
 * 9. Custom form validations & success animations
 */

document.addEventListener('DOMContentLoaded', () => {

  // ==========================================
  // 1. Sticky Navbar Scroll Effect
  // ==========================================
  const handleNavbarScroll = () => {
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
      if (window.scrollY > 20) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    }
  };

  window.addEventListener('scroll', handleNavbarScroll);
  handleNavbarScroll(); // Initial call on load

  // ==========================================
  // 2. Active Navbar Links Highlighting
  // ==========================================
  const highlightActiveLink = () => {
    const currentPath = window.location.pathname;
    const filename = currentPath.substring(currentPath.lastIndexOf('/') + 1) || 'index.html';

    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    navLinks.forEach(link => {
      const linkHref = link.getAttribute('href');
      // Exact match or fallback for index.html
      if (linkHref === filename || (filename === 'index.html' && linkHref === '/') || (filename === '' && linkHref === 'index.html')) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  };

  highlightActiveLink();

  // ==========================================
  // 3. Bookshelf Search & Category Filter
  // ==========================================
  const initPublicationsFilter = () => {
    const searchInput = document.getElementById('pubSearch');
    const categoryButtons = document.querySelectorAll('.category-btn-group .category-btn');
    const publicationCols = document.querySelectorAll('.publication-col');

    if (publicationCols.length === 0) return; // Exit if not on bookshelf page

    let currentCategory = 'all';
    let searchQuery = '';

    const filterPublications = () => {
      publicationCols.forEach(col => {
        const card = col.querySelector('.publication-card');
        if (!card) return;
        const category = card.getAttribute('data-category');
        const title = col.querySelector('.pub-title') ? col.querySelector('.pub-title').textContent.toLowerCase() : '';
        const author = col.querySelector('.book-cover-author') ? col.querySelector('.book-cover-author').textContent.toLowerCase() : '';
        const desc = col.querySelector('.pub-desc') ? col.querySelector('.pub-desc').textContent.toLowerCase() : '';

        const matchesCategory = currentCategory === 'all' || category === currentCategory;
        const matchesSearch = title.includes(searchQuery) || author.includes(searchQuery) || desc.includes(searchQuery);

        if (matchesCategory && matchesSearch) {
          col.style.display = 'block';
          col.classList.remove('animate-up');
          void col.offsetWidth; // Trigger reflow
          col.classList.add('animate-up');
        } else {
          col.style.display = 'none';
        }
      });

      // Show "No Results" message if no items match
      let visibleCount = 0;
      publicationCols.forEach(col => {
        if (col.style.display !== 'none') visibleCount++;
      });

      let noResultsMsg = document.getElementById('noResultsMessage');
      if (visibleCount === 0) {
        if (!noResultsMsg) {
          noResultsMsg = document.createElement('div');
          noResultsMsg.id = 'noResultsMessage';
          noResultsMsg.className = 'col-12 text-center py-5 animate-up';
          noResultsMsg.innerHTML = `
            <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
            <h4 class="text-dark">No Publications Found</h4>
            <p class="text-muted">Try refining your search terms or selecting a different category.</p>
          `;
          const grid = document.getElementById('publicationsGrid');
          if (grid) grid.appendChild(noResultsMsg);
        }
      } else if (noResultsMsg) {
        noResultsMsg.remove();
      }
    };

    // Category Button Clicks
    categoryButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        categoryButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCategory = btn.getAttribute('data-filter');
        filterPublications();
      });
    });

    // Search Input Typing
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        searchQuery = e.target.value.toLowerCase().trim();
        filterPublications();
      });
    }
  };

  initPublicationsFilter();

  // ==========================================
  // 4. Featured Books Slider (Home Page)
  // ==========================================
  const initBookSlider = () => {
    const track = document.getElementById('sliderTrack');
    const prevBtn = document.getElementById('sliderPrev');
    const nextBtn = document.getElementById('sliderNext');
    if (!track || !prevBtn || !nextBtn) return;

    let scrollAmount = 0;

    // Slide step matches .book-slide min-width (220px) + gap (25px)
    const getStepWidth = () => {
      const firstSlide = track.querySelector('.book-slide');
      return firstSlide ? firstSlide.offsetWidth + 25 : 245;
    };

    nextBtn.addEventListener('click', () => {
      const slideWidth = getStepWidth();
      const maxScroll = track.scrollWidth - track.clientWidth;
      if (scrollAmount < maxScroll) {
        scrollAmount += slideWidth;
        if (scrollAmount > maxScroll) scrollAmount = maxScroll;
      } else {
        scrollAmount = 0; // Wrap back to beginning
      }
      track.style.transform = `translateX(-${scrollAmount}px)`;
    });

    prevBtn.addEventListener('click', () => {
      const slideWidth = getStepWidth();
      if (scrollAmount > 0) {
        scrollAmount -= slideWidth;
        if (scrollAmount < 0) scrollAmount = 0;
      } else {
        const maxScroll = track.scrollWidth - track.clientWidth;
        scrollAmount = maxScroll; // Wrap to end
      }
      track.style.transform = `translateX(-${scrollAmount}px)`;
    });

    // Touch Swipe Support for mobile devices
    let startX = 0;
    let isSwiping = false;

    track.addEventListener('touchstart', (e) => {
      startX = e.touches[0].clientX;
      isSwiping = true;
    });

    track.addEventListener('touchmove', (e) => {
      if (!isSwiping) return;
      const currentX = e.touches[0].clientX;
      const diffX = startX - currentX;
      const slideWidth = getStepWidth();
      const maxScroll = track.scrollWidth - track.clientWidth;

      if (Math.abs(diffX) > 50) {
        if (diffX > 0) { // Swiped left, show next
          scrollAmount = Math.min(scrollAmount + slideWidth, maxScroll);
        } else { // Swiped right, show prev
          scrollAmount = Math.max(scrollAmount - slideWidth, 0);
        }
        track.style.transform = `translateX(-${scrollAmount}px)`;
        isSwiping = false;
      }
    });

    track.addEventListener('touchend', () => {
      isSwiping = false;
    });
  };

  initBookSlider();

  // ==========================================
  // 5. Gallery Filters & Lightbox (Gallery Page)
  // ==========================================
  const initGallery = () => {
    const filterButtons = document.querySelectorAll('[data-gallery-filter]');
    const galleryItems = document.querySelectorAll('.gallery-item-col');
    const lightbox = document.getElementById('galleryLightbox');

    if (galleryItems.length === 0) return; // Exit if not on gallery page

    // Category filtering
    if (filterButtons.length > 0) {
      filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          filterButtons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          const filterValue = btn.getAttribute('data-gallery-filter');

          galleryItems.forEach(item => {
            const cat = item.getAttribute('data-gallery-cat');
            if (filterValue === 'all' || cat === filterValue) {
              item.style.display = 'block';
              item.classList.remove('animate-up');
              void item.offsetWidth; // Trigger reflow
              item.classList.add('animate-up');
            } else {
              item.style.display = 'none';
            }
          });
        });
      });
    }

    // Lightbox handling
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const closeBtn = document.getElementById('lightboxClose');
    const prevBtn = document.getElementById('lightboxPrev');
    const nextBtn = document.getElementById('lightboxNext');
    const cards = document.querySelectorAll('.gallery-card');

    if (!lightbox || cards.length === 0) return;

    let currentIndex = 0;
    let visibleCards = [];

    const updateLightboxContent = () => {
      const card = visibleCards[currentIndex];
      if (!card) return;
      const src = card.getAttribute('data-src');
      const title = card.getAttribute('data-title');
      const desc = card.getAttribute('data-desc');

      if (lightboxImg) {
        lightboxImg.style.opacity = '0';
        setTimeout(() => {
          lightboxImg.src = src;
          if (lightboxCaption) {
            lightboxCaption.innerHTML = `<h5 class="text-white mb-1">${title}</h5><p class="mb-0 text-white-50 small">${desc}</p>`;
          }
          lightboxImg.style.opacity = '1';
        }, 150);
      }
    };

    cards.forEach(card => {
      card.addEventListener('click', (e) => {
        e.preventDefault();
        // Recalculate visible cards on click to reflect current category filter state
        visibleCards = Array.from(cards).filter(c => {
          const col = c.closest('.gallery-item-col');
          return col && col.style.display !== 'none';
        });
        currentIndex = visibleCards.indexOf(card);
        if (currentIndex === -1) currentIndex = 0;

        updateLightboxContent();
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden'; // Lock scrolling
      });
    });

    const closeLightbox = () => {
      lightbox.classList.remove('active');
      document.body.style.overflow = ''; // Unlock scrolling
      setTimeout(() => {
        if (lightboxImg) lightboxImg.src = '';
      }, 300);
    };

    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox || e.target.classList.contains('lightbox-content')) {
        closeLightbox();
      }
    });

    const showNext = () => {
      if (visibleCards.length <= 1) return;
      currentIndex = (currentIndex + 1) % visibleCards.length;
      updateLightboxContent();
    };

    const showPrev = () => {
      if (visibleCards.length <= 1) return;
      currentIndex = (currentIndex - 1 + visibleCards.length) % visibleCards.length;
      updateLightboxContent();
    };

    if (nextBtn) nextBtn.addEventListener('click', showNext);
    if (prevBtn) prevBtn.addEventListener('click', showPrev);

    // Keyboard support
    document.addEventListener('keydown', (e) => {
      if (!lightbox.classList.contains('active')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowRight') showNext();
      if (e.key === 'ArrowLeft') showPrev();
    });
  };

  initGallery();

  // ==========================================
  // 6. Scroll-to-top action & visibility
  // ==========================================
  const initScrollToTop = () => {
    const btn = document.getElementById('scrollTopBtn');
    if (!btn) return;

    window.addEventListener('scroll', () => {
      if (window.scrollY > 400) {
        btn.classList.add('visible');
      } else {
        btn.classList.remove('visible');
      }
    });

    btn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  };

  initScrollToTop();

  // ==========================================
  // 7. Combined Internship/Training Form Toggles
  // ==========================================
  const initFormToggles = () => {
    const programType = document.getElementById('programType');
    const domainContainer = document.getElementById('domainSelectContainer');
    const courseContainer = document.getElementById('courseSelectContainer');
    const domainSelect = document.getElementById('domainSelect');
    const courseSelect = document.getElementById('courseSelect');
    const resumeLink = document.getElementById('resumeLink');
    const resumeLinkLabel = document.getElementById('resumeLinkLabel');
    const resumeLinkFeedback = document.getElementById('resumeLinkFeedback');

    if (!programType) return; // Exit if not on internship page

    const toggleFields = () => {
      const val = programType.value;
      if (val === 'internship') {
        domainContainer.style.display = 'block';
        if (domainSelect) domainSelect.setAttribute('required', '');

        courseContainer.style.display = 'none';
        if (courseSelect) {
          courseSelect.removeAttribute('required');
          courseSelect.value = '';
        }

        if (resumeLink) {
          resumeLink.setAttribute('required', '');
          if (resumeLinkLabel) {
            resumeLinkLabel.innerHTML = 'Resume/CV Link (Google Drive / GitHub / Dropbox) <span class="text-danger">*</span>';
          }
          if (resumeLinkFeedback) {
            resumeLinkFeedback.textContent = 'Please share a link to your resume or curriculum vitae.';
          }
        }
      } else if (val === 'training') {
        courseContainer.style.display = 'block';
        if (courseSelect) courseSelect.setAttribute('required', '');

        domainContainer.style.display = 'none';
        if (domainSelect) {
          domainSelect.removeAttribute('required');
          domainSelect.value = '';
        }

        if (resumeLink) {
          resumeLink.removeAttribute('required');
          if (resumeLinkLabel) {
            resumeLinkLabel.innerHTML = 'Resume/CV Link (Optional for Training)';
          }
          if (resumeLinkFeedback) {
            resumeLinkFeedback.textContent = '';
          }
        }
      } else {
        domainContainer.style.display = 'none';
        courseContainer.style.display = 'none';
        if (domainSelect) domainSelect.removeAttribute('required');
        if (courseSelect) courseSelect.removeAttribute('required');
        if (resumeLink) resumeLink.removeAttribute('required');
      }
    };

    programType.addEventListener('change', toggleFields);
    toggleFields(); // Initial call

    // Handle course cards "Register Now" auto scroll & pre-select
    const enrollButtons = document.querySelectorAll('.enroll-btn');
    enrollButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const val = btn.getAttribute('data-value');
        programType.value = 'training';
        toggleFields();
        if (courseSelect) {
          courseSelect.value = val;
        }
      });
    });
  };

  initFormToggles();

  // ==========================================
  // 8. Custom Form Validations & Success Animations
  // ==========================================
  const initFormValidations = () => {
    const forms = document.querySelectorAll('.custom-validate-form');

    forms.forEach(form => {
      form.addEventListener('submit', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (form.checkValidity()) {
          // If valid, show custom success feedback instead of page reload
          showSuccessMessage(form);
        } else {
          form.classList.add('was-validated');
        }
      });
    });
  };

  const showSuccessMessage = (form) => {
    const parentContainer = form.parentElement;
    const isContact = form.id === 'contactUsForm';

    // Store height to prevent layout jumping
    const containerHeight = parentContainer.offsetHeight;
    parentContainer.style.minHeight = `${containerHeight}px`;

    // Success Block Markup
    const successTitle = isContact ? 'Message Received!' : 'Registration Received!';
    const successBody = isContact
      ? 'Thank you for contacting Anniyappa Publications. Our representative will review your request and get back to you within 24 business hours.'
      : 'Thank you for enrolling. Our academic coordinators will email you schedules, assessment steps, and syllabus information shortly.';

    const successHtml = `
      <div class="text-center py-5 animate-up" id="successBlock">
        <div class="mb-4">
          <svg class="success-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" style="width: 80px; height: 80px; display: block; margin: 0 auto;">
            <circle class="success-checkmark-circle" cx="26" cy="26" r="25" fill="none" stroke="#2563eb" stroke-width="3" style="stroke-dasharray: 166; stroke-dashoffset: 166; animation: stroke-circle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;"/>
            <path class="success-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" stroke="#2563eb" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" style="stroke-dasharray: 48; stroke-dashoffset: 48; animation: stroke-check 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;"/>
          </svg>
        </div>
        <h3 class="mb-3">${successTitle}</h3>
        <p class="text-muted mb-4 px-md-4">${successBody}</p>
        <button class="btn btn-primary-custom rounded-pill px-4" id="resetFormBtn">Submit Another Response</button>
      </div>
    `;

    // Add styles for checkmark dynamically
    if (!document.getElementById('checkmark-styles')) {
      const styleNode = document.createElement('style');
      styleNode.id = 'checkmark-styles';
      styleNode.textContent = `
        @keyframes stroke-circle { 100% { stroke-dashoffset: 0; } }
        @keyframes stroke-check { 100% { stroke-dashoffset: 0; } }
      `;
      document.head.appendChild(styleNode);
    }

    // Hide the form with fade
    form.style.transition = 'opacity 0.3s ease';
    form.style.opacity = '0';

    setTimeout(() => {
      form.style.display = 'none';
      const successDiv = document.createElement('div');
      successDiv.innerHTML = successHtml;
      parentContainer.appendChild(successDiv.firstElementChild);

      // Handle reset click
      document.getElementById('resetFormBtn').addEventListener('click', () => {
        const successBlock = document.getElementById('successBlock');
        if (successBlock) {
          successBlock.style.transition = 'opacity 0.3s ease';
          successBlock.style.opacity = '0';

          setTimeout(() => {
            successBlock.remove();
            form.reset();
            form.classList.remove('was-validated');

            // Re-trigger field toggles if on internship page
            if (form.id === 'internshipForm') {
              const programType = document.getElementById('programType');
              if (programType) {
                programType.value = '';
                const domainContainer = document.getElementById('domainSelectContainer');
                const courseContainer = document.getElementById('courseSelectContainer');
                if (domainContainer) domainContainer.style.display = 'none';
                if (courseContainer) courseContainer.style.display = 'none';
              }
            }

            form.style.display = 'block';
            void form.offsetWidth; // Trigger reflow
            form.style.opacity = '1';
            parentContainer.style.minHeight = 'auto';
          }, 300);
        }
      });
    }, 300);
  };

  initFormValidations();

});
