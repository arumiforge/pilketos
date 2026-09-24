/**
 * Visi-misi interaktif & art direction bergerak (Stage 2).
 *
 * Semua fitur di sini progressive enhancement: tanpa JavaScript, visi dan
 * seluruh misi tetap tampil utuh. Dengan prefers-reduced-motion, hanya
 * fitur non-gerak yang aktif (buka-tutup misi, penanda navigasi).
 *
 * - [data-reveal-words] : visi menyala kata demi kata mengikuti posisi scroll;
 * - [data-misi]         : panel misi dapat dibuka-tutup, item muncul bertahap;
 * - [data-parallax]     : lapisan panggung bergerak beda kecepatan (hanya bab
 *                         yang terlihat, satu rAF per frame);
 * - [data-tilt]         : kedalaman ringan mengikuti mouse (bukan layar sentuh);
 * - [data-chapter-nav]  : penanda bab aktif (aria-current);
 * - [data-journey]      : langkah memilih di pembuka (Stage 8): langkah aktif
 *                         maju saat surat suara tercapai, dan mengikuti event
 *                         "osis:journey" dari ballot.js (konfirmasi dibuka/batal).
 */
(function () {
  'use strict';

  var reduced = window.App && window.App.reducedMotion ? window.App.reducedMotion() : false;
  var hasIO = 'IntersectionObserver' in window;

  /* ---------- visi: kata demi kata ---------- */

  function splitWords(block) {
    var p = block.querySelector('p') || block;
    var words = p.textContent.trim().split(/\s+/);
    p.textContent = '';

    words.forEach(function (word, index) {
      var span = document.createElement('span');
      span.className = 'w';
      span.textContent = word;
      p.appendChild(span);
      if (index < words.length - 1) {
        p.appendChild(document.createTextNode(' '));
      }
    });

    return p.querySelectorAll('.w');
  }

  function initRevealWords(blocks) {
    var items = [];

    blocks.forEach(function (block) {
      items.push({ block: block, words: splitWords(block), lit: -1 });
    });

    return function update(viewportHeight) {
      items.forEach(function (item) {
        var rect = item.block.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > viewportHeight) {
          return;
        }

        // 0 saat blok baru masuk dari bawah (85% layar), 1 saat mencapai 35% layar.
        var startY = viewportHeight * 0.85;
        var endY = viewportHeight * 0.35;
        var progress = (startY - rect.top) / (startY - endY);
        progress = Math.min(1, Math.max(0, progress));

        var lit = Math.round(progress * item.words.length) - 1;
        if (lit === item.lit) {
          return;
        }
        item.lit = lit;

        for (var i = 0; i < item.words.length; i++) {
          item.words[i].classList.toggle('is-lit', i <= lit);
        }
      });
    };
  }

  /* ---------- misi: buka-tutup ---------- */

  function initMisi(panel) {
    var toggle = panel.querySelector('[data-misi-toggle]');
    if (!toggle) {
      return;
    }

    var label = toggle.querySelector('[data-misi-toggle-label]');
    var items = panel.querySelectorAll('.misi__item');

    function setOpen(open, animate) {
      panel.classList.toggle('is-collapsed', !open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      label.textContent = open ? 'Tutup misi' : 'Lihat semua misi';

      if (open && animate && !reduced) {
        for (var i = 1; i < items.length; i++) {
          items[i].classList.remove('is-entering');
          void items[i].offsetWidth;
          items[i].classList.add('is-entering');
        }
      }
    }

    toggle.hidden = false;
    setOpen(false, false);

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true', true);
    });

    // Sorotan interaktif: item yang disentuh/diklik menjadi aktif.
    panel.addEventListener('click', function (event) {
      var item = event.target.closest('.misi__item');
      if (!item) {
        return;
      }
      for (var i = 0; i < items.length; i++) {
        items[i].classList.toggle('is-active', items[i] === item && !item.classList.contains('is-active'));
      }
    });
  }

  /* ---------- parallax & reveal saat masuk layar ---------- */

  function initScrollEffects(chapters, updateWords) {
    var visible = [];
    var ticking = false;

    function frame() {
      ticking = false;
      var vh = window.innerHeight;

      visible.forEach(function (chapter) {
        var rect = chapter.getBoundingClientRect();
        var offset = (rect.top + rect.height / 2) - vh / 2;
        var layers = chapter.querySelectorAll('[data-parallax]');
        for (var i = 0; i < layers.length; i++) {
          var speed = parseFloat(layers[i].getAttribute('data-parallax')) || 0;
          // Dibatasi agar lapisan tidak keluar dari panggungnya.
          var shift = Math.max(-56, Math.min(56, offset * speed));
          layers[i].style.setProperty('--parallax', shift.toFixed(1));
        }
      });

      if (updateWords) {
        updateWords(vh);
      }
    }

    function request() {
      if (!ticking) {
        ticking = true;
        window.requestAnimationFrame(frame);
      }
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        var index = visible.indexOf(entry.target);
        if (entry.isIntersecting && index === -1) {
          visible.push(entry.target);
        } else if (!entry.isIntersecting && index !== -1) {
          visible.splice(index, 1);
        }
      });
      request();
    }, { rootMargin: '10% 0px' });

    chapters.forEach(function (chapter) {
      observer.observe(chapter);
    });

    window.addEventListener('scroll', request, { passive: true });
    window.addEventListener('resize', request);
    request();
  }

  function initRevealItems(root) {
    var items = root.querySelectorAll('[data-chapter] .misi__item');
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in');
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px' });

    for (var i = 0; i < items.length; i++) {
      observer.observe(items[i]);
    }
  }

  /* ---------- tilt (mouse) ---------- */

  function initTilt(elements) {
    if (!window.matchMedia || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      return;
    }

    elements.forEach(function (el) {
      var stage = el.closest('.chapter__stage') || el;

      stage.addEventListener('pointermove', function (event) {
        var rect = stage.getBoundingClientRect();
        var x = (event.clientX - rect.left) / rect.width - 0.5;
        var y = (event.clientY - rect.top) / rect.height - 0.5;
        el.style.setProperty('--tilt-y', (x * 8).toFixed(2) + 'deg');
        el.style.setProperty('--tilt-x', (y * -6).toFixed(2) + 'deg');
      });

      stage.addEventListener('pointerleave', function () {
        el.style.setProperty('--tilt-y', '0deg');
        el.style.setProperty('--tilt-x', '0deg');
      });
    });
  }

  /* ---------- navigasi bab ---------- */

  function initChapterNav(nav) {
    if (!nav || !hasIO) {
      return;
    }

    var links = nav.querySelectorAll('a[href^="#"]');
    var map = {};

    for (var i = 0; i < links.length; i++) {
      var id = links[i].getAttribute('href').slice(1);
      var target = document.getElementById(id);
      if (target) {
        map[id] = links[i];
      }
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }
        for (var key in map) {
          if (Object.prototype.hasOwnProperty.call(map, key)) {
            map[key].removeAttribute('aria-current');
          }
        }
        var link = map[entry.target.id];
        if (link) {
          link.setAttribute('aria-current', 'true');
        }
      });
    }, { rootMargin: '-45% 0px -50% 0px' });

    Object.keys(map).forEach(function (key) {
      observer.observe(document.getElementById(key));
    });
  }

  /* ---------- journey timeline: langkah memilih (Stage 8) ---------- */

  function initJourney(journey) {
    if (!journey) {
      return;
    }

    var steps = journey.querySelectorAll('[data-journey-step]');
    var current = 0;

    function setStep(index) {
      current = Math.max(0, Math.min(steps.length - 1, index));
      for (var i = 0; i < steps.length; i++) {
        steps[i].classList.toggle('is-done', i < current);
        steps[i].classList.toggle('is-current', i === current);
        if (i === current) {
          steps[i].setAttribute('aria-current', 'step');
        } else {
          steps[i].removeAttribute('aria-current');
        }
      }
    }

    // Surat suara tercapai: "Kenali paslon" selesai, "Coblos satu" aktif.
    var ballot = document.getElementById('surat-suara');
    if (ballot && hasIO) {
      var observer = new IntersectionObserver(function (entries) {
        for (var i = 0; i < entries.length; i++) {
          if (entries[i].isIntersecting) {
            if (current < 1) {
              setStep(1);
            }
            observer.disconnect();
            return;
          }
        }
      }, { rootMargin: '0px 0px -40% 0px' });
      observer.observe(ballot);
    }

    // ballot.js: kertas tercoblos -> "Konfirmasi & kunci"; batal -> kembali.
    document.addEventListener('osis:journey', function (event) {
      if (event.detail && typeof event.detail.step === 'number') {
        setStep(event.detail.step);
      }
    });
  }

  function init() {
    var chapters = Array.prototype.slice.call(document.querySelectorAll('[data-chapter]'));
    var misiPanels = document.querySelectorAll('[data-misi]');

    for (var i = 0; i < misiPanels.length; i++) {
      initMisi(misiPanels[i]);
    }

    initChapterNav(document.querySelector('[data-chapter-nav]'));
    initJourney(document.querySelector('[data-journey]'));

    if (reduced || !hasIO || chapters.length === 0) {
      return;
    }

    var updateWords = initRevealWords(Array.prototype.slice.call(document.querySelectorAll('[data-reveal-words]')));
    initRevealItems(document);
    initTilt(Array.prototype.slice.call(document.querySelectorAll('[data-tilt]')));
    initScrollEffects(chapters, updateWords);

    // Aktifkan gaya "belum menyala" hanya setelah efek siap (tanpa JS/IO teks tetap penuh).
    document.documentElement.classList.add('fx-ready');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
