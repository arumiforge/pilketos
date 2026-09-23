/**
 * SMP 1 DAWE — Pemilihan Ketua OSIS 2026
 * Beranda imersif (redesign beranda, STAGE5-NOTES.md).
 *
 * 1. Layar pembuka: bilah muat mengikuti asset yang benar-benar dimuat (font,
 *    logo, latar, gambar scene), tampil minimal ~1,5 detik dan paling lama
 *    ~5 detik; lalu logo "terbang" ke logo navigasi dan layar pembuka memudar
 *    ke beranda. Sekali per sesi tab (sessionStorage "osis2026.intro",
 *    penanda non-sensitif; dibaca juga oleh layouts/main.php).
 * 2. Scene layar penuh, satu per satu: roda mouse & trackpad (satu gestur =
 *    satu scene; inersia trackpad tidak melompati scene), geser sentuh,
 *    keyboard (panah, PageUp/PageDown, spasi, Home/End), navigasi scene,
 *    tautan "#", dan fokus Tab (scene mengikuti elemen yang difokus).
 *    Konten yang lebih tinggi dari layar (HP miring, zoom besar) digulir di
 *    dalam scene lebih dulu; scene berpindah di batas atas/bawahnya.
 * 3. Live count publik: GET live-count sesuai irama dari server (30 detik),
 *    dijeda saat tab tidak aktif, angka bergulir halus saat berubah, muat
 *    ulang bila status pemilihan berubah (server merender keadaan baru).
 * 4. Bidang titik interaktif di hero (canvas 2D: "surat suara berlubang"),
 *    paralaks pointer halus, portal Siswa/Guru yang mengikuti pointer.
 *
 * Semua gerak mengikuti prefers-reduced-motion. Teks dari server hanya
 * lewat textContent. Tanpa JavaScript beranda tetap halaman bergulir biasa.
 */
