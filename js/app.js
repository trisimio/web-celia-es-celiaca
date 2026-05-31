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

    window.addEventListener('scroll', function () {
      if (!ticking) {
        requestAnimationFrame(function () {
          var scrollY = window.scrollY;

          if (scrollY > 50) {
            nav.classList.add('nav--scrolled');
          } else {
            nav.classList.remove('nav--scrolled');
          }

          // Nav always visible — no hide on scroll

          lastScroll = scrollY;
          ticking = false;
        });
        ticking = true;
      }
    });

    hamburger.addEventListener('click', function () {
      var isOpen = hamburger.classList.toggle('active');
      mobileMenu.classList.toggle('active');
      mobileMenu.setAttribute('aria-hidden', !isOpen);
      hamburger.setAttribute('aria-expanded', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    mobileLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        hamburger.classList.remove('active');
        mobileMenu.classList.remove('active');
        mobileMenu.setAttribute('aria-hidden', 'true');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });

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
  // This is the ONLY system for entrance animations.
  // GSAP is NOT used for reveal — it caused inline style conflicts.
  function initScrollReveal() {
    var reveals = document.querySelectorAll('.reveal');

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      reveals.forEach(function (el) {
        el.classList.add('revealed');
      });
      return;
    }

    // Group reveals by parent section for stagger effect
    var sectionMap = new Map();
    reveals.forEach(function (el) {
      var section = el.closest('.section, .hero');
      var key = section ? section.id || 'unknown' : 'unknown';
      if (!sectionMap.has(key)) sectionMap.set(key, []);
      sectionMap.get(key).push(el);
    });

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          // Find siblings in same section for stagger
          var section = el.closest('.section, .hero');
          var key = section ? section.id || 'unknown' : 'unknown';
          var siblings = sectionMap.get(key) || [];
          var idx = siblings.indexOf(el);

          // Apply stagger delay
          var delay = Math.min(idx * 0.08, 0.5);
          el.style.transitionDelay = delay + 's';

          el.classList.add('revealed');
          observer.unobserve(el);

          // Clean up delay after animation
          setTimeout(function () {
            el.style.transitionDelay = '';
          }, (delay + 1) * 1000);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    reveals.forEach(function (el) {
      observer.observe(el);
    });
  }

  // ─── Hero Entrance (GSAP) ───
  function initHeroEntrance() {
    if (typeof gsap === 'undefined' || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      document.querySelectorAll('.hero__logo, .hero__label, .hero__tagline, .hero__ctas, .hero__badge').forEach(function (el) {
        el.style.opacity = '1';
      });
      return;
    }

    var tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

    tl.to('.hero__logo', { opacity: 1, scale: 1, duration: 0.8 })
      .to('.hero__label', { opacity: 1, y: 0, duration: 0.5 }, '-=0.3')
      .to('.hero__tagline', { opacity: 1, y: 0, duration: 0.6 }, '-=0.3')
      .to('.hero__ctas', { opacity: 1, y: 0, duration: 0.5 }, '-=0.2')
      .to('.hero__badge', { opacity: 1, scale: 1, duration: 0.4 }, '-=0.1');
  }

  // ─── GSAP ScrollTrigger — Parallax & Counters only ───
  // Reduced from 57 triggers to 7. No more reveal animations via GSAP.
  function initGSAPAnimations() {
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    gsap.registerPlugin(ScrollTrigger);

    // 1. Hero content parallax
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

    // 2. Hero bg parallax
    gsap.to('.hero__bg-img', {
      yPercent: 30,
      ease: 'none',
      scrollTrigger: {
        trigger: '.hero',
        start: 'top top',
        end: 'bottom top',
        scrub: true
      }
    });

    // 3. Bio image parallax
    gsap.to('.bio__img', {
      yPercent: -15,
      ease: 'none',
      scrollTrigger: {
        trigger: '.bio__image',
        start: 'top bottom',
        end: 'bottom top',
        scrub: true
      }
    });

    // 4-7. Counter animations (grouped under one trigger per section)
    var counters = document.querySelectorAll('.stat__number');
    if (counters.length) {
      var counterSection = counters[0].closest('.bio__stats');
      if (counterSection) {
        var counterObjects = [];
        counters.forEach(function (counter) {
          counterObjects.push({
            el: counter,
            target: parseInt(counter.getAttribute('data-count')),
            obj: { val: 0 }
          });
        });

        ScrollTrigger.create({
          trigger: counterSection,
          start: 'top 85%',
          once: true,
          onEnter: function () {
            counterObjects.forEach(function (c) {
              gsap.to(c.obj, {
                val: c.target,
                duration: 2,
                ease: 'power2.out',
                onUpdate: function () {
                  c.el.textContent = Math.round(c.obj.val);
                }
              });
            });
          }
        });
      }
    }

    // Refresh after everything is laid out
    ScrollTrigger.refresh();
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

    document.addEventListener('keydown', function (e) {
      if (!lightbox.classList.contains('active')) return;

      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowRight') showNext();
      if (e.key === 'ArrowLeft') showPrev();
    });

    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });

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

    var select = form.querySelector('select');
    if (select) {
      select.addEventListener('change', function () {
        this.classList.toggle('has-value', this.value !== '');
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var formData = new FormData(form);

      var submitBtn = form.querySelector('button[type=submit]');
      var originalLabel = submitBtn ? submitBtn.textContent : '';
      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enviando…'; }

      fetch('contact.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: new URLSearchParams(formData).toString()
      })
        .then(function (response) { return response.json().catch(function(){ return { ok: response.ok }; }).then(function(j){ return { status: response.status, body: j }; }); })
        .then(function (r) {
          if (r.status >= 200 && r.status < 300 && r.body && r.body.ok) {
            form.hidden = true;
            success.hidden = false;
          } else {
            var msg = (r.body && (r.body.error || (r.body.errors && r.body.errors.join('. ')))) || 'Error al enviar el formulario. Inténtalo de nuevo o escribe a celiaesceliaca@gmail.com.';
            alert(msg);
          }
        })
        .catch(function () {
          alert('Error de conexión. Inténtalo de nuevo o escribe a celiaesceliaca@gmail.com.');
        })
        .finally(function () {
          if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = originalLabel; }
        });
    });
  }

  // ─── Hero video robustness ───
  // Si el iframe de YouTube no carga (CSP, bloqueo, fallo de red), recargamos una vez tras 4s.
  // Tambien forzamos un reload al recuperar foco si el documento estuvo oculto un rato (ahorro de bateria iOS).
  function initHeroVideo() {
    var iframe = document.getElementById('heroVideo');
    if (!iframe) return;
    var vid = iframe.getAttribute('data-video-id');
    if (!vid) return;

    function buildSrc(extra) {
      var params = 'autoplay=1&mute=1&loop=1&playlist=' + vid + '&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1&iv_load_policy=3&disablekb=1&enablejsapi=1';
      if (extra) params += '&_=' + Date.now();
      return 'https://www.youtube-nocookie.com/embed/' + vid + '?' + params;
    }

    var loaded = false;
    iframe.addEventListener('load', function () { loaded = true; }, { once: true });

    // Fallback de carga (si la primera no carga en 4s, fuerza reload una vez)
    setTimeout(function () {
      if (!loaded) {
        try { iframe.src = buildSrc(true); } catch (e) {}
      }
    }, 4000);

    // Cuando la pestaña vuelve a primer plano tras >30s oculta, recargamos para reanudar autoplay
    var hiddenSince = 0;
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'hidden') {
        hiddenSince = Date.now();
      } else if (hiddenSince && (Date.now() - hiddenSince) > 30000) {
        hiddenSince = 0;
        try { iframe.src = buildSrc(true); } catch (e) {}
      }
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
    initHeroVideo();

    // Wait for GSAP to load (deferred)
    if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
      initGSAPAnimations();
    } else {
      var gsapCheck = setInterval(function () {
        if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
          clearInterval(gsapCheck);
          initGSAPAnimations();
        }
      }, 100);

      setTimeout(function () { clearInterval(gsapCheck); }, 5000);
    }
  });
})();
