/**
 * SMP 1 Dawe — Pemilihan Ketua OSIS 2026
 * Foundation JS (Stage 1). Interaksi voting/3D/countdown kinetic
 * ditambahkan pada Stage 2, tidak dimuat di sini.
 *
 * Tidak menyimpan data siswa/guru di localStorage/sessionStorage.
 */
(function () {
  'use strict';

  /**
   * Token CSRF untuk request AJAX (Stage 2 voting, Stage 3 live count).
   * Sumber: <meta name="X-CSRF-TOKEN"> dari csrf_meta() di layout.
   * Contoh: fetch(url, { method: 'POST', headers: App.jsonHeaders(), body: ... })
   */
  function csrfHeader() {
    var meta = document.querySelector('meta[name="X-CSRF-TOKEN"]');
    var headers = {};
    if (meta) {
      headers['X-CSRF-TOKEN'] = meta.getAttribute('content');
    }
    return headers;
  }

  function jsonHeaders() {
    var headers = csrfHeader();
    headers['Accept'] = 'application/json';
    headers['Content-Type'] = 'application/json';
    headers['X-Requested-With'] = 'XMLHttpRequest';
    return headers;
  }

  /** Modal berbasis <dialog> native. */
  function initModals() {
    document.addEventListener('click', function (event) {
      var opener = event.target.closest('[data-modal-open]');
      if (opener) {
        var dialog = document.getElementById(opener.getAttribute('data-modal-open'));
        if (dialog && typeof dialog.showModal === 'function') {
          event.preventDefault();
          dialog.showModal();
        }
        return;
      }

      var closer = event.target.closest('[data-modal-close]');
      if (closer) {
        var parent = closer.closest('dialog');
        if (parent) {
          event.preventDefault();
          parent.close();
        }
        return;
      }

      // Klik pada backdrop menutup modal.
      if (event.target.tagName === 'DIALOG' && event.target.open) {
        var rect = event.target.getBoundingClientRect();
        var inside = event.clientX >= rect.left && event.clientX <= rect.right &&
          event.clientY >= rect.top && event.clientY <= rect.bottom;
        if (!inside) {
          event.target.close();
        }
      }
    });
  }

  /**
   * Cegah submit ganda: tombol submit dinonaktifkan setelah form dikirim.
   * Keamanan tetap di server (CSRF, validasi, constraint DB).
   */
  function initSubmitGuard() {
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (form.dataset.submitting === '1') {
        event.preventDefault();
        return;
      }
      form.dataset.submitting = '1';

      var button = form.querySelector('button[type="submit"]');
      if (button) {
        button.setAttribute('aria-disabled', 'true');
        if (button.dataset.loadingText) {
          button.textContent = button.dataset.loadingText;
        }
      }
    });

    // Kembali lewat tombol back (bfcache): aktifkan lagi form.
    window.addEventListener('pageshow', function (event) {
      if (!event.persisted) {
        return;
      }
      document.querySelectorAll('form[data-submitting="1"]').forEach(function (form) {
        form.dataset.submitting = '';
        var button = form.querySelector('button[type="submit"]');
        if (button) {
          button.removeAttribute('aria-disabled');
        }
      });
    });
  }

  /** Pesan sukses boleh hilang otomatis; pesan error tetap tampil. */
  function initFlash() {
    document.querySelectorAll('[data-flash-dismiss]').forEach(function (el) {
      var timer = window.setTimeout(function () {
        el.setAttribute('hidden', 'hidden');
      }, 6000);

      el.addEventListener('mouseenter', function () {
        window.clearTimeout(timer);
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initModals();
    initSubmitGuard();
    initFlash();
  });

  window.App = {
    csrfHeader: csrfHeader,
    jsonHeaders: jsonHeaders
  };
})();
