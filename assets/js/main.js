/* =============================================================
   MT SAFARIS — Main JavaScript
   ============================================================= */

(function () {
  'use strict';

  // ============================================================
  // PAGE LOADER
  // ============================================================
  window.addEventListener('load', () => {
    const loader = document.querySelector('.page-loader');
    if (loader) {
      setTimeout(() => loader.classList.add('hidden'), 300);
    }
  });

  // ============================================================
  // HEADER SCROLL BEHAVIOR
  // ============================================================
  const header = document.getElementById('header');
  if (header) {
    const onScroll = () => {
      if (window.scrollY > 80) {
        header.classList.add('scrolled');
        header.classList.remove('transparent');
      } else {
        header.classList.remove('scrolled');
        if (header.dataset.transparent !== undefined) {
          header.classList.add('transparent');
        }
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ============================================================
  // MOBILE NAV
  // ============================================================
  const mobileToggle = document.querySelector('.mobile-toggle');
  const mobileNav    = document.querySelector('.mobile-nav');
  const mobileClose  = document.querySelector('.mobile-nav .close-btn');

  if (mobileToggle && mobileNav) {
    mobileToggle.addEventListener('click', () => {
      mobileNav.style.display = 'flex';
      setTimeout(() => mobileNav.classList.add('open'), 10);
      document.body.style.overflow = 'hidden';
    });
  }
  if (mobileClose) {
    mobileClose.addEventListener('click', closeMobileNav);
  }
  function closeMobileNav() {
    if (!mobileNav) return;
    mobileNav.classList.remove('open');
    setTimeout(() => {
      mobileNav.style.display = '';
      document.body.style.overflow = '';
    }, 400);
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMobileNav(); });

  // ============================================================
  // BACK TO TOP
  // ============================================================
  const btt = document.querySelector('.back-to-top');
  if (btt) {
    window.addEventListener('scroll', () => {
      btt.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
    btt.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  // ============================================================
  // SCROLL ANIMATIONS
  // ============================================================
  const animEls = document.querySelectorAll('[data-animate]');
  if (animEls.length && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          const delay = e.target.dataset.delay || 0;
          setTimeout(() => e.target.classList.add('animated'), +delay);
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12 });
    animEls.forEach(el => io.observe(el));
  }

  // ============================================================
  // TABS
  // ============================================================
  document.querySelectorAll('[data-tab]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target  = btn.dataset.tab;
      const parent  = btn.closest('[data-tabs]');
      if (!parent) return;
      parent.querySelectorAll('[data-tab]').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      parent.querySelectorAll('.tab-panel').forEach(p => {
        p.classList.toggle('active', p.dataset.panel === target);
      });
    });
  });

  // Package detail tabs
  document.querySelectorAll('.tab-nav-item').forEach(item => {
    item.addEventListener('click', () => {
      const tabId = item.dataset.tabId;
      document.querySelectorAll('.tab-nav-item').forEach(i => i.classList.remove('active'));
      document.querySelectorAll('.tab-content > div').forEach(p => p.classList.remove('active'));
      item.classList.add('active');
      const panel = document.querySelector(`.tab-content > div[data-panel="${tabId}"]`);
      if (panel) panel.classList.add('active');
    });
  });

  // ============================================================
  // ACCORDION
  // ============================================================
  document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
      const item   = header.parentElement;
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.accordion-item.open').forEach(i => i.classList.remove('open'));
      if (!isOpen) item.classList.add('open');
    });
  });

  // ============================================================
  // GALLERY / LIGHTBOX
  // ============================================================
  const lightbox   = document.querySelector('.lightbox');
  const lightboxImg = lightbox?.querySelector('img');

  document.querySelectorAll('[data-lightbox]').forEach(trigger => {
    trigger.addEventListener('click', () => {
      const src = trigger.dataset.lightbox || trigger.src;
      if (lightbox && lightboxImg) {
        lightboxImg.src = src;
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
      }
    });
  });
  if (lightbox) {
    lightbox.querySelector('.lightbox-close')?.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });
  }
  function closeLightbox() {
    lightbox?.classList.remove('open');
    document.body.style.overflow = '';
  }

  // Gallery thumbs (package detail)
  document.querySelectorAll('.gallery-thumb').forEach(thumb => {
    thumb.addEventListener('click', () => {
      const mainImg = document.querySelector('.gallery-main-img');
      if (mainImg) mainImg.src = thumb.querySelector('img').src;
      document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });

  // ============================================================
  // COUNTER ANIMATION
  // ============================================================
  function animateCounter(el, from, to, duration) {
    const start = performance.now();
    const update = (time) => {
      const progress = Math.min((time - start) / duration, 1);
      const val = Math.floor(progress * (to - from) + from);
      el.textContent = val.toLocaleString();
      if (progress < 1) requestAnimationFrame(update);
      else el.textContent = to.toLocaleString();
    };
    requestAnimationFrame(update);
  }

  const counters = document.querySelectorAll('[data-counter]');
  if (counters.length && 'IntersectionObserver' in window) {
    const counterIO = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          animateCounter(e.target, 0, +e.target.dataset.counter, 1800);
          counterIO.unobserve(e.target);
        }
      });
    }, { threshold: 0.5 });
    counters.forEach(c => counterIO.observe(c));
  }

  // ============================================================
  // WISHLIST TOGGLE
  // ============================================================
  document.querySelectorAll('.package-wishlist').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const packageId = btn.dataset.id;
      try {
        const res = await fetch('/api/wishlist.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getMeta('csrf-token') },
          body: JSON.stringify({ package_id: packageId }),
        });
        const data = await res.json();
        if (data.success) {
          btn.classList.toggle('active', data.in_wishlist);
          btn.querySelector('i').className = data.in_wishlist ? 'fas fa-heart' : 'far fa-heart';
          showToast(data.in_wishlist ? 'Added to wishlist' : 'Removed from wishlist', 'success');
        }
      } catch {}
    });
  });

  // ============================================================
  // SEARCH HERO
  // ============================================================
  const heroSearch = document.getElementById('heroSearchForm');
  if (heroSearch) {
    heroSearch.addEventListener('submit', e => {
      e.preventDefault();
      const data = new FormData(heroSearch);
      const params = new URLSearchParams(
        [...data.entries()].filter(([, v]) => v.trim())
      );
      window.location.href = '/packages.php?' + params.toString();
    });
  }

  // ============================================================
  // COUPON CHECK
  // ============================================================
  const couponBtn = document.getElementById('applyCoupon');
  if (couponBtn) {
    couponBtn.addEventListener('click', async () => {
      const code    = document.getElementById('couponCode')?.value.trim();
      const amount  = document.getElementById('bookingAmount')?.value;
      const resultEl = document.getElementById('couponResult');
      if (!code) return;
      couponBtn.textContent = 'Checking…';
      couponBtn.disabled = true;
      try {
        const res  = await fetch('/api/coupon.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ code, amount }),
        });
        const data = await res.json();
        if (resultEl) {
          resultEl.textContent = data.message;
          resultEl.className   = 'form-hint ' + (data.valid ? 'text-success' : 'form-error');
        }
        if (data.valid) {
          document.getElementById('discountAmount').value = data.discount;
          updatePriceSummary();
        }
      } catch {
        if (resultEl) resultEl.textContent = 'Error checking coupon.';
      } finally {
        couponBtn.textContent = 'Apply';
        couponBtn.disabled = false;
      }
    });
  }

  // ============================================================
  // BOOKING STEP NAVIGATION
  // ============================================================
  let currentStep = 1;
  const stepPanels = document.querySelectorAll('[data-step]');
  const stepDots   = document.querySelectorAll('.step-circle');

  function showStep(n) {
    stepPanels.forEach(p => {
      p.style.display = +p.dataset.step === n ? 'block' : 'none';
    });
    stepDots.forEach((dot, i) => {
      dot.closest('.booking-step').classList.toggle('active', i + 1 === n);
      dot.closest('.booking-step').classList.toggle('done', i + 1 < n);
    });
    window.scrollTo({ top: 200, behavior: 'smooth' });
  }

  document.querySelectorAll('[data-step-next]').forEach(btn => {
    btn.addEventListener('click', () => {
      if (validateStep(currentStep)) {
        currentStep++;
        showStep(currentStep);
      }
    });
  });
  document.querySelectorAll('[data-step-prev]').forEach(btn => {
    btn.addEventListener('click', () => { currentStep--; showStep(currentStep); });
  });

  function validateStep(n) {
    const panel = document.querySelector(`[data-step="${n}"]`);
    if (!panel) return true;
    let valid = true;
    panel.querySelectorAll('[required]').forEach(field => {
      if (!field.value.trim()) {
        field.classList.add('is-invalid');
        valid = false;
      } else {
        field.classList.remove('is-invalid');
      }
    });
    if (!valid) showToast('Please fill in all required fields.', 'error');
    return valid;
  }

  // ============================================================
  // TRAVELER COUNT (BOOKING)
  // ============================================================
  document.querySelectorAll('.traveler-counter').forEach(counter => {
    const minus = counter.querySelector('.tc-minus');
    const plus  = counter.querySelector('.tc-plus');
    const input = counter.querySelector('input');
    if (!minus || !plus || !input) return;

    minus.addEventListener('click', () => {
      const min = +input.min || 0;
      if (+input.value > min) { input.value = +input.value - 1; updatePriceSummary(); }
    });
    plus.addEventListener('click', () => {
      const max = +input.max || 20;
      if (+input.value < max) { input.value = +input.value + 1; updatePriceSummary(); }
    });
  });

  function updatePriceSummary() {
    const basePrice = +document.getElementById('basePrice')?.value || 0;
    const adults    = +document.getElementById('adultsCount')?.value || 1;
    const children  = +document.getElementById('childrenCount')?.value || 0;
    const discount  = +document.getElementById('discountAmount')?.value || 0;
    const taxRate   = +document.getElementById('taxRate')?.value || 16;

    const subtotal = basePrice * (adults + children * 0.5);
    const tax      = (subtotal - discount) * taxRate / 100;
    const total    = subtotal - discount + tax;

    setText('summarySubtotal', '$' + subtotal.toFixed(2));
    setText('summaryDiscount', discount > 0 ? '-$' + discount.toFixed(2) : '$0.00');
    setText('summaryTax',      '$' + tax.toFixed(2));
    setText('summaryTotal',    '$' + total.toFixed(2));
    if (document.getElementById('hiddenTotal')) {
      document.getElementById('hiddenTotal').value = total.toFixed(2);
    }
  }

  // ============================================================
  // NEWSLETTER
  // ============================================================
  document.querySelectorAll('.newsletter-form').forEach(form => {
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const emailInput = form.querySelector('input[type="email"]');
      const btn        = form.querySelector('button');
      const email      = emailInput?.value.trim();
      if (!email) return;
      btn.textContent  = 'Subscribing…';
      btn.disabled     = true;
      try {
        const res  = await fetch('/api/newsletter.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email }),
        });
        const data = await res.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success && emailInput) emailInput.value = '';
      } catch {
        showToast('Something went wrong. Please try again.', 'error');
      } finally {
        btn.textContent = 'Subscribe';
        btn.disabled = false;
      }
    });
  });

  // ============================================================
  // CATEGORY FILTER (packages page)
  // ============================================================
  document.querySelectorAll('.cat-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const cat = tab.dataset.cat;
      document.querySelectorAll('.package-card[data-cat]').forEach(card => {
        card.closest('[data-card-wrap]')?.style.setProperty('display',
          (!cat || cat === 'all' || card.dataset.cat === cat) ? '' : 'none');
      });
    });
  });

  // ============================================================
  // TOAST NOTIFICATIONS
  // ============================================================
  function showToast(message, type = 'info', duration = 4000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9000;display:flex;flex-direction:column;gap:10px;';
      document.body.appendChild(container);
    }
    const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
    const colors = { success: '#38A169', error: '#E53E3E', warning: '#D69E2E', info: '#3182CE' };
    const toast = document.createElement('div');
    toast.style.cssText = `background:#fff;border-left:4px solid ${colors[type]||colors.info};
      padding:14px 18px;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);
      display:flex;align-items:center;gap:10px;font-size:.875rem;
      animation:fadeInUp .3s ease;max-width:320px;`;
    toast.innerHTML = `<span style="color:${colors[type]||colors.info};font-weight:700;font-size:1rem">${icons[type]||'ℹ'}</span>
      <span style="flex:1">${message}</span>
      <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;opacity:.5;font-size:1rem">×</button>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, duration);
  }
  window.showToast = showToast;

  // ============================================================
  // HELPERS
  // ============================================================
  function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  }
  function getMeta(name) {
    return document.querySelector(`meta[name="${name}"]`)?.content || '';
  }

  // ============================================================
  // SLIDERS (lightweight auto-slider)
  // ============================================================
  document.querySelectorAll('[data-slider]').forEach(slider => {
    const track    = slider.querySelector('.slider-track');
    const slides   = slider.querySelectorAll('.slide');
    const prevBtn  = slider.querySelector('.slider-prev');
    const nextBtn  = slider.querySelector('.slider-next');
    const dotsWrap = slider.querySelector('.slider-dots');
    if (!track || slides.length < 2) return;

    let current = 0;
    let autoplay;

    const dots = [];
    if (dotsWrap) {
      slides.forEach((_, i) => {
        const dot = document.createElement('button');
        dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
        dot.addEventListener('click', () => goto(i));
        dotsWrap.appendChild(dot);
        dots.push(dot);
      });
    }

    function goto(n) {
      slides[current].classList.remove('active');
      dots[current]?.classList.remove('active');
      current = (n + slides.length) % slides.length;
      slides[current].classList.add('active');
      dots[current]?.classList.add('active');
      track.style.transform = `translateX(-${current * 100}%)`;
    }

    prevBtn?.addEventListener('click', () => { clearInterval(autoplay); goto(current - 1); startAuto(); });
    nextBtn?.addEventListener('click', () => { clearInterval(autoplay); goto(current + 1); startAuto(); });
    slides[0].classList.add('active');

    function startAuto() {
      const interval = +(slider.dataset.interval || 5000);
      autoplay = setInterval(() => goto(current + 1), interval);
    }
    startAuto();
  });

  // ============================================================
  // STICKY BOOKING WIDGET
  // ============================================================
  const bookingWidget = document.querySelector('.booking-widget');
  if (bookingWidget) {
    const stickyTop = bookingWidget.offsetTop;
    window.addEventListener('scroll', () => {
      bookingWidget.style.top = window.scrollY > stickyTop
        ? `calc(var(--header-h) + 20px)` : '';
    }, { passive: true });
  }

  // ============================================================
  // PACKAGE CARD HOVER — quick view
  // ============================================================
  // (enhancement — placeholder for future quick-view modal)

  // ============================================================
  // INIT
  // ============================================================
  if (stepPanels.length) showStep(currentStep);
  updatePriceSummary();

  // Dismiss flash message automatically
  setTimeout(() => {
    document.querySelectorAll('.flash-msg').forEach(el => {
      el.style.transition = 'opacity .5s ease';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    });
  }, 5000);

  // ============================================================
  // HERO SLIDESHOW
  // ============================================================
  (function () {
    const slides = document.querySelectorAll('.hero-slide');
    const dots   = document.querySelectorAll('#heroDots .hero-dot');
    if (!slides.length) return;

    let current = 0;
    let timer;

    function goTo(n) {
      slides[current].classList.remove('active');
      dots[current] && dots[current].classList.remove('active');
      current = (n + slides.length) % slides.length;
      slides[current].classList.add('active');
      dots[current] && dots[current].classList.add('active');
      // Reset Ken Burns by removing/re-adding the img
      const img = slides[current].querySelector('img');
      if (img) { img.style.animation = 'none'; requestAnimationFrame(() => { img.style.animation = ''; }); }
    }

    function startAuto() { timer = setInterval(() => goTo(current + 1), 5000); }
    function stopAuto()  { clearInterval(timer); }

    dots.forEach((dot, i) => dot.addEventListener('click', () => { stopAuto(); goTo(i); startAuto(); }));

    // Pause on hover
    const heroEl = document.querySelector('.hero');
    if (heroEl) {
      heroEl.addEventListener('mouseenter', stopAuto);
      heroEl.addEventListener('mouseleave', startAuto);
    }
    startAuto();
  })();

  // ============================================================
  // FEATURED PACKAGES FILTER TABS (homepage)
  // ============================================================
  (function () {
    const tabs  = document.querySelectorAll('#pkgTabs .pkg-filter-tab');
    const cards = document.querySelectorAll('#packagesGrid .package-card');
    if (!tabs.length || !cards.length) return;

    tabs.forEach(tab => {
      tab.addEventListener('click', function () {
        tabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const filter = this.dataset.filter;
        let visible = 0;
        cards.forEach(card => {
          const match = filter === 'all' || card.dataset.cat === filter;
          if (match) {
            card.classList.remove('fade-out');
            card.style.display = '';
            visible++;
          } else {
            card.classList.add('fade-out');
            setTimeout(() => { if (card.classList.contains('fade-out')) card.style.display = 'none'; }, 310);
          }
        });
      });
    });
  })();

  // ============================================================
  // TESTIMONIALS CAROUSEL
  // ============================================================
  (function () {
    const track  = document.getElementById('testimonialTrack');
    const dots   = document.querySelectorAll('#testimonialDots .testimonial-dot');
    const btnPrev = document.getElementById('testimonialPrev');
    const btnNext = document.getElementById('testimonialNext');
    if (!track) return;

    const cards       = track.querySelectorAll('.testimonial-card');
    const perPage     = window.innerWidth < 580 ? 1 : window.innerWidth < 900 ? 2 : 3;
    const totalPages  = Math.ceil(cards.length / perPage);
    let   page        = 0;
    let   autoTimer;

    function updateTrack() {
      const cardW   = cards[0].offsetWidth + 16; // 16 = gap
      track.style.transform = `translateX(-${page * perPage * cardW}px)`;
      dots.forEach((d, i) => d.classList.toggle('active', i === page));
    }

    function goPage(n) {
      page = ((n % totalPages) + totalPages) % totalPages;
      updateTrack();
    }

    btnPrev && btnPrev.addEventListener('click', () => { goPage(page - 1); resetAuto(); });
    btnNext && btnNext.addEventListener('click', () => { goPage(page + 1); resetAuto(); });
    dots.forEach((d, i) => d.addEventListener('click', () => { goPage(i); resetAuto(); }));

    function resetAuto() { clearInterval(autoTimer); autoTimer = setInterval(() => goPage(page + 1), 4000); }
    resetAuto();

    // Swipe support
    let touchStart = 0;
    track.addEventListener('touchstart', e => { touchStart = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend',   e => {
      const dx = touchStart - e.changedTouches[0].clientX;
      if (Math.abs(dx) > 50) { goPage(dx > 0 ? page + 1 : page - 1); resetAuto(); }
    });
  })();

  // ============================================================
  // DUAL PRICE RANGE SLIDER (packages.php)
  // ============================================================
  (function () {
    const sliderMin  = document.getElementById('sliderMin');
    const sliderMax  = document.getElementById('sliderMax');
    const fill       = document.getElementById('priceFill');
    const label      = document.getElementById('priceLabel');
    const inputMin   = document.getElementById('minPriceInput');
    const inputMax   = document.getElementById('maxPriceInput');
    if (!sliderMin || !sliderMax) return;

    function updateSlider() {
      const min  = parseInt(sliderMin.value);
      const max  = parseInt(sliderMax.value);
      const range = parseInt(sliderMin.max) - parseInt(sliderMin.min);
      const left  = (min / range) * 100;
      const right = 100 - (max / range) * 100;

      if (min > max - 100) { sliderMin.value = max - 100; return updateSlider(); }

      fill.style.left  = left + '%';
      fill.style.right = right + '%';

      const fmt = v => v >= 10000 ? '$10,000+' : '$' + v.toLocaleString();
      label.innerHTML = `<strong>${fmt(min)} — ${fmt(max)}</strong>`;
      inputMin.value = min > 0      ? min : '';
      inputMax.value = max < 10000  ? max : '';
    }

    sliderMin.addEventListener('input', updateSlider);
    sliderMax.addEventListener('input', updateSlider);
    updateSlider();
  })();

  // ============================================================
  // PACKAGE COMPARISON (packages.php)
  // ============================================================
  (function () {
    const bar       = document.getElementById('compareBar');
    const badge     = document.getElementById('compareBadge');
    const slots     = [0,1,2].map(i => document.getElementById('cmpSlot' + i));
    const btnNow    = document.getElementById('compareNow');
    const btnClear  = document.getElementById('compareClear');
    const modal     = document.getElementById('compareModal');
    const modalClose = document.getElementById('compareModalClose');
    const table     = document.getElementById('compareTable');
    if (!bar) return;

    let selected = [];  // array of {id, title, price, duration, type, rating, img, url, dest}

    function getData(card) {
      return {
        id:       card.dataset.cmpId,
        title:    card.dataset.cmpTitle,
        price:    card.dataset.cmpPrice,
        duration: card.dataset.cmpDuration,
        type:     card.dataset.cmpType,
        rating:   card.dataset.cmpRating,
        img:      card.dataset.cmpImg,
        url:      card.dataset.cmpUrl,
        dest:     card.dataset.cmpDest,
      };
    }

    function renderBar() {
      badge.textContent = selected.length;
      slots.forEach((slot, i) => {
        const pkg = selected[i];
        if (pkg) {
          slot.innerHTML = pkg.img
            ? `<img src="${pkg.img}" alt="${pkg.title}"><button class="remove-slot" data-id="${pkg.id}" title="Remove"><i class="fas fa-times" style="font-size:.5rem"></i></button>`
            : `<div style="font-size:.6rem;padding:4px;color:#fff;text-align:center;line-height:1.2">${pkg.title.substring(0,20)}</div><button class="remove-slot" data-id="${pkg.id}" title="Remove"><i class="fas fa-times" style="font-size:.5rem"></i></button>`;
        } else {
          slot.innerHTML = '<i class="fas fa-plus"></i>';
        }
      });
      bar.classList.toggle('visible', selected.length >= 1);
      if (btnNow) btnNow.disabled = selected.length < 2;

      // Wire remove buttons
      bar.querySelectorAll('.remove-slot').forEach(btn => {
        btn.addEventListener('click', () => removeFromCompare(btn.dataset.id));
      });
    }

    function addToCompare(card) {
      if (selected.length >= 3) return;
      const data = getData(card);
      if (selected.find(s => s.id === data.id)) return;
      selected.push(data);
      card.classList.add('in-compare');
      renderBar();
    }

    function removeFromCompare(id) {
      selected = selected.filter(s => s.id !== id);
      document.querySelectorAll(`.cmp-check[data-id="${id}"]`).forEach(c => {
        c.checked = false;
        c.closest('.package-card')?.classList.remove('in-compare');
      });
      renderBar();
    }

    function clearCompare() {
      selected = [];
      document.querySelectorAll('.cmp-check').forEach(c => {
        c.checked = false;
        c.closest('.package-card')?.classList.remove('in-compare');
      });
      renderBar();
    }

    document.querySelectorAll('.cmp-check').forEach(chk => {
      chk.addEventListener('change', function () {
        const card = this.closest('.package-card');
        if (this.checked) {
          if (selected.length >= 3) { this.checked = false; return; }
          addToCompare(card);
        } else {
          removeFromCompare(this.dataset.id);
        }
      });
    });

    btnClear  && btnClear.addEventListener('click', clearCompare);
    modalClose && modalClose.addEventListener('click', () => modal.classList.remove('open'));
    modal     && modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });

    btnNow && btnNow.addEventListener('click', () => {
      if (selected.length < 2) return;

      const rows = [
        ['', ...selected.map(p => `
          <div>
            ${p.img ? `<img src="${p.img}" alt="${p.title}" class="compare-pkg-img">` : `<div style="height:120px;background:#f1f5f9;border-radius:var(--radius-md);margin-bottom:8px;display:grid;place-items:center"><i class="fas fa-image" style="font-size:2rem;color:#cbd5e0"></i></div>`}
            <div class="compare-pkg-name"><a href="${p.url}" style="color:var(--clr-primary)">${p.title}</a></div>
          </div>`)],
        ['Price',        ...selected.map(p => `<strong style="color:var(--clr-gold)">${p.price}</strong>`)],
        ['Destination',  ...selected.map(p => p.dest || '—')],
        ['Duration',     ...selected.map(p => p.duration)],
        ['Type',         ...selected.map(p => `<span style="text-transform:capitalize">${p.type}</span>`)],
        ['Rating',       ...selected.map(p => p.rating !== 'N/A' ? `<i class="fas fa-star" style="color:var(--clr-gold)"></i> ${p.rating}` : '—')],
        ['Book',         ...selected.map(p => `<a href="${p.url}" class="btn btn-primary btn-sm">View Package</a>`)],
      ];

      table.innerHTML = rows.map((row, ri) => `
        <tr>
          <th>${row[0]}</th>
          ${row.slice(1).map(cell => `<td>${cell}</td>`).join('')}
        </tr>`).join('');

      modal.classList.add('open');
    });
  })();

  // ============================================================
  // LIVE SEARCH OVERLAY
  // ============================================================
  const searchOverlay    = document.getElementById('searchOverlay');
  const overlayInput     = document.getElementById('overlaySearchInput');
  const searchDropdown   = document.getElementById('searchDropdown');
  const searchQuickLinks = document.getElementById('searchQuickLinks');
  const navSearchBtn     = document.getElementById('navSearchBtn');
  const closeSearchBtn   = document.getElementById('closeSearch');

  function openSearch() {
    if (!searchOverlay) return;
    searchOverlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => overlayInput && overlayInput.focus(), 80);
  }

  function closeSearch() {
    if (!searchOverlay) return;
    searchOverlay.style.display = 'none';
    document.body.style.overflow = '';
    if (searchDropdown) searchDropdown.style.display = 'none';
    if (overlayInput)   overlayInput.value = '';
  }

  if (navSearchBtn)  navSearchBtn.addEventListener('click', openSearch);
  if (closeSearchBtn) closeSearchBtn.addEventListener('click', closeSearch);
  if (searchOverlay) {
    searchOverlay.addEventListener('click', e => { if (e.target === searchOverlay) closeSearch(); });
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSearch(); });

  // Autocomplete
  let searchTimer;
  if (overlayInput) {
    overlayInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      const q = this.value.trim();
      if (q.length < 2) {
        if (searchDropdown) searchDropdown.style.display = 'none';
        if (searchQuickLinks) searchQuickLinks.style.display = 'flex';
        return;
      }
      if (searchQuickLinks) searchQuickLinks.style.display = 'none';
      searchTimer = setTimeout(() => fetchSearchResults(q), 280);
    });

    overlayInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        window.location.href = (window.APP_URL || '') + '/search.php?q=' + encodeURIComponent(this.value.trim());
      }
      // Arrow key navigation
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        const items = searchDropdown.querySelectorAll('a[data-result]');
        if (!items.length) return;
        const focused = searchDropdown.querySelector('a[data-result]:focus');
        const idx = [...items].indexOf(focused);
        e.preventDefault();
        if (e.key === 'ArrowDown') (items[idx + 1] || items[0]).focus();
        else (items[idx - 1] || items[items.length - 1]).focus();
      }
    });
  }

  async function fetchSearchResults(q) {
    if (!searchDropdown) return;
    searchDropdown.innerHTML = '<div style="padding:16px 20px;color:var(--clr-muted);font-size:.875rem"><i class="fas fa-spinner fa-spin"></i> Searching…</div>';
    searchDropdown.style.display = 'block';
    try {
      const res  = await fetch((window.APP_URL || '') + '/api/search.php?q=' + encodeURIComponent(q));
      const data = await res.json();
      renderSearchResults(data.results || [], q);
    } catch {
      searchDropdown.innerHTML = '<div style="padding:16px 20px;color:#ef4444;font-size:.875rem"><i class="fas fa-exclamation-circle"></i> Search unavailable</div>';
    }
  }

  function renderSearchResults(results, q) {
    if (!searchDropdown) return;
    if (!results.length) {
      searchDropdown.innerHTML = `
        <div style="padding:20px;text-align:center;color:var(--clr-muted)">
          <i class="fas fa-search-minus" style="font-size:1.5rem;margin-bottom:8px;display:block"></i>
          No results for "<strong>${q}</strong>"
          <br><a href="${(window.APP_URL||'')}/search.php?q=${encodeURIComponent(q)}" style="color:var(--clr-sky);font-size:.8rem;margin-top:6px;display:inline-block">Full search →</a>
        </div>`;
      return;
    }

    const groups = { package: [], destination: [], blog: [] };
    results.forEach(r => { if (groups[r.type]) groups[r.type].push(r); });

    const labels = { package: 'Packages', destination: 'Destinations', blog: 'Articles' };
    const icons  = { package: 'fa-suitcase', destination: 'fa-map-marker-alt', blog: 'fa-blog' };

    let html = '';
    for (const [type, items] of Object.entries(groups)) {
      if (!items.length) continue;
      html += `<div style="padding:8px 0 0;border-top:1px solid var(--clr-border)"><div style="padding:6px 16px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--clr-muted)"><i class="fas ${icons[type]}" style="margin-right:6px"></i>${labels[type]}</div>`;
      items.forEach(r => {
        const highlighted = r.title.replace(new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'), '<mark style="background:rgba(212,160,23,.25);border-radius:2px;padding:0 2px">$1</mark>');
        html += `<a href="${r.url}" data-result style="display:flex;align-items:center;gap:12px;padding:10px 16px;text-decoration:none;transition:background .15s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
          ${r.image ? `<img src="${r.image}" alt="" style="width:44px;height:36px;object-fit:cover;border-radius:4px;flex-shrink:0">` : `<div style="width:44px;height:36px;background:var(--clr-light);border-radius:4px;flex-shrink:0;display:grid;place-items:center"><i class="fas ${icons[type]}" style="color:var(--clr-muted);font-size:.75rem"></i></div>`}
          <div style="flex:1;min-width:0">
            <div style="font-size:.875rem;font-weight:500;color:var(--clr-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${highlighted}</div>
            <div style="font-size:.75rem;color:var(--clr-muted)">${r.meta || r.price || ''}</div>
          </div>
          ${r.price ? `<div style="font-size:.8rem;font-weight:600;color:var(--clr-gold);flex-shrink:0">${r.price}</div>` : ''}
        </a>`;
      });
      html += '</div>';
    }

    html += `<a href="${(window.APP_URL||'')}/search.php?q=${encodeURIComponent(q)}" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;background:var(--clr-light);color:var(--clr-primary);font-size:.85rem;font-weight:600;text-decoration:none;border-top:1px solid var(--clr-border)"><i class="fas fa-search"></i> See all results for "${q}"</a>`;
    searchDropdown.innerHTML = html;
  }

})();
