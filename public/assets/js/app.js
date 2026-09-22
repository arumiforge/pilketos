/**
 * SMP 1 Dawe — Pemilihan Ketua OSIS 2026
 * Foundation JS (Stage 1). Interaksi voting/3D/countdown kinetic
 * ditambahkan pada Stage 2, tidak dimuat di sini.
 */
(function () {
  'use strict';

  // Auto-dismiss flash alert setelah beberapa detik, kecuali user hover.
  document.addEventListener('DOMContentLoaded', function () {
    var alerts = document.querySelectorAll('[data-flash]');

    alerts.forEach(function (alertEl) {
      var timer = window.setTimeout(function () {
        alertEl.setAttribute('hidden', 'hidden');
      }, 6000);

      alertEl.addEventListener('mouseenter', function () {
        window.clearTimeout(timer);
      });
    });
  });
})();