(function () {
  'use strict';

  var doc = document.documentElement;
  var body = document.body;
  var stage = document.querySelector('[data-scenes]');

  if (!stage || !window.requestAnimationFrame || !Element.prototype.closest) {
    return;
  }

  var reduced = window.App && window.App.reducedMotion ? window.App.reducedMotion() : false;
  var finePointer = !!(window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches);
  var INTRO_KEY = 'osis2026.intro';

  /* -- util ----------------------------------------------------------------- */
  function now() {
    return window.performance && window.performance.now ? window.performance.now() : Date.now();
  }

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function toArray(list) {
    return Array.prototype.slice.call(list);
  }

  function pad3(value) {
    var text = String(value);
    while (text.length < 3) {
      text = '0' + text;
    }
    return text;
  }

  function sessionSet(key, value) {
    try {
      window.sessionStorage.setItem(key, value);
    } catch (e) {
      /* penyimpanan diblokir: layar pembuka tampil lagi, tidak apa-apa */
    }
  }

  function currentHash() {
    try {
      return decodeURIComponent(window.location.hash.slice(1));
    } catch (e) {
      return '';
    }
  }

  /** Masih bisa digulir ke arah dir (1 = bawah, -1 = atas)? */
  function canScroll(el, dir) {
    if (!el || el.scrollHeight - el.clientHeight < 2) {
      return false;
    }
    return dir > 0 ? el.scrollTop + el.clientHeight < el.scrollHeight - 1 : el.scrollTop > 1;
  }

  function isTyping(el) {
    return !!el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName));
  }

  function activatesWithSpace(el) {
    return !!el && !!el.closest('button, a[href], summary, [role="button"], input, label');
  }

  function whenImage(img, done) {
    if (!img || (img.complete && img.naturalWidth > 0)) {
      done();
      return;
    }
    var finish = function () {
      img.removeEventListener('load', finish);
      img.removeEventListener('error', finish);
      done();
    };
    img.addEventListener('load', finish);
    img.addEventListener('error', finish);
  }

  /**
   * Tinggi navigasi & panel status yang sebenarnya (font, safe-area, dua baris
   * di HP) dipakai scene sebagai ruang atas/bawah: konten tidak pernah
   * tertutup. Disetel di <body> agar menimpa perkiraan CSS di .page-home.
   */
  function measureChrome() {
    var dock = document.querySelector('[data-dock]');
    var nav = document.querySelector('[data-site-nav]');
    var update = function () {
      if (dock) {
        body.style.setProperty('--dock-h', dock.offsetHeight + 'px');
      }
      if (nav) {
        body.style.setProperty('--nav-h', nav.offsetHeight + 'px');
      }
    };

    update();
    if (window.ResizeObserver) {
      var observer = new ResizeObserver(update);
      if (dock) {
        observer.observe(dock);
      }
      if (nav) {
        observer.observe(nav);
      }
    } else {
      window.addEventListener('resize', update);
    }
  }

  /* ==========================================================================
     Scene
     ========================================================================== */
  function Deck(root) {
    this.root = root;
    this.scenes = toArray(root.querySelectorAll('[data-scene]'));
    this.index = 0;
    this.busy = false;
    this.locked = false;
    this.pending = null;
    this.duration = reduced ? 0 : 1050;
    this.pager = toArray(document.querySelectorAll('[data-pager] [data-scene-link]'));
    this.announcer = document.querySelector('[data-scene-announce]');
    this.handlers = [];
  }

  Deck.prototype.indexOfId = function (id) {
    for (var i = 0; i < this.scenes.length; i++) {
      if (id !== '' && this.scenes[i].id === id) {
        return i;
      }
    }
    return -1;
  };

  Deck.prototype.indexOfElement = function (el) {
    var scene = el && el.closest ? el.closest('[data-scene]') : null;
    return scene ? this.scenes.indexOf(scene) : -1;
  };

  Deck.prototype.scroller = function () {
    return this.scenes[this.index].querySelector('[data-scene-scroll]');
  };

  Deck.prototype.onChange = function (handler) {
    this.handlers.push(handler);
  };

  Deck.prototype.start = function () {
    var hash = currentHash();
    var initial = this.indexOfId(hash);

    if (initial < 0 && hash !== '') {
      initial = this.indexOfElement(document.getElementById(hash));
    }
    this.index = Math.max(0, initial);

    // Urutan muncul bertahap di tiap scene.
    this.scenes.forEach(function (scene) {
      toArray(scene.querySelectorAll('[data-reveal]')).forEach(function (el, order) {
        el.style.setProperty('--order', order);
      });
    });

    // Posisi awal tanpa transisi, baru mode scene diaktifkan.
    var active = this.index;
    this.scenes.forEach(function (scene, i) {
      scene.classList.add('no-transition');
      scene.classList.toggle('is-active', i === active);
      scene.classList.toggle('is-before', i < active);
      scene.classList.toggle('is-after', i > active);
      scene.classList.remove('is-shown');
    });
    doc.classList.add('has-scenes');
    void this.root.offsetWidth;
    this.scenes.forEach(function (scene) {
      scene.classList.remove('no-transition');
    });

    // overflow: clip mencegah panggung ikut tergulir oleh fokus; pengaman lama:
    var root = this.root;
    root.addEventListener('scroll', function () {
      root.scrollTop = 0;
      root.scrollLeft = 0;
    });

    this.updateNav();
  };

  /** Munculkan isi scene aktif (setelah layar pembuka / saat pertama dibuka). */
  Deck.prototype.reveal = function () {
    this.scenes[this.index].classList.add('is-shown');
    this.emit();
  };

  Deck.prototype.updateNav = function () {
    var id = this.scenes[this.index].id;
    this.pager.forEach(function (link) {
      if (link.getAttribute('href') === '#' + id) {
        link.setAttribute('aria-current', 'true');
      } else {
        link.removeAttribute('aria-current');
      }
    });
    doc.setAttribute('data-scene', id);
  };

  Deck.prototype.emit = function () {
    var index = this.index;
    this.handlers.forEach(function (handler) {
      handler(index);
    });
  };

  Deck.prototype.announce = function (scene) {
    if (!this.announcer) {
      return;
    }
    var heading = document.getElementById(scene.getAttribute('aria-labelledby') || '');
    var label = heading ? heading.textContent.replace(/\s+/g, ' ').trim() : scene.id;
    this.announcer.textContent = 'Bagian ' + (this.scenes.indexOf(scene) + 1) + ' dari ' + this.scenes.length + ': ' + label;
  };

  Deck.prototype.writeHash = function (scene) {
    if (!window.history || !window.history.replaceState) {
      return;
    }
    // Scene pertama = alamat bersih; lainnya #id (muat ulang kembali ke sini).
    var url = this.scenes.indexOf(scene) === 0
      ? window.location.pathname + window.location.search
      : '#' + scene.id;
    try {
      window.history.replaceState(window.history.state, '', url);
    } catch (e) {
      /* abaikan */
    }
  };

  /**
   * Pindah ke scene target. opts: focus (pindahkan fokus ke scene), queue
   * (antrekan bila transisi sedang berjalan), fromFocus (dipicu Tab).
   */
  Deck.prototype.go = function (target, opts) {
    opts = opts || {};
    target = clamp(target, 0, this.scenes.length - 1);

    if (target === this.index) {
      return false;
    }
    if (this.busy) {
      if (opts.queue) {
        this.pending = { target: target, opts: opts };
      }
      return false;
    }

    var self = this;
    var from = this.scenes[this.index];
    var to = this.scenes[target];
    var forward = target > this.index;
    var focusWasInside = from.contains(document.activeElement);

    this.busy = true;

    // Scene lain (bila melompat) langsung ke sisi istirahatnya, tanpa animasi.
    this.scenes.forEach(function (scene, i) {
      if (scene === from || scene === to) {
        return;
      }
      scene.classList.add('no-transition');
      scene.classList.remove('is-active', 'is-shown');
      scene.classList.toggle('is-before', i < target);
      scene.classList.toggle('is-after', i > target);
    });

    // Scene tujuan mulai dari sisi datangnya; isi mulai dari tepi yang sesuai.
    to.classList.add('no-transition');
    to.classList.remove('is-active', 'is-shown', 'is-dragging', 'is-settling');
    to.classList.toggle('is-after', forward);
    to.classList.toggle('is-before', !forward);
    var toScroller = to.querySelector('[data-scene-scroll]');
    if (toScroller) {
      toScroller.scrollTop = forward ? 0 : toScroller.scrollHeight;
      // Dipicu Tab: elemen yang difokus tetap terlihat di dalam scene.
      if (opts.fromFocus && toScroller.contains(document.activeElement)) {
        document.activeElement.scrollIntoView({ block: 'nearest', inline: 'nearest' });
      }
    }
    void to.offsetWidth;
    this.scenes.forEach(function (scene) {
      scene.classList.remove('no-transition');
    });

    // Maju: scene baru meluncur di atas; mundur: scene lama turun di atas.
    from.style.zIndex = forward ? '1' : '3';
    to.style.zIndex = forward ? '3' : '1';

    from.classList.remove('is-active', 'is-dragging', 'is-settling');
    from.style.removeProperty('--drag');
    from.classList.toggle('is-before', forward);
    from.classList.toggle('is-after', !forward);
    to.classList.remove('is-before', 'is-after');
    to.classList.add('is-active');

    this.index = target;
    this.updateNav();
    this.writeHash(to);

    window.setTimeout(function () {
      to.classList.add('is-shown');
    }, reduced ? 0 : Math.round(this.duration * 0.3));

    window.setTimeout(function () {
      from.classList.remove('is-shown');
      from.style.zIndex = '';
      to.style.zIndex = '';
      self.busy = false;
      self.emit();

      if (self.pending) {
        var next = self.pending;
        self.pending = null;
        self.go(next.target, next.opts);
      }
    }, this.duration + 60);

    if (!opts.fromFocus) {
      if (opts.focus || focusWasInside) {
        try {
          to.focus({ preventScroll: true });
        } catch (e) {
          to.focus();
        }
      } else {
        this.announce(to);
      }
    }

    return true;
  };

  Deck.prototype.step = function (dir, opts) {
    var target = this.index + dir;
    if (target < 0 || target >= this.scenes.length) {
      this.bump(dir);
      return false;
    }
    return this.go(target, opts);
  };

  /** Umpan balik di scene pertama/terakhir: tidak ada scene lagi. */
  Deck.prototype.bump = function (dir) {
    if (reduced || this.busy) {
      return;
    }
    var scene = this.scenes[this.index];
    var name = dir > 0 ? 'is-bump-up' : 'is-bump-down';
    scene.classList.remove('is-bump-up', 'is-bump-down');
    void scene.offsetWidth;
    scene.classList.add(name);
    window.setTimeout(function () {
      scene.classList.remove(name);
    }, 650);
  };

  /** Seret sentuh: scene aktif ikut jari dengan hambatan (terasa seperti app). */
  Deck.prototype.drag = function (dy) {
    if (reduced) {
      return;
    }
    var last = this.scenes.length - 1;
    var edge = (dy < 0 && this.index === last) || (dy > 0 && this.index === 0);
    var scene = this.scenes[this.index];
    scene.classList.add('is-dragging');
    scene.style.setProperty('--drag', clamp(dy * (edge ? 0.16 : 0.3), -90, 90).toFixed(1) + 'px');
  };

  Deck.prototype.settle = function (scene) {
    if (!scene.classList.contains('is-dragging')) {
      return;
    }
    scene.classList.remove('is-dragging');
    scene.classList.add('is-settling');
    scene.style.removeProperty('--drag');
    window.setTimeout(function () {
      scene.classList.remove('is-settling');
    }, 520);
  };

  Deck.prototype.bind = function () {
    var self = this;

    /* -- roda mouse & trackpad ---------------------------------------------- */
    var wheel = { last: 0, dir: 0, acc: 0, consumed: false, inner: false, recent: [] };

    // Gestur baru di tengah inersia: delta membesar lagi (bukan ekor inersia).
    function accelerating(list) {
      if (list.length < 6) {
        return false;
      }
      var n = list.length;
      var recent = (list[n - 1] + list[n - 2] + list[n - 3]) / 3;
      var before = (list[n - 4] + list[n - 5] + list[n - 6]) / 3;
      return list[n - 1] > 12 && recent > before * 1.3;
    }

    window.addEventListener('wheel', function (event) {
      if (event.ctrlKey || event.defaultPrevented) {
        return; // pinch-zoom trackpad
      }

      var dy = event.deltaY;
      var dx = event.deltaX;
      if (event.deltaMode === 1) {
        dy *= 16;
        dx *= 16;
      } else if (event.deltaMode === 2) {
        dy *= window.innerHeight;
        dx *= window.innerWidth;
      }
      if (dy === 0 || Math.abs(dx) > Math.abs(dy) || (event.target.closest && event.target.closest('dialog'))) {
        return;
      }

      var dir = dy > 0 ? 1 : -1;
      var t = now();
      var scroller = self.scroller();

      if (t - wheel.last > 200 || dir !== wheel.dir) {
        wheel.dir = dir;
        wheel.acc = 0;
        wheel.consumed = false;
        wheel.recent = [];
        // Gestur yang dimulai saat isi scene masih bisa digulir = milik isi scene.
        wheel.inner = canScroll(scroller, dir);
      }
      wheel.last = t;
      wheel.recent.push(Math.abs(dy));
      if (wheel.recent.length > 12) {
        wheel.recent.shift();
      }

      if (self.locked) {
        event.preventDefault();
        return;
      }

      if (wheel.inner) {
        if (scroller && !scroller.contains(event.target)) {
          // mis. roda di atas panel status: gulir isi scene aktif
          event.preventDefault();
          scroller.scrollTop += dy;
        }
        return;
      }

      event.preventDefault();

      if (self.busy) {
        wheel.consumed = true;
        return;
      }
      if (wheel.consumed) {
        if (!accelerating(wheel.recent)) {
          return;
        }
        wheel.consumed = false;
        wheel.acc = 0;
      }

      wheel.acc += Math.abs(dy);
      if (wheel.acc >= 40) {
        wheel.consumed = true;
        wheel.acc = 0;
        self.step(dir);
      }
    }, { passive: false });

    /* -- sentuh --------------------------------------------------------------- */
    var touch = null;

    window.addEventListener('touchstart', function (event) {
      if (event.touches.length !== 1 || self.locked) {
        touch = null;
        return;
      }
      var point = event.touches[0];
      var scroller = self.scroller();
      touch = {
        x: point.clientX,
        y: point.clientY,
        t: now(),
        dy: 0,
        axis: null,
        scene: self.scenes[self.index],
        canUp: canScroll(scroller, -1),
        canDown: canScroll(scroller, 1)
      };
    }, { passive: true });

    window.addEventListener('touchmove', function (event) {
      if (!touch || event.touches.length !== 1) {
        return;
      }
      var point = event.touches[0];
      var dx = point.clientX - touch.x;
      var dy = point.clientY - touch.y;

      if (touch.axis === null) {
        if (Math.abs(dx) < 8 && Math.abs(dy) < 8) {
          return;
        }
        touch.axis = Math.abs(dy) >= Math.abs(dx) ? 'y' : 'x';
      }
      if (touch.axis !== 'y') {
        return;
      }

      // Isi scene masih bisa digulir ke arah ini: biarkan gulir bawaan.
      if (dy < 0 ? touch.canDown : touch.canUp) {
        return;
      }
      if (event.cancelable) {
        event.preventDefault(); // tanpa pantulan / tarik-untuk-muat-ulang
      }
      touch.dy = dy;
      if (!self.busy) {
        self.drag(dy);
      }
    }, { passive: false });

    var endTouch = function () {
      if (!touch) {
        return;
      }
      var dy = touch.dy;
      var speed = Math.abs(dy) / Math.max(1, now() - touch.t);
      var scene = touch.scene;
      var moved = false;
      touch = null;

      if (dy !== 0 && !self.busy && (Math.abs(dy) > Math.min(90, window.innerHeight * 0.12) || (speed > 0.45 && Math.abs(dy) > 24))) {
        moved = self.step(dy < 0 ? 1 : -1);
      }
      if (!moved) {
        self.settle(scene);
      }
    };

    window.addEventListener('touchend', endTouch);
    window.addEventListener('touchcancel', endTouch);

    /* -- keyboard ------------------------------------------------------------ */
    document.addEventListener('keydown', function (event) {
      if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey || self.locked) {
        return;
      }
      if (isTyping(event.target) || document.querySelector('dialog[open]')) {
        return;
      }

      var dir = 0;
      var page = false;

      switch (event.key) {
        case 'ArrowDown':
        case 'Down':
          dir = 1;
          break;
        case 'ArrowUp':
        case 'Up':
          dir = -1;
          break;
        case 'PageDown':
          dir = 1;
          page = true;
          break;
        case 'PageUp':
          dir = -1;
          page = true;
          break;
        case ' ':
        case 'Spacebar':
          if (activatesWithSpace(event.target)) {
            return;
          }
          dir = event.shiftKey ? -1 : 1;
          page = true;
          break;
        case 'Home':
          event.preventDefault();
          self.go(0, { focus: true });
          return;
        case 'End':
          event.preventDefault();
          self.go(self.scenes.length - 1, { focus: true });
          return;
        default:
          return;
      }

      event.preventDefault();
      var scroller = self.scroller();
      if (canScroll(scroller, dir)) {
        scroller.scrollBy({
          top: dir * (page ? scroller.clientHeight * 0.85 : 80),
          behavior: reduced ? 'auto' : 'smooth'
        });
        return;
      }
      if (!self.busy) {
        self.step(dir, { focus: true });
      }
    });

    /* -- tautan "#scene" (CTA, navigasi scene, kembali ke awal) ---------------- */
    document.addEventListener('click', function (event) {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }
      var link = event.target.closest ? event.target.closest('a[href^="#"]') : null;
      if (!link) {
        return;
      }
      var id = '';
      try {
        id = decodeURIComponent(link.getAttribute('href').slice(1));
      } catch (e) {
        return;
      }
      var index = self.indexOfId(id);
      if (index < 0) {
        return; // mis. tautan lewati ke konten (#main)
      }
      event.preventDefault();

      if (index === self.index) {
        var scroller = self.scroller();
        if (scroller && scroller.scrollTop > 0) {
          scroller.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
        }
        return;
      }
      self.go(index, { focus: true, queue: true });
    });

    /* -- fokus Tab masuk ke scene lain: scene ikut pindah --------------------- */
    document.addEventListener('focusin', function (event) {
      var index = self.indexOfElement(event.target);
      if (index >= 0 && index !== self.index) {
        self.go(index, { queue: true, fromFocus: true });
      }
    });

    window.addEventListener('hashchange', function () {
      var index = self.indexOfId(currentHash());
      if (index >= 0 && index !== self.index) {
        self.go(index, { focus: true, queue: true });
      }
    });
  };

  /* ==========================================================================
     Layar pembuka
     ========================================================================== */
  function Intro(el) {
    this.el = el;
    this.fill = el.querySelector('[data-splash-fill]');
    this.pct = el.querySelector('[data-splash-pct]');
    this.logo = el.querySelector('[data-splash-logo]');
    this.bg = el.querySelector('[data-splash-bg]');
    this.shown = 0;
    this.loaded = 0;
    this.total = 0;
    this.finished = false;
    this.startedAt = now();
  }

  Intro.MIN = 1500;
  Intro.MAX = 5200;

  Intro.prototype.run = function (done) {
    var self = this;
    var waits = [];

    this.done = done;
    this.el.classList.add('is-managed');
    doc.classList.add('intro-active');

    if (this.bg) {
      whenImage(this.bg, function () {
        if (self.bg.naturalWidth > 0) {
          self.bg.classList.add('is-loaded');
        }
      });
    }

    // Yang ditunggu: logo, latar, gambar scene, font, dan halaman selesai dimuat.
    [this.logo, this.bg].concat(toArray(document.querySelectorAll('[data-nav-logo], [data-preload]'))).forEach(function (img) {
      if (img) {
        waits.push(function (next) {
          whenImage(img, next);
        });
      }
    });
    if (document.fonts && document.fonts.ready) {
      waits.push(function (next) {
        document.fonts.ready.then(next, next);
      });
    }
    waits.push(function (next) {
      if (document.readyState === 'complete') {
        next();
      } else {
        window.addEventListener('load', next);
      }
    });

    this.total = waits.length;
    waits.forEach(function (wait) {
      var called = false;
      wait(function () {
        if (!called) {
          called = true;
          self.loaded++;
        }
      });
    });

    var tick = function () {
      if (self.finished) {
        return;
      }
      var elapsed = now() - self.startedAt;
      var real = self.total > 0 ? self.loaded / self.total : 1;
      var paced = reduced ? 1 : Math.min(1, elapsed / Intro.MIN);
      var goal = elapsed >= Intro.MAX ? 1 : Math.min(real, paced);

      self.shown += (goal - self.shown) * (reduced ? 1 : 0.12);
      if (goal - self.shown < 0.003) {
        self.shown = goal;
      }
      self.render();

      if (self.shown >= 1 && elapsed >= (reduced ? 250 : Intro.MIN)) {
        self.finish();
        return;
      }
      window.requestAnimationFrame(tick);
    };
    window.requestAnimationFrame(tick);
  };

  Intro.prototype.render = function () {
    if (this.fill) {
      this.fill.style.setProperty('--p', this.shown.toFixed(4));
    }
    if (this.pct) {
      this.pct.textContent = pad3(Math.round(this.shown * 100));
    }
  };

  Intro.prototype.finish = function () {
    var self = this;
    var navLogo = document.querySelector('[data-nav-logo]');
    var duration = reduced ? 200 : 1000;

    this.finished = true;
    sessionSet(INTRO_KEY, '1');

    if (!reduced && this.logo && navLogo) {
      var a = this.logo.getBoundingClientRect();
      var b = navLogo.getBoundingClientRect();

      if (a.width > 0 && b.width > 0) {
        // Logo pembuka "mendarat" tepat di logo navigasi (shared element).
        this.logo.style.animation = 'none';
        this.logo.style.opacity = '1';
        this.logo.style.transform = 'none';
        void this.logo.offsetWidth;
        this.el.classList.add('is-leaving');
        this.logo.style.transform = 'translate3d(' +
          ((b.left + b.width / 2) - (a.left + a.width / 2)).toFixed(1) + 'px,' +
          ((b.top + b.height / 2) - (a.top + a.height / 2)).toFixed(1) + 'px,0) scale(' +
          (b.width / a.width).toFixed(4) + ')';
      }
    }

    if (!this.el.classList.contains('is-leaving')) {
      this.el.classList.add('is-leaving');
      if (this.logo) {
        this.logo.style.transition = 'opacity 0.2s linear';
        this.logo.style.opacity = '0';
      }
    }

    window.setTimeout(function () {
      self.done();
    }, reduced ? 0 : 260);

    window.setTimeout(function () {
      doc.classList.remove('intro-active');
      window.setTimeout(function () {
        if (self.el.parentNode) {
          self.el.parentNode.removeChild(self.el);
        }
      }, 80);
    }, duration);
  };

  /* ==========================================================================
     Live count publik
     ========================================================================== */
  function Live(el) {
    var self = this;
    this.el = el;
    this.url = el.getAttribute('data-live-url');
    this.status = el.getAttribute('data-live-status') || '';
    this.interval = parseInt(el.getAttribute('data-live-interval'), 10) || 0;
    this.timer = null;
    this.busy = false;
    this.stopped = false;
    this.failures = 0;
    this.pairs = {};
    this.fmtPct = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
    this.fmtInt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

    toArray(el.querySelectorAll('[data-live-pair]')).forEach(function (item) {
      var num = item.querySelector('[data-live-pct]');
      if (num) {
        self.pairs[item.getAttribute('data-live-pair')] = {
          item: item,
          el: num,
          bar: item.querySelector('[data-live-bar]'),
          value: parseFloat(num.getAttribute('data-value')) || 0,
          raf: 0
        };
      }
    });

    var turnout = el.querySelector('[data-live-turnout]');
    this.turnout = turnout ? { el: turnout, value: parseFloat(turnout.getAttribute('data-value')) || 0, raf: 0 } : null;
    this.meter = el.querySelector('[data-live-meter]');
    this.voted = el.querySelector('[data-live-voted]');
    this.total = el.querySelector('[data-live-total]');
    this.updated = el.querySelector('[data-live-updated]');
    this.announcer = el.querySelector('[data-live-announce]');
  }

  Live.prototype.start = function () {
    var self = this;

    document.addEventListener('visibilitychange', function () {
      if (self.stopped) {
        return;
      }
      if (document.hidden) {
        window.clearTimeout(self.timer);
        self.timer = null;
      } else if (self.interval > 0) {
        self.tick();
      }
    });

    window.addEventListener('pageshow', function (event) {
      if (event.persisted && !self.stopped && self.interval > 0) {
        self.tick();
      }
    });

    this.schedule(this.interval);
  };

  Live.prototype.schedule = function (seconds) {
    window.clearTimeout(this.timer);
    this.timer = null;
    if (!this.stopped && seconds > 0 && !document.hidden) {
      this.timer = window.setTimeout(this.tick.bind(this), seconds * 1000);
    }
  };

  Live.prototype.tick = function () {
    if (this.busy || this.stopped || !window.fetch) {
      return;
    }
    var self = this;
    var controller = window.AbortController ? new AbortController() : null;
    var abortTimer = window.setTimeout(function () {
      if (controller) {
        controller.abort();
      }
    }, 10000);

    this.busy = true;
    window.fetch(this.url, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      signal: controller ? controller.signal : undefined
    }).then(function (response) {
      if (response.status === 404) {
        self.stopped = true; // live count publik dimatikan panitia
        return null;
      }
      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }
      return response.json();
    }).then(function (data) {
      if (!data) {
        return;
      }
      self.failures = 0;

      // Status berubah (dibuka/ditutup) atau susunan pasangan berubah:
      // biarkan server merender keadaan baru.
      if ((data.status || '') !== self.status || !self.samePairs(data.candidates || [])) {
        self.reload(data);
        return;
      }

      self.render(data);
      self.interval = data.poll && typeof data.poll.interval === 'number' ? data.poll.interval : 0;
      self.schedule(self.interval);
    }).catch(function () {
      self.failures++;
      self.schedule(Math.min(120, Math.max(self.interval, 10) * Math.pow(2, Math.min(self.failures, 3))));
    }).then(function () {
      window.clearTimeout(abortTimer);
      self.busy = false;
    });
  };

  Live.prototype.samePairs = function (candidates) {
    var ids = Object.keys(this.pairs);
    if (candidates.length !== ids.length) {
      return false;
    }
    for (var i = 0; i < candidates.length; i++) {
      if (!this.pairs[String(candidates[i].id)]) {
        return false;
      }
    }
    return true;
  };

  Live.prototype.reload = function (data) {
    this.stopped = true;
    if (this.announcer) {
      this.announcer.textContent = data.status === 'FINISHED'
        ? 'Pemilihan telah selesai. Halaman dimuat ulang.'
        : 'Status pemilihan berubah. Halaman dimuat ulang.';
    }
    window.setTimeout(function () {
      window.location.reload();
    }, 1500);
  };

  Live.prototype.render = function (data) {
    var self = this;

    (data.candidates || []).forEach(function (candidate) {
      var pair = self.pairs[String(candidate.id)];
      if (!pair) {
        return;
      }
      var value = Number(candidate.percent) || 0;
      if (Math.abs(value - pair.value) >= 0.005) {
        self.tween(pair, value);
        pair.item.classList.remove('is-changed');
        void pair.item.offsetWidth;
        pair.item.classList.add('is-changed');
      }
      if (pair.bar) {
        pair.bar.style.setProperty('--share', clamp(value, 0, 100).toFixed(2));
      }
    });

    if (data.turnout) {
      var share = Number(data.turnout.percent) || 0;
      if (this.turnout && Math.abs(share - this.turnout.value) >= 0.005) {
        this.tween(this.turnout, share);
      }
      if (this.meter) {
        this.meter.style.setProperty('--share', clamp(share, 0, 100).toFixed(2));
      }
      if (this.voted) {
        this.voted.textContent = this.fmtInt.format(Number(data.turnout.voted) || 0);
      }
      if (this.total) {
        this.total.textContent = this.fmtInt.format(Number(data.turnout.total) || 0);
      }
    }

    if (this.updated && data.updated_label) {
      this.updated.textContent = String(data.updated_label);
      if (data.updated_at) {
        this.updated.setAttribute('datetime', String(data.updated_at));
      }
    }
  };

  /** Angka bergulir halus dari nilai lama ke nilai baru. */
  Live.prototype.tween = function (target, value) {
    var fmt = this.fmtPct;
    var from = target.value;

    target.value = value;
    target.el.setAttribute('data-value', value.toFixed(2));
    window.cancelAnimationFrame(target.raf);

    if (reduced || document.hidden) {
      target.el.textContent = fmt.format(value);
      return;
    }

    var start = null;
    var frame = function (t) {
      if (start === null) {
        start = t;
      }
      var k = Math.min(1, (t - start) / 1200);
      var eased = 1 - Math.pow(1 - k, 3);
      target.el.textContent = fmt.format(from + (value - from) * eased);
      target.raf = k < 1 ? window.requestAnimationFrame(frame) : 0;
    };
    target.raf = window.requestAnimationFrame(frame);
  };

  /* ==========================================================================
     Bidang titik hero: kisi "surat suara berlubang" yang tertekan pointer
     (seperti paku menekan kertas) dan beriak saat ditusuk (klik/ketuk).
     ========================================================================== */
  function Field(canvas, scene) {
    this.canvas = canvas;
    this.scene = scene;
    this.ctx = canvas.getContext('2d');
    this.pointer = { x: -1e4, y: -1e4, tx: -1e4, ty: -1e4, on: false };
    this.ripples = [];
    this.active = false;
    this.visible = !document.hidden;
    this.raf = 0;
    this.last = 0;
    this.idle = finePointer && !reduced && (navigator.hardwareConcurrency || 4) >= 4;
    this.frame = this.frame.bind(this);
  }

  Field.prototype.init = function () {
    var self = this;
    var resizeTimer = null;

    this.resize();
    this.canvas.classList.add('is-on');

    if (reduced) {
      return; // bidang diam, tanpa interaksi
    }

    var move = function (event) {
      var rect = self.rect;
      self.pointer.tx = event.clientX - rect.left;
      self.pointer.ty = event.clientY - rect.top;
      if (!self.pointer.on) {
        self.pointer.x = self.pointer.tx;
        self.pointer.y = self.pointer.ty;
      }
      self.pointer.on = true;
      self.kick();
    };

    this.scene.addEventListener('pointermove', move);
    this.scene.addEventListener('pointerdown', function (event) {
      move(event);
      self.ripples.push({ x: self.pointer.tx, y: self.pointer.ty, t0: now() });
      if (self.ripples.length > 4) {
        self.ripples.shift();
      }
      self.kick();
    });
    this.scene.addEventListener('pointerleave', function () {
      self.pointer.on = false;
      self.pointer.tx = self.pointer.ty = -1e4;
      self.kick();
    });

    window.addEventListener('resize', function () {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(function () {
        self.resize();
      }, 150);
    });

    document.addEventListener('visibilitychange', function () {
      self.visible = !document.hidden;
      self.kick();
    });
  };

  /**
   * Ukuran dari kotak layout (clientWidth/Height), bukan getBoundingClientRect:
   * hero bisa sedang "mundur" (transform) saat dibuka lewat #scene lain.
   */
  Field.prototype.resize = function () {
    var width = this.canvas.clientWidth;
    var height = this.canvas.clientHeight;
    var dpr = Math.min(window.devicePixelRatio || 1, 2);

    this.rect = this.canvas.getBoundingClientRect();
    if (width === this.w && height === this.h && dpr === this.dpr) {
      return;
    }

    this.w = width;
    this.h = height;
    this.dpr = dpr;
    this.canvas.width = Math.max(1, Math.round(width * dpr));
    this.canvas.height = Math.max(1, Math.round(height * dpr));
    this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    this.gap = width < 640 ? 24 : 28;
    this.cols = Math.floor(width / this.gap) + 2;
    this.rows = Math.floor(height / this.gap) + 2;
    this.ox = (width - (this.cols - 1) * this.gap) / 2;
    this.oy = (height - (this.rows - 1) * this.gap) / 2;
    this.draw(now());
  };

  Field.prototype.setActive = function (active) {
    this.active = active;
    if (active && this.w !== undefined) {
      this.resize(); // posisi pointer & ukuran saat hero kembali tampil penuh
    }
    this.kick();
  };

  Field.prototype.kick = function () {
    if (!this.raf && this.active && this.visible && !reduced) {
      this.raf = window.requestAnimationFrame(this.frame);
    }
  };

  Field.prototype.frame = function (t) {
    this.raf = 0;
    if (!this.active || !this.visible) {
      return;
    }

    var p = this.pointer;
    var ripples = this.ripples;
    var settling = p.on && (Math.abs(p.tx - p.x) > 0.5 || Math.abs(p.ty - p.y) > 0.5);

    p.x += (p.tx - p.x) * 0.2;
    p.y += (p.ty - p.y) * 0.2;
    if (!p.on) {
      p.x = p.tx;
      p.y = p.ty;
    }

    for (var i = ripples.length - 1; i >= 0; i--) {
      if (t - ripples[i].t0 > 1400) {
        ripples.splice(i, 1);
      }
    }

    var busy = p.on || ripples.length > 0 || settling;
    // Tanpa interaksi: gelombang diam ~30 fps agar hemat baterai.
    if (!busy && this.idle && t - this.last < 32) {
      this.raf = window.requestAnimationFrame(this.frame);
      return;
    }

    this.last = t;
    this.draw(t);

    if (busy || this.idle) {
      this.raf = window.requestAnimationFrame(this.frame);
    }
  };

  Field.prototype.draw = function (t) {
    var ctx = this.ctx;
    var gap = this.gap;
    var p = this.pointer;
    var radius = Math.min(170, Math.max(110, this.w * 0.16));
    var radius2 = radius * radius;
    var time = t * 0.001;
    var waves = [];

    this.ripples.forEach(function (r) {
      var age = (t - r.t0) / 1400;
      waves.push({ x: r.x, y: r.y, radius: age * 520, strength: 1 - age });
    });

    ctx.clearRect(0, 0, this.w, this.h);
    ctx.fillStyle = '#F4F2EC';

    for (var j = 0; j < this.rows; j++) {
      var y0 = this.oy + j * gap;

      for (var i = 0; i < this.cols; i++) {
        var x0 = this.ox + i * gap;
        var x = x0;
        var y = y0;
        var glow = 0;

        if (this.idle) {
          y += Math.sin(x0 * 0.011 + time * 0.8) * Math.cos(y0 * 0.013 + time * 0.6) * 1.8;
        }

        var dx = x0 - p.x;
        var dy = y0 - p.y;
        var d2 = dx * dx + dy * dy;
        if (d2 < radius2) {
          var d = Math.sqrt(d2) || 1;
          var f = 1 - d / radius;
          f *= f;
          x += dx / d * f * 18;
          y += dy / d * f * 18;
          glow = f;
        }

        for (var k = 0; k < waves.length; k++) {
          var w = waves[k];
          var wx = x0 - w.x;
          var wy = y0 - w.y;
          var wd = Math.sqrt(wx * wx + wy * wy) || 1;
          var band = 1 - Math.abs(wd - w.radius) / 36;
          if (band > 0) {
            var amp = band * w.strength;
            x += wx / wd * amp * 9;
            y += wy / wd * amp * 9;
            glow = Math.max(glow, amp);
          }
        }

        var size = 1.3 + glow * 2.4;
        ctx.globalAlpha = 0.2 + glow * 0.65;
        ctx.fillRect(x - size / 2, y - size / 2, size, size);
      }
    }
    ctx.globalAlpha = 1;
  };

  /* ==========================================================================
     Paralaks pointer & portal
     ========================================================================== */
  function initParallax(root) {
    if (!finePointer || reduced) {
      return;
    }
    var target = { x: 0, y: 0 };
    var current = { x: 0, y: 0 };
    var raf = 0;

    var frame = function () {
      current.x += (target.x - current.x) * 0.07;
      current.y += (target.y - current.y) * 0.07;
      root.style.setProperty('--mx', current.x.toFixed(4));
      root.style.setProperty('--my', current.y.toFixed(4));
      raf = Math.abs(target.x - current.x) > 0.001 || Math.abs(target.y - current.y) > 0.001
        ? window.requestAnimationFrame(frame)
        : 0;
    };

    window.addEventListener('pointermove', function (event) {
      if (event.pointerType !== 'mouse') {
        return;
      }
      target.x = clamp(event.clientX / window.innerWidth * 2 - 1, -1, 1);
      target.y = clamp(event.clientY / window.innerHeight * 2 - 1, -1, 1);
      if (!raf) {
        raf = window.requestAnimationFrame(frame);
      }
    });
  }

  function initPortals() {
    if (!finePointer || reduced) {
      return;
    }
    toArray(document.querySelectorAll('[data-portal]')).forEach(function (portal) {
      portal.addEventListener('pointermove', function (event) {
        var rect = portal.getBoundingClientRect();
        portal.style.setProperty('--px', ((event.clientX - rect.left) / rect.width - 0.5).toFixed(3));
        portal.style.setProperty('--py', ((event.clientY - rect.top) / rect.height - 0.5).toFixed(3));
      });
      portal.addEventListener('pointerleave', function () {
        portal.style.setProperty('--px', '0');
        portal.style.setProperty('--py', '0');
      });
    });
  }

  /* ==========================================================================
     Mulai
     ========================================================================== */
  function init() {
    measureChrome();

    var deck = new Deck(stage);
    deck.start();
    deck.bind();

    var canvas = stage.querySelector('[data-field]');
    var field = null;
    if (canvas && canvas.getContext) {
      field = new Field(canvas, canvas.closest('[data-scene]'));
      deck.onChange(function (index) {
        field.setActive(index === deck.indexOfElement(canvas));
      });
    }

    initParallax(stage);
    initPortals();

    var liveEl = stage.querySelector('[data-live]');
    if (liveEl && window.fetch && window.Intl) {
      new Live(liveEl).start();
    }

    var ready = function () {
      doc.classList.add('is-ready');
      if (field) {
        field.init();
      }
      deck.reveal();
    };

    var splash = document.querySelector('[data-splash]');
    if (splash && !doc.classList.contains('intro-seen') && window.getComputedStyle(splash).display !== 'none') {
      deck.locked = true;
      new Intro(splash).run(function () {
        deck.locked = false;
        ready();
      });
    } else {
      if (splash && splash.parentNode) {
        splash.parentNode.removeChild(splash);
      }
      // Satu frame dengan keadaan tersembunyi dulu, agar transisi masuk berjalan.
      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(ready);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
