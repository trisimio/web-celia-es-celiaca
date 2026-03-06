/* ═══════════════════════════════════════════════════════
   CELIA ES CELIACA — App
   ═══════════════════════════════════════════════════════ */

(function () {
  'use strict';

  // ─── Loading Screen ───
  function initLoader() {
    var loader = document.getElementById('loader');
    var progress = loader.querySelector('.loader__progress');
    var pct = 0;

    document.body.classList.add('loading');

    var interval = setInterval(function () {
      pct += Math.random() * 15 + 5;
      if (pct >= 100) {
        pct = 100;
        clearInterval(interval);
        progress.style.width = '100%';
        setTimeout(function () {
          loader.classList.add('hidden');
          document.body.classList.remove('loading');
          initHeroEntrance();
        }, 400);
      } else {
        progress.style.width = pct + '%';
      }
    }, 150);
  }

  // ─── Navigation ───
  function initNav() {
    var nav = document.getElementById('nav');
    var hamburger = document.getElementById('hamburger');
    var mobileMenu = document.getElementById('mobileMenu');
    var mobileLinks = mobileMenu.querySelectorAll('.mobile-menu__link');
    var lastScroll = 0;
    var ticking = false;

    // Scroll behavior: show/hide nav + add background
    window.addEventListener('scroll', function () {
      if (!ticking) {
        requestAnimationFrame(function () {
          var scrollY = window.scrollY;

          // Background on scroll
          if (scrollY > 50) {
            nav.classList.add('nav--scrolled');
          } else {
            nav.classList.remove('nav--scrolled');
          }

          // Hide/show on scroll direction
          if (scrollY > lastScroll && scrollY > 200) {
            nav.classList.add('nav--hidden');
          } else {
            nav.classList.remove('nav--hidden');
          }

          lastScroll = scrollY;
          ticking = false;
        });
        ticking = true;
      }
    });

    // Hamburger
    hamburger.addEventListener('click', function () {
      var isOpen = hamburger.classList.toggle('active');
      mobileMenu.classList.toggle('active');
      mobileMenu.setAttribute('aria-hidden', !isOpen);
      hamburger.setAttribute('aria-expanded', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    // Close mobile menu on link click
    mobileLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        hamburger.classList.remove('active');
        mobileMenu.classList.remove('active');
        mobileMenu.setAttribute('aria-hidden', 'true');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });

    // Smooth scroll for nav links
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
      link.addEventListener('click', function (e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
          e.preventDefault();
          var offset = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-height')) || 70;
          var top = target.getBoundingClientRect().top + window.scrollY - offset;
          window.scrollTo({ top: top, behavior: 'smooth' });
        }
      });
    });
  }

  // ─── Scroll Progress ───
  function initScrollProgress() {
    var progressBar = document.getElementById('scrollProgress');

    window.addEventListener('scroll', function () {
      var scrollTop = window.scrollY;
      var docHeight = document.documentElement.scrollHeight - window.innerHeight;
      var progress = (scrollTop / docHeight) * 100;
      progressBar.style.width = progress + '%';
    });
  }

  // ─── Scroll Reveal ───
  function initScrollReveal() {
    var reveals = document.querySelectorAll('.reveal');

    // Check if reduced motion is preferred
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      reveals.forEach(function (el) {
        el.classList.add('revealed');
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -80px 0px' });

    reveals.forEach(function (el) {
      observer.observe(el);
    });
  }

  // ─── Hero Entrance (GSAP) ───
  function initHeroEntrance() {
    if (typeof gsap === 'undefined' || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      // Fallback: just show everything
      document.querySelectorAll('.hero__label, .hero__title, .hero__tagline, .hero__ctas, .hero__badge').forEach(function (el) {
        el.style.opacity = '1';
      });
      return;
    }

    var tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

    tl.to('.hero__label', { opacity: 1, y: 0, duration: 0.6 })
      .to('.hero__title', { opacity: 1, y: 0, duration: 0.8 }, '-=0.3')
      .to('.hero__tagline', { opacity: 1, y: 0, duration: 0.6 }, '-=0.3')
      .to('.hero__ctas', { opacity: 1, y: 0, duration: 0.5 }, '-=0.2')
      .to('.hero__badge', { opacity: 1, scale: 1, duration: 0.4 }, '-=0.1');
  }

  // ─── GSAP ScrollTrigger Animations ───
  function initGSAPAnimations() {
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    gsap.registerPlugin(ScrollTrigger);

    // Hero parallax
    gsap.to('.hero__content', {
      yPercent: -20,
      ease: 'none',
      scrollTrigger: {
        trigger: '.hero',
        start: 'top top',
        end: 'bottom top',
        scrub: true
      }
    });

    // Section titles
    gsap.utils.toArray('.section__title').forEach(function (title) {
      gsap.from(title, {
        x: -40,
        opacity: 0,
        duration: 0.8,
        scrollTrigger: {
          trigger: title,
          start: 'top 85%',
          toggleActions: 'play none none none'
        }
      });
    });

    // Member cards stagger
    gsap.utils.toArray('.member-card').forEach(function (card, i) {
      gsap.from(card, {
        x: 40,
        opacity: 0,
        duration: 0.6,
        delay: i * 0.1,
        scrollTrigger: {
          trigger: card,
          start: 'top 85%',
          toggleActions: 'play none none none'
        }
      });
    });

    // Album cards stagger
    gsap.utils.toArray('.album-card').forEach(function (card, i) {
      gsap.from(card, {
        y: 60,
        opacity: 0,
        duration: 0.7,
        delay: i * 0.15,
        scrollTrigger: {
          trigger: card,
          start: 'top 85%',
          toggleActions: 'play none none none'
        }
      });
    });

    // Concert items stagger
    gsap.utils.toArray('.concert-item').forEach(function (item, i) {
      gsap.from(item, {
        x: -30,
        opacity: 0,
        duration: 0.5,
        delay: i * 0.1,
        scrollTrigger: {
          trigger: item,
          start: 'top 90%',
          toggleActions: 'play none none none'
        }
      });
    });

    // Press quotes
    gsap.utils.toArray('.press-quote').forEach(function (quote, i) {
      gsap.from(quote, {
        y: 40,
        opacity: 0,
        duration: 0.7,
        delay: i * 0.15,
        scrollTrigger: {
          trigger: quote,
          start: 'top 85%',
          toggleActions: 'play none none none'
        }
      });
    });

    // Counter animation
    gsap.utils.toArray('.stat__number').forEach(function (counter) {
      var target = parseInt(counter.getAttribute('data-count'));
      var obj = { val: 0 };

      gsap.to(obj, {
        val: target,
        duration: 2,
        ease: 'power2.out',
        scrollTrigger: {
          trigger: counter,
          start: 'top 85%',
          toggleActions: 'play none none none'
        },
        onUpdate: function () {
          counter.textContent = Math.round(obj.val);
        }
      });
    });
  }

  // ─── YouTube Lazy Load ───
  function initYouTubeLazy() {
    document.querySelectorAll('.video-thumb[data-youtube]').forEach(function (thumb) {
      function loadVideo() {
        var videoId = thumb.getAttribute('data-youtube');
        var iframe = document.createElement('iframe');
        iframe.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        iframe.allowFullscreen = true;
        iframe.title = thumb.getAttribute('aria-label') || 'Video';

        // Clear contents and add iframe
        thumb.innerHTML = '';
        thumb.appendChild(iframe);
        thumb.style.cursor = 'default';
      }

      thumb.addEventListener('click', loadVideo);
      thumb.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          loadVideo();
        }
      });
    });
  }

  // ─── Tracklist Toggle ───
  function initTracklistToggle() {
    document.querySelectorAll('.tracklist-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var albumId = this.getAttribute('data-album');
        var tracklist = document.getElementById('tracklist-' + albumId);
        var isExpanded = this.getAttribute('aria-expanded') === 'true';

        this.setAttribute('aria-expanded', !isExpanded);

        if (isExpanded) {
          tracklist.hidden = true;
        } else {
          tracklist.hidden = false;
        }
      });
    });
  }

  // ─── Lightbox ───
  function initLightbox() {
    var lightbox = document.getElementById('lightbox');
    var closeBtn = lightbox.querySelector('.lightbox__close');
    var prevBtn = lightbox.querySelector('.lightbox__prev');
    var nextBtn = lightbox.querySelector('.lightbox__next');
    var counter = lightbox.querySelector('.lightbox__counter');
    var photos = document.querySelectorAll('.photo-item');
    var currentIndex = 0;
    var totalPhotos = photos.length;

    function openLightbox(index) {
      currentIndex = index;
      updateLightbox();
      lightbox.hidden = false;
      // Force reflow
      lightbox.offsetHeight;
      lightbox.classList.add('active');
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      lightbox.classList.remove('active');
      lightbox.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      setTimeout(function () {
        lightbox.hidden = true;
      }, 300);
    }

    function updateLightbox() {
      counter.textContent = (currentIndex + 1) + ' / ' + totalPhotos;
      var contentEl = lightbox.querySelector('.lightbox__content');
      var photo = photos[currentIndex];
      var src = photo.getAttribute('data-src');
      var alt = photo.getAttribute('aria-label') || 'Foto';

      if (src) {
        contentEl.innerHTML = '<img src="' + src + '" alt="' + alt + '" class="lightbox__img">';
      }
    }

    function showNext() {
      currentIndex = (currentIndex + 1) % totalPhotos;
      updateLightbox();
    }

    function showPrev() {
      currentIndex = (currentIndex - 1 + totalPhotos) % totalPhotos;
      updateLightbox();
    }

    // Open on photo click
    photos.forEach(function (photo) {
      var handler = function () {
        openLightbox(parseInt(photo.getAttribute('data-index')) || 0);
      };
      photo.addEventListener('click', handler);
      photo.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          handler();
        }
      });
    });

    closeBtn.addEventListener('click', closeLightbox);
    nextBtn.addEventListener('click', showNext);
    prevBtn.addEventListener('click', showPrev);

    // Keyboard navigation
    document.addEventListener('keydown', function (e) {
      if (!lightbox.classList.contains('active')) return;

      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowRight') showNext();
      if (e.key === 'ArrowLeft') showPrev();
    });

    // Close on backdrop click
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });

    // Touch swipe support
    var touchStartX = 0;
    var touchEndX = 0;

    lightbox.addEventListener('touchstart', function (e) {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    lightbox.addEventListener('touchend', function (e) {
      touchEndX = e.changedTouches[0].screenX;
      var diff = touchStartX - touchEndX;
      if (Math.abs(diff) > 50) {
        if (diff > 0) showNext();
        else showPrev();
      }
    }, { passive: true });
  }

  // ─── Contact Form ───
  function initContactForm() {
    var form = document.getElementById('contactForm');
    var success = document.getElementById('formSuccess');

    if (!form) return;

    // Float label fix for select
    var select = form.querySelector('select');
    if (select) {
      select.addEventListener('change', function () {
        this.classList.toggle('has-value', this.value !== '');
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var formData = new FormData(form);

      fetch('/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(formData).toString()
      })
        .then(function (response) {
          if (response.ok) {
            form.hidden = true;
            success.hidden = false;
          } else {
            alert('Error al enviar el formulario. Intentalo de nuevo.');
          }
        })
        .catch(function () {
          alert('Error de conexion. Intentalo de nuevo.');
        });
    });
  }

  // ─── Init ───
  document.addEventListener('DOMContentLoaded', function () {
    initLoader();
    initNav();
    initScrollProgress();
    initScrollReveal();
    initYouTubeLazy();
    initTracklistToggle();
    initLightbox();
    initContactForm();

    // Wait for GSAP to load (deferred)
    if (typeof gsap !== 'undefined') {
      initGSAPAnimations();
    } else {
      // GSAP loads deferred, wait for it
      var gsapCheck = setInterval(function () {
        if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
          clearInterval(gsapCheck);
          initGSAPAnimations();
        }
      }, 100);

      // Give up after 5 seconds
      setTimeout(function () { clearInterval(gsapCheck); }, 5000);
    }
  });
})();
