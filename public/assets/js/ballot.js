/**
 * Surat suara interaktif (Stage 2): ambil paku -> arahkan -> coblos ->
 * dampak pada kotak -> modal konfirmasi -> POST JSON -> terkunci.
 *
 * Efek visual TIDAK ikut menentukan keamanan: yang dikirim hanya
 * candidate_id; server memvalidasi ulang sesi, jadwal, kandidat, dan
 * suara ganda di dalam transaction.
 *
 * Mode paku:
 * - "3d": renderer WebGL (nail-webgl.js, dimuat malas);
 * - "2d": SVG + CSS transform (fallback: WebGL tidak ada, perangkat lemah,
 *         prefers-reduced-motion, frame lambat, atau dipilih pengguna).
 * Tombol "Coblos Pasangan 0X" selalu tersedia (keyboard, pembaca layar,
 * dan tanpa gestur). Tanpa JavaScript tombol itu membuka halaman konfirmasi.
 */
(function () {
  'use strict';

  var ballot = document.querySelector('[data-ballot]');
  var App = window.App;

  if (!ballot || !ballot.hasAttribute('data-submit-url') || !App || !window.Promise) {
    return;
  }

  var reduced = App.reducedMotion();
  var submitUrl = ballot.getAttribute('data-submit-url');
  var myVoteUrl = ballot.getAttribute('data-myvote-url');
  var webglSrc = ballot.getAttribute('data-webgl-src');
  var cells = Array.prototype.slice.call(ballot.querySelectorAll('[data-cell]'));
  var dock = ballot.querySelector('[data-dock]');
  var grip = ballot.querySelector('[data-nail-grip]');
  var fxToggle = ballot.querySelector('[data-fx-toggle]');
  var fxLabel = ballot.querySelector('[data-fx-label]');
  var live = ballot.querySelector('[data-ballot-live]');
  var dialog = document.querySelector('[data-confirm]');
  var nail2d = document.querySelector('[data-nail2d]');
  var canvas = document.querySelector('[data-nail3d]');

  if (!grip || !dialog || !nail2d || !canvas || typeof dialog.showModal !== 'function') {
    return; // tanpa <dialog>: tombol Coblos tetap memakai halaman konfirmasi server
  }

  var nail2dBody = nail2d.querySelector('.nail2d__body');
  var nail2dSvg = nail2d.querySelector('.nail-svg');
  var nail2dShadow = nail2d.querySelector('.nail2d__shadow');
  var slots = {
    number: dialog.querySelector('[data-slot="number"]'),
    ketua: dialog.querySelector('[data-slot="ketua"]'),
    wakil: dialog.querySelector('[data-slot="wakil"]'),
    portraits: dialog.querySelector('[data-slot="portraits"]'),
    error: dialog.querySelector('[data-slot="error"]'),
    successMeta: dialog.querySelector('[data-slot="success-meta"]')
  };
  var reviewState = dialog.querySelector('[data-confirm-state="review"]');
  var successState = dialog.querySelector('[data-confirm-state="success"]');
  var submitBtn = dialog.querySelector('[data-confirm-submit]');
  var cancelBtn = dialog.querySelector('[data-confirm-cancel]');
  var submitText = submitBtn.textContent;

  /* ------------------------------------------------------------------ */
  /* state                                                              */
  /* ------------------------------------------------------------------ */

  var BASE_TILT = 38;
  var BASE_AZ = 48;
  var TOUCH_OFFSET = -72; // ujung paku di atas jari agar tidak tertutup

  var phase = 'idle'; // idle | holding | flying | stabbing | confirming | submitting | done
  var mode = '2d';
  var renderer = null;
  var webglState = 'unknown'; // unknown | loading | ready | unavailable
  var forced3d = false;

  var nail = {
    x: 0, y: 0, h: 0, scale: 1, alpha: 0, lean: 0, press: 0, spin: 0,
    visible: false, rim: [0.92, 0.92, 0.96], rimStrength: 0.25
  };
  var aim = { x: 0, y: 0 };
  var flight = null;
  var tweens = [];
  var rafId = null;
  var lastFrame = 0;
  var lastX = 0;
  var pointerId = null;
  var pointerOffset = 0;
  var leftDock = false;
  var aimedCell = null;
  var selection = null;
  var openedAt = 0;
  var exitUrl = null; // tujuan saat modal ditutup setelah proses selesai
  var perf = { frames: 0, slow: 0 };

  /* ------------------------------------------------------------------ */
  /* util                                                               */
  /* ------------------------------------------------------------------ */

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function now() {
    return window.performance.now();
  }

  var ease = {
    out: function (t) { return 1 - Math.pow(1 - t, 3); },
    in: function (t) { return t * t * t; },
    inOut: function (t) { return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; },
    back: function (t) { var c = 1.6; return 1 + (c + 1) * Math.pow(t - 1, 3) + c * Math.pow(t - 1, 2); }
  };

  function hexToRgb(hex) {
    var match = /^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(hex || '');
    if (!match) {
      return [0.92, 0.92, 0.96];
    }
    return [parseInt(match[1], 16) / 255, parseInt(match[2], 16) / 255, parseInt(match[3], 16) / 255];
  }

  function say(text) {
    if (live) {
      live.textContent = '';
      window.setTimeout(function () {
        live.textContent = text;
      }, 30);
    }
  }

  function tween(target, to, duration, easing) {
    return new Promise(function (resolve) {
      if (reduced || duration <= 0) {
        for (var key in to) {
          if (Object.prototype.hasOwnProperty.call(to, key)) {
            target[key] = to[key];
          }
        }
        requestFrame();
        resolve();
        return;
      }

      var from = {};
      for (var k in to) {
        if (Object.prototype.hasOwnProperty.call(to, k)) {
          from[k] = target[k];
        }
      }
      tweens.push({
        target: target, from: from, to: to, start: now(),
        duration: duration, ease: easing || ease.out, resolve: resolve
      });
      requestFrame();
    });
  }

  function stepTweens(time) {
    for (var i = tweens.length - 1; i >= 0; i--) {
      var t = tweens[i];
      var p = clamp((time - t.start) / t.duration, 0, 1);
      var e = t.ease(p);
      for (var key in t.to) {
        if (Object.prototype.hasOwnProperty.call(t.to, key)) {
          t.target[key] = t.from[key] + (t.to[key] - t.from[key]) * e;
        }
      }
      if (p >= 1) {
        tweens.splice(i, 1);
        t.resolve();
      }
    }
  }

  /* ------------------------------------------------------------------ */
  /* mode 3D / ringan                                                   */
  /* ------------------------------------------------------------------ */

  function lowEndDevice() {
    var nav = window.navigator;
    return (nav.deviceMemory && nav.deviceMemory <= 2) ||
      (nav.hardwareConcurrency && nav.hardwareConcurrency <= 2) ||
      (nav.connection && nav.connection.saveData === true);
  }

  function decideMode() {
    var query = /[?&]fx=(3d|2d)\b/.exec(window.location.search);
    if (query) {
      App.setPref('fx', query[1]);
    }

    var pref = App.getPref('fx');
    forced3d = pref === '3d';

    // Pemeriksaan murah saja; konteks WebGL baru dibuat saat renderer dimuat
    // (NailWebGL.create() mengembalikan null bila GPU tidak layak).
    if (!window.WebGLRenderingContext) {
      webglState = 'unavailable';
      return '2d';
    }
    if (pref === '2d' || pref === '3d') {
      return pref;
    }
    return (reduced || lowEndDevice()) ? '2d' : '3d';
  }

  function loadWebgl() {
    if (webglState !== 'unknown' || !webglSrc) {
      return;
    }
    webglState = 'loading';

    var script = document.createElement('script');
    script.src = webglSrc;
    script.async = true;
    script.onload = function () {
      renderer = window.NailWebGL ? window.NailWebGL.create(canvas, forced3d) : null;
      webglState = renderer ? 'ready' : 'unavailable';
      if (!renderer && mode === '3d') {
        setMode('2d', false);
      }
      updateFxToggle();
    };
    script.onerror = function () {
      webglState = 'unavailable';
      setMode('2d', false);
    };
    document.head.appendChild(script);
  }

  function using3d() {
    return mode === '3d' && webglState === 'ready' && renderer && !renderer.lost;
  }

  function setMode(next, remember) {
    mode = next;
    if (remember) {
      App.setPref('fx', next);
      forced3d = next === '3d';
    }
    if (next === '3d') {
      loadWebgl();
    }
    canvas.hidden = true;
    nail2d.hidden = true;
    updateFxToggle();
    draw();
  }

  /**
   * WebGL tidak layak di perangkat ini (frame lambat / context lost): lepas
   * resource GPU dan pakai paku 2D sampai halaman dimuat ulang.
   */
  function downgrade() {
    if (renderer) {
      renderer.destroy();
      renderer = null;
    }
    webglState = 'unavailable';
    setMode('2d', false);
  }

  function updateFxToggle() {
    if (!fxToggle) {
      return;
    }
    if (webglState === 'unavailable') {
      fxToggle.disabled = true;
      fxToggle.setAttribute('aria-pressed', 'false');
      fxLabel.textContent = 'Tidak didukung';
      fxToggle.title = 'Perangkat ini memakai paku 2D (mode ringan).';
      return;
    }
    var on = mode === '3d';
    fxToggle.setAttribute('aria-pressed', on ? 'true' : 'false');
    fxLabel.textContent = on ? 'Nyala' : 'Mati';
    fxToggle.title = on ? 'Matikan efek 3D (paku 2D ringan)' : 'Nyalakan efek 3D';
  }

  /* ------------------------------------------------------------------ */
  /* render                                                             */
  /* ------------------------------------------------------------------ */

  function requestFrame() {
    if (rafId === null) {
      lastFrame = now();
      rafId = window.requestAnimationFrame(frame);
    }
  }

  function frame(time) {
    rafId = null;
    var dt = clamp((time - lastFrame) / 1000, 0.001, 0.05);
    lastFrame = time;

    stepTweens(time);

    if (flight) {
      var u = flight.u;
      var inv = 1 - u;
      nail.x = inv * inv * flight.x0 + 2 * inv * u * flight.cx + u * u * flight.x1;
      nail.y = inv * inv * flight.y0 + 2 * inv * u * flight.cy + u * u * flight.y1;
    } else if (phase === 'holding') {
      var follow = 1 - Math.exp(-dt * (reduced ? 60 : 28));
      nail.x += (aim.x - nail.x) * follow;
      nail.y += (aim.y - nail.y) * follow;
    }

    // Condong mengikuti kecepatan geser (kepala paku tertinggal).
    var vx = (nail.x - lastX) / dt;
    lastX = nail.x;
    var leanTarget = reduced ? 0 : clamp(-vx * 0.018, -20, 20);
    nail.lean += (leanTarget - nail.lean) * (1 - Math.exp(-dt * 10));

    draw();

    if (using3d() && phase === 'holding') {
      perf.frames++;
      if (dt > 0.045) {
        perf.slow++;
      }
      // Frame terlalu lambat di awal interaksi: pindah ke mode ringan untuk halaman ini.
      if (perf.frames <= 45 && perf.slow >= 10) {
        downgrade();
      }
    }

    var settling = phase === 'holding' || tweens.length > 0 || flight !== null || Math.abs(nail.lean) > 0.05;
    if (settling) {
      rafId = window.requestAnimationFrame(frame);
    }
  }

  function draw() {
    if (!nail.visible || nail.alpha <= 0.01) {
      canvas.hidden = true;
      nail2d.hidden = true;
      return;
    }

    if (using3d()) {
      nail2d.hidden = true;
      canvas.hidden = false;
      var anchor = window.NailWebGL.anchor;
      canvas.style.transform = 'translate3d(' + (nail.x - anchor.x).toFixed(1) + 'px,' + (nail.y - anchor.y).toFixed(1) + 'px,0)';
      var ok = renderer.render({
        h: nail.h,
        tilt: BASE_TILT * (1 - nail.press * 0.7) + Math.abs(nail.lean) * 0.4,
        az: BASE_AZ - nail.lean * 1.6,
        spin: nail.spin,
        scale: nail.scale,
        alpha: nail.alpha,
        rim: nail.rim,
        rimStrength: nail.rimStrength
      });
      if (!ok) {
        downgrade();
      }
      return;
    }

    canvas.hidden = true;
    nail2d.hidden = false;
    var lift = Math.max(0, nail.h);
    var sink = Math.max(0, -nail.h) * 34;
    var rotation = 34 + nail.lean - nail.press * 22;
    var scale = nail.scale * (1 + lift * 0.07);

    nail2d.style.opacity = nail.alpha.toFixed(3);
    nail2d.style.transform = 'translate3d(' + nail.x.toFixed(1) + 'px,' + nail.y.toFixed(1) + 'px,0)';
    nail2dBody.style.transform = 'rotate(' + rotation.toFixed(2) + 'deg) scale(' + scale.toFixed(3) + ')';
    nail2dSvg.style.transform = sink > 0 ? 'translateY(' + sink.toFixed(1) + 'px)' : '';
    nail2dSvg.style.clipPath = sink > 0 ? 'inset(0 0 ' + sink.toFixed(1) + 'px 0)' : '';
    nail2dShadow.style.opacity = clamp(0.55 - lift * 0.15, 0.15, 0.6).toFixed(3);
    nail2dShadow.style.transform = 'translate(' + (lift * 16).toFixed(1) + 'px,' + (lift * 12).toFixed(1) + 'px) rotate(' +
      (rotation + 8).toFixed(2) + 'deg) scaleY(' + (0.4 + lift * 0.5).toFixed(3) + ')';
  }

  /* ------------------------------------------------------------------ */
  /* bidikan & dampak                                                   */
  /* ------------------------------------------------------------------ */

  function cellAt(x, y) {
    if (x < 0 || y < 0 || x > window.innerWidth || y > window.innerHeight) {
      return null;
    }
    var el = document.elementFromPoint(x, y);
    var target = el ? el.closest('[data-target]') : null;
    var cell = target ? target.closest('[data-cell]') : null;
    return cell && ballot.contains(cell) ? cell : null;
  }

  function setAimedCell(cell) {
    if (cell === aimedCell) {
      return;
    }
    if (aimedCell) {
      aimedCell.classList.remove('is-aimed');
    }
    aimedCell = cell;
    if (cell) {
      cell.classList.add('is-aimed');
      nail.rim = hexToRgb(cell.getAttribute('data-accent'));
      nail.rimStrength = 0.95;
      say('Mengarah ke pasangan ' + cell.getAttribute('data-number') + '. Lepaskan untuk mencoblos.');
    } else {
      nail.rim = [0.92, 0.92, 0.96];
      nail.rimStrength = 0.25;
    }
    requestFrame();
  }

  function holeSvg() {
    function ring(count, minR, maxR) {
      var points = [];
      for (var i = 0; i < count; i++) {
        var a = (i / count) * Math.PI * 2 + (Math.random() - 0.5) * 0.4;
        var r = minR + Math.random() * (maxR - minR);
        points.push((Math.cos(a) * r).toFixed(2) + ' ' + (Math.sin(a) * r).toFixed(2));
      }
      return 'M' + points.join(' L') + ' Z';
    }
    return '<svg viewBox="-15 -15 30 30" aria-hidden="true" focusable="false">' +
      '<path d="' + ring(11, 8.5, 12.5) + '" fill="#E3E0D8" stroke="#B9B4A6" stroke-width="0.6"/>' +
      '<path d="' + ring(9, 4.6, 6.4) + '" fill="#15141A"/>' +
      '</svg>';
  }

  function punch(cell, point) {
    var target = cell.querySelector('[data-target]');
    var holes = cell.querySelector('[data-holes]');
    var rect = target.getBoundingClientRect();
    var x = clamp(point.x - rect.left, 18, rect.width - 18);
    var y = clamp(point.y - rect.top, 18, rect.height - 18);

    var hole = document.createElement('span');
    hole.className = 'hole';
    hole.style.left = (x / rect.width * 100).toFixed(2) + '%';
    hole.style.top = (y / rect.height * 100).toFixed(2) + '%';
    hole.style.rotate = Math.round(Math.random() * 360) + 'deg';
    hole.innerHTML = holeSvg();
    holes.appendChild(hole);

    if (!reduced) {
      ['impact-ring', 'impact-ring impact-ring--late'].forEach(function (className) {
        var ringEl = document.createElement('span');
        ringEl.className = className;
        ringEl.style.left = hole.style.left;
        ringEl.style.top = hole.style.top;
        ringEl.addEventListener('animationend', function () {
          ringEl.remove();
        });
        holes.appendChild(ringEl);
      });

      cell.classList.remove('is-hit');
      void cell.offsetWidth;
      cell.classList.add('is-hit');
    }

    cell.classList.add('is-punched');
    ballot.classList.add('is-busy');

    if (window.navigator.vibrate) {
      try {
        window.navigator.vibrate(18);
      } catch (e) { /* abaikan */ }
    }

    return hole;
  }

  /* ------------------------------------------------------------------ */
  /* alur paku                                                          */
  /* ------------------------------------------------------------------ */

  function showNailAt(x, y) {
    nail.x = x;
    nail.y = y;
    lastX = x;
    nail.h = 0.15;
    nail.scale = 0.72;
    nail.alpha = 1;
    nail.lean = 0;
    nail.press = 0;
    nail.spin = 0;
    nail.visible = true;
    perf.frames = 0;
    perf.slow = 0;
    grip.classList.add('is-empty');
    return tween(nail, { h: 1, scale: 1, spin: 0.9 }, 280, ease.back);
  }

  function hideNail() {
    return tween(nail, { alpha: 0 }, 180, ease.out).then(function () {
      nail.visible = false;
      grip.classList.remove('is-empty');
      draw();
    });
  }

  function returnToDock() {
    phase = 'returning';
    setAimedCell(null);
    var rect = grip.getBoundingClientRect();
    flight = { u: 0, x0: nail.x, y0: nail.y, x1: rect.left + 30, y1: rect.top + rect.height / 2, cx: 0, cy: 0 };
    flight.cx = (flight.x0 + flight.x1) / 2;
    flight.cy = Math.min(flight.y0, flight.y1) - 40;

    return Promise.all([
      tween(flight, { u: 1 }, 320, ease.inOut),
      tween(nail, { h: 0.2, scale: 0.7 }, 320, ease.inOut)
    ]).then(function () {
      flight = null;
      return hideNail();
    }).then(function () {
      phase = 'idle';
    });
  }

  function stab(cell, point) {
    phase = 'stabbing';
    setAimedCell(null);
    nail.x = point.x;
    nail.y = point.y;
    nail.rim = hexToRgb(cell.getAttribute('data-accent'));
    nail.rimStrength = 0.95;

    return tween(nail, { h: 1.45, press: 0.4 }, 110, ease.out)
      .then(function () {
        return tween(nail, { h: -0.6, press: 1, scale: 0.97 }, 130, ease.in);
      })
      .then(function () {
        var hole = punch(cell, point);
        tween(nail, { h: 0.3, press: 0.5, scale: 1 }, 240, ease.out).then(hideNail);
        return hole;
      })
      .then(function (hole) {
        openConfirm(cell, hole);
      });
  }

  function flyAndStab(cell, fromRect) {
    var target = cell.querySelector('[data-target]');
    var rect = target.getBoundingClientRect();
    var point = {
      x: rect.left + rect.width * (0.5 + (Math.random() - 0.5) * 0.25),
      y: rect.top + rect.height * (0.45 + (Math.random() - 0.5) * 0.2)
    };

    if (reduced) {
      phase = 'stabbing';
      var hole = punch(cell, point);
      openConfirm(cell, hole);
      return;
    }

    phase = 'flying';
    var x0 = fromRect.left + fromRect.width / 2;
    var y0 = fromRect.top + fromRect.height / 2;
    showNailAt(x0, y0);
    flight = {
      u: 0, x0: x0, y0: y0, x1: point.x, y1: point.y,
      cx: (x0 + point.x) / 2 + 40, cy: Math.min(y0, point.y) - 90
    };
    nail.rim = hexToRgb(cell.getAttribute('data-accent'));
    nail.rimStrength = 0.95;

    Promise.all([
      tween(flight, { u: 1 }, 460, ease.inOut),
      tween(nail, { h: 1.2 }, 460, ease.out)
    ]).then(function () {
      flight = null;
      return stab(cell, point);
    });
  }

  /* ---- pegang & geser (pointer/touch) ---- */

  function autoScroll(y) {
    var dockTop = dock.getBoundingClientRect().top;
    if (y < 72) {
      window.scrollBy(0, -Math.round((72 - y) / 4) - 2);
    } else if (leftDock && y > dockTop - 12) {
      window.scrollBy(0, Math.round((y - dockTop + 12) / 4) + 2);
    }
    if (y < dockTop - 30) {
      leftDock = true;
    }
  }

  function onGripDown(event) {
    if (phase !== 'idle' || (event.pointerType === 'mouse' && event.button !== 0)) {
      return;
    }
    event.preventDefault();

    pointerId = event.pointerId;
    try {
      grip.setPointerCapture(pointerId);
    } catch (e) { /* abaikan */ }

    pointerOffset = event.pointerType === 'mouse' ? 0 : TOUCH_OFFSET;
    leftDock = false;
    phase = 'holding';
    setPicked(null);
    document.body.classList.add('is-holding-nail');

    aim.x = event.clientX;
    aim.y = event.clientY + pointerOffset;
    showNailAt(aim.x, aim.y);
    say('Paku diambil. Geser ke kotak pasangan pilihan, lalu lepaskan.');
  }

  function onGripMove(event) {
    if (phase !== 'holding' || event.pointerId !== pointerId) {
      return;
    }
    aim.x = event.clientX;
    aim.y = event.clientY + pointerOffset;
    autoScroll(aim.y);
    setAimedCell(cellAt(aim.x, aim.y));
    requestFrame();
  }

  function endHold() {
    document.body.classList.remove('is-holding-nail');
    try {
      if (pointerId !== null && grip.hasPointerCapture(pointerId)) {
        grip.releasePointerCapture(pointerId);
      }
    } catch (e) { /* abaikan */ }
    pointerId = null;
  }

  function onGripUp(event) {
    if (phase !== 'holding' || event.pointerId !== pointerId) {
      return;
    }
    endHold();

    var cell = cellAt(aim.x, aim.y);
    if (cell) {
      stab(cell, { x: aim.x, y: aim.y });
      return;
    }

    returnToDock();
    say('Paku belum mengenai kotak pasangan. Tahan paku, geser ke kotak, lalu lepaskan. Bisa juga memakai tombol Coblos.');
  }

  function onGripCancel() {
    if (phase !== 'holding') {
      return;
    }
    endHold();
    returnToDock();
  }

  /* ------------------------------------------------------------------ */
  /* konfirmasi & kirim                                                 */
  /* ------------------------------------------------------------------ */

  function showError(message) {
    slots.error.textContent = message;
    slots.error.hidden = false;
  }

  function setBusy(busy) {
    submitBtn.disabled = busy;
    cancelBtn.disabled = busy;
    submitBtn.textContent = busy ? (submitBtn.getAttribute('data-loading-text') || submitText) : submitText;
  }

  function openConfirm(cell, hole) {
    selection = { cell: cell, hole: hole };
    phase = 'confirming';

    dialog.setAttribute('style', cell.getAttribute('style') || '');
    slots.number.textContent = cell.getAttribute('data-number');
    slots.ketua.textContent = cell.getAttribute('data-ketua');
    slots.wakil.textContent = cell.getAttribute('data-wakil');
    slots.portraits.textContent = '';
    var portraits = cell.querySelector('[data-portraits]');
    if (portraits) {
      slots.portraits.appendChild(portraits.cloneNode(true));
    }
    slots.error.hidden = true;
    slots.error.textContent = '';
    reviewState.hidden = false;
    successState.hidden = true;
    setBusy(false);

    openedAt = now();
    dialog.showModal();
    var title = dialog.querySelector('#confirm-title');
    if (title) {
      title.focus();
    }
    say('Konfirmasi pilihan pasangan ' + cell.getAttribute('data-number') + '.');
  }

  function resetSelection() {
    if (!selection) {
      return;
    }
    var cell = selection.cell;
    var hole = selection.hole;
    selection = null;

    hole.classList.add('is-leaving');
    window.setTimeout(function () {
      hole.remove();
    }, reduced ? 0 : 320);
    cell.classList.remove('is-punched', 'is-hit');
    ballot.classList.remove('is-busy');
    phase = 'idle';

    var button = cell.querySelector('[data-coblos]');
    if (button) {
      button.focus();
    }
    say('Pilihan dibatalkan. Silakan pilih ulang.');
  }

  function onSuccess(data) {
    phase = 'done';
    exitUrl = myVoteUrl;
    ballot.classList.remove('is-busy');
    ballot.classList.add('is-locked');
    cells.forEach(function (cell) {
      var button = cell.querySelector('[data-coblos]');
      if (button) {
        button.setAttribute('aria-disabled', 'true');
        button.setAttribute('tabindex', '-1');
      }
    });

    var vote = data.vote || {};
    slots.successMeta.textContent = 'Pasangan ' + (vote.number || '') + ' · dicoblos ' + (vote.voted_at || '');
    reviewState.hidden = true;
    successState.hidden = false;

    var title = successState.querySelector('[data-success-title]');
    if (title) {
      title.focus();
    }
    say('Suara berhasil disimpan. Hak suara Anda telah dikunci.');
  }

  function submit() {
    if (phase !== 'confirming' || !selection || now() - openedAt < 400) {
      return; // cegah klik ganda / tombol Enter tertahan
    }

    phase = 'submitting';
    setBusy(true);
    slots.error.hidden = true;

    var candidateId = parseInt(selection.cell.getAttribute('data-candidate-id'), 10);

    App.postJson(submitUrl, { candidate_id: candidateId }).then(function (result) {
      var data = result.data || {};

      if (result.status === 200 && data.status === 'ok') {
        onSuccess(data);
        return;
      }

      if (result.status === 409) {
        phase = 'done';
        exitUrl = data.redirect || myVoteUrl;
        showError(data.message || 'Hak suara ini sudah digunakan.');
        window.setTimeout(function () {
          window.location.assign(data.redirect || myVoteUrl);
        }, 1800);
        return;
      }

      if (result.status === 401) {
        phase = 'done';
        exitUrl = data.redirect || window.location.href;
        showError(data.message || 'Sesi berakhir. Silakan masuk kembali.');
        return;
      }

      if (result.status === 403) {
        phase = 'done';
        exitUrl = window.location.href;
        showError(data.message || 'Pencoblosan tidak tersedia saat ini.');
        window.setTimeout(function () {
          window.location.reload();
        }, 2500);
        return;
      }

      phase = 'confirming';
      setBusy(false);
      if (result.status === 422) {
        submitBtn.disabled = true;
        showError(data.message || 'Pasangan calon tidak valid. Batalkan lalu pilih ulang.');
        return;
      }
      showError(data.message || 'Suara belum tersimpan. Periksa koneksi lalu tekan KONFIRMASI PILIHAN lagi.');
    }).catch(function () {
      phase = 'confirming';
      setBusy(false);
      showError('Koneksi terputus, suara belum tersimpan. Periksa jaringan lalu tekan KONFIRMASI PILIHAN lagi.');
    });
  }

  /* ------------------------------------------------------------------ */
  /* "Pilih 0X" dari kartu Sekilas paslon / akhir bab (Stage 7)         */
  /* ------------------------------------------------------------------ */

  var pickedCell = null;

  function setPicked(cell) {
    if (pickedCell) {
      pickedCell.classList.remove('is-picked');
    }
    pickedCell = cell;
    if (cell) {
      cell.classList.add('is-picked');
    }
  }

  /**
   * Tautan #coblos-0X: biarkan browser menggulir ke kotaknya (tanpa JS pun
   * jalan lewat :target), lalu tandai kotak & fokuskan tombol Coblos-nya
   * supaya pengguna keyboard/pembaca layar langsung sampai di sana.
   */
  function pickFromHash() {
    var match = /^#coblos-(\d{2})$/.exec(window.location.hash);
    var cell = match ? ballot.querySelector('#coblos-' + match[1]) : null;
    if (!cell || phase !== 'idle') {
      return;
    }
    setPicked(cell);
    var button = cell.querySelector('[data-coblos]');
    if (button) {
      button.focus({ preventScroll: true });
    }
    say('Kotak pasangan ' + cell.getAttribute('data-number') + '. Tekan Coblos untuk mencoblos.');
  }

  /* ------------------------------------------------------------------ */
  /* init                                                               */
  /* ------------------------------------------------------------------ */

  function init() {
    mode = decideMode();
    dock.hidden = false;
    updateFxToggle();

    if (mode === '3d') {
      // Muat renderer setelah halaman tenang, bukan saat render awal.
      var idle = window.requestIdleCallback || function (fn) {
        return window.setTimeout(fn, 600);
      };
      idle(loadWebgl);
    }

    grip.addEventListener('pointerdown', onGripDown);
    grip.addEventListener('pointermove', onGripMove);
    grip.addEventListener('pointerup', onGripUp);
    grip.addEventListener('pointercancel', onGripCancel);
    grip.addEventListener('lostpointercapture', onGripCancel);
    grip.addEventListener('contextmenu', function (event) {
      event.preventDefault();
    });
    grip.addEventListener('click', function (event) {
      // Enter/Spasi pada tombol paku: arahkan ke tombol Coblos (alternatif keyboard).
      if (event.detail === 0 && phase === 'idle') {
        var first = ballot.querySelector('[data-coblos]');
        if (first) {
          first.focus();
        }
        say('Gunakan tombol Coblos pada kotak pasangan pilihan.');
      }
    });

    cells.forEach(function (cell) {
      var button = cell.querySelector('[data-coblos]');
      if (!button) {
        return;
      }
      button.addEventListener('click', function (event) {
        event.preventDefault();
        if (phase !== 'idle') {
          return;
        }
        setPicked(null);
        flyAndStab(cell, button.getBoundingClientRect());
      });
    });

    if (fxToggle) {
      fxToggle.addEventListener('click', function () {
        if (phase !== 'idle' || webglState === 'unavailable') {
          return;
        }
        setMode(mode === '3d' ? '2d' : '3d', true);
        say(mode === '3d' ? 'Efek 3D dinyalakan.' : 'Mode ringan dinyalakan.');
      });
    }

    // Kotak tujuan kini disorot lewat .is-picked (bukan :target) agar sorotan
    // bisa dilepas saat pemilih mencoblos kotak lain.
    ballot.classList.add('ballot--picks');
    window.addEventListener('hashchange', pickFromHash);
    Array.prototype.forEach.call(document.querySelectorAll('[data-pick]'), function (link) {
      link.addEventListener('click', function () {
        // Hash sama (klik ulang) tidak memicu hashchange.
        if (link.getAttribute('href') === window.location.hash) {
          window.setTimeout(pickFromHash, 0);
        }
      });
    });
    pickFromHash();

    submitBtn.addEventListener('click', submit);
    cancelBtn.addEventListener('click', function () {
      if (phase === 'confirming') {
        dialog.close();
      }
    });

    dialog.addEventListener('cancel', function (event) {
      if (phase === 'submitting') {
        event.preventDefault(); // jangan tutup saat suara sedang dikirim
      }
    });

    dialog.addEventListener('close', function () {
      if (phase === 'done') {
        window.location.assign(exitUrl || myVoteUrl);
        return;
      }
      if (phase === 'confirming') {
        resetSelection();
      }
    });

    window.addEventListener('resize', function () {
      if (renderer && !renderer.lost) {
        renderer.resize();
      }
    });

    // Kembali lewat tombol back setelah memilih: biarkan server memutuskan tampilan.
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        window.location.reload();
      }
    });

    // Stage 4: halaman ditinggal -> lepas konteks WebGL (hemat memori GPU).
    window.addEventListener('pagehide', function () {
      if (renderer) {
        renderer.destroy();
        renderer = null;
        webglState = 'unknown';
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
