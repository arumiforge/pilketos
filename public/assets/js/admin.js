/**
 * SMP 1 Dawe — Pemilihan Ketua OSIS 2026
 * Panel admin (Stage 3): drawer menu HP, dialog konfirmasi, pemeriksaan
 * file sebelum unggah, pratinjau warna aksen, filter otomatis, dan tombol
 * tampilkan kode unik. Semua progressive enhancement: tanpa JavaScript
 * seluruh fitur tetap berjalan lewat form biasa dan validasi server.
 *
 * Tidak menyimpan data siswa/guru di localStorage/sessionStorage.
 */
(function () {
  'use strict';

  var PAPER = '#FAF9F6';
  var INK = '#15141A';
  var WHITE = '#FFFFFF';

  function formatBytes(bytes) {
    if (bytes >= 1048576) {
      return (bytes / 1048576).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' MB';
    }
    return Math.max(1, Math.round(bytes / 1024)) + ' KB';
  }

  /* -- drawer menu (HP) ---------------------------------------------------- */
  function initDrawer() {
    var shell = document.querySelector('[data-admin-shell]');
    var nav = document.getElementById('admin-nav');
    var main = document.querySelector('[data-admin-main]');
    var scrim = document.querySelector('[data-admin-scrim]');
    var openButton = document.querySelector('[data-admin-menu]');
    var closeButton = document.querySelector('[data-admin-menu-close]');

    if (!shell || !nav || !openButton) {
      return;
    }

    function isOpen() {
      return shell.classList.contains('is-nav-open');
    }

    function open() {
      shell.classList.add('is-nav-open');
      openButton.setAttribute('aria-expanded', 'true');
      if (scrim) {
        scrim.hidden = false;
      }
      if (main) {
        main.setAttribute('inert', '');
      }
      document.documentElement.style.overflow = 'hidden';
      var first = nav.querySelector('[aria-current="page"]') || nav.querySelector('a, button');
      if (first) {
        first.focus();
      }
    }

    function close(restoreFocus) {
      if (!isOpen()) {
        return;
      }
      shell.classList.remove('is-nav-open');
      openButton.setAttribute('aria-expanded', 'false');
      if (scrim) {
        scrim.hidden = true;
      }
      if (main) {
        main.removeAttribute('inert');
      }
      document.documentElement.style.overflow = '';
      if (restoreFocus) {
        openButton.focus();
      }
    }

    openButton.addEventListener('click', open);
    if (closeButton) {
      closeButton.addEventListener('click', function () {
        close(true);
      });
    }
    if (scrim) {
      scrim.addEventListener('click', function () {
        close(true);
      });
    }
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && isOpen()) {
        close(true);
      }
    });

    if (window.matchMedia) {
      var wide = window.matchMedia('(min-width: 1024px)');
      var onChange = function (event) {
        if (event.matches) {
          close(false);
        }
      };
      if (wide.addEventListener) {
        wide.addEventListener('change', onChange);
      } else if (wide.addListener) {
        wide.addListener(onChange);
      }
    }
  }

  /* -- dialog konfirmasi untuk form[data-confirm] -------------------------- */
  function initConfirm() {
    var dialog = document.getElementById('admin-confirm');
    var supported = dialog && typeof dialog.showModal === 'function';
    var text = dialog ? dialog.querySelector('#admin-confirm-text') : null;
    var accept = dialog ? dialog.querySelector('[data-confirm-accept]') : null;
    var cancel = dialog ? dialog.querySelector('[data-modal-close]') : null;
    var pending = null;

    // Fase capture: berjalan sebelum pencegah submit ganda di app.js.
    document.addEventListener('submit', function (event) {
      var form = event.target;

      if (!form.matches || !form.matches('form[data-confirm]') || form.dataset.confirmed === '1') {
        return;
      }

      // Isian wajib diperiksa dulu agar dialog tidak muncul untuk form yang pasti ditolak.
      if (form.noValidate && typeof form.checkValidity === 'function' && !form.checkValidity()) {
        event.preventDefault();
        event.stopImmediatePropagation();
        form.reportValidity();
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();

      var message = form.getAttribute('data-confirm');

      if (!supported) {
        if (window.confirm(message)) {
          form.dataset.confirmed = '1';
          form.submit();
        }
        return;
      }

      pending = { form: form, submitter: event.submitter || null };
      text.textContent = message;
      accept.textContent = form.getAttribute('data-confirm-button') || 'Lanjutkan';
      var danger = form.hasAttribute('data-confirm-danger');
      dialog.classList.toggle('is-danger', danger);
      dialog.showModal();
      // Tindakan berisiko: fokus awal di "Batal" agar Enter tidak langsung mengeksekusi.
      (danger ? cancel : accept).focus();
    }, true);

    if (!supported) {
      return;
    }

    accept.addEventListener('click', function () {
      if (!pending) {
        return;
      }
      var form = pending.form;
      var submitter = pending.submitter;
      pending = null;
      dialog.close();
      form.dataset.confirmed = '1';
      if (typeof form.requestSubmit === 'function') {
        if (submitter) {
          form.requestSubmit(submitter);
        } else {
          form.requestSubmit();
        }
      } else {
        form.submit();
      }
    });

    dialog.addEventListener('close', function () {
      pending = null;
    });

    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        document.querySelectorAll('form[data-confirmed]').forEach(function (form) {
          delete form.dataset.confirmed;
        });
      }
    });
  }

  /* -- file: ukuran, tipe, pratinjau --------------------------------------- */
  function checkFile(input) {
    var file = input.files && input.files[0];
    var max = parseInt(input.getAttribute('data-max-bytes'), 10) || 0;
    var kind = input.getAttribute('data-file-kind') || 'image';

    if (!file) {
      return '';
    }
    if (max && file.size > max) {
      return 'Ukuran file ' + formatBytes(file.size) + ' melebihi batas ' + formatBytes(max) + '.';
    }
    if (kind === 'xlsx' && !/\.xlsx$/i.test(file.name)) {
      return 'Pilih file Excel dengan ekstensi .xlsx.';
    }
    if (kind === 'image' && file.type && !/^image\/(jpeg|png|webp)$/.test(file.type)) {
      return 'Pilih gambar JPG, PNG, atau WebP.';
    }
    return '';
  }

  function initFileInputs() {
    document.querySelectorAll('input[type="file"][data-file-input]').forEach(function (input) {
      var holder = input.closest('[data-slot]') || input.closest('.field');
      var error = holder ? holder.querySelector('[data-file-error]') : null;
      var preview = holder ? holder.querySelector('[data-file-preview]') : null;
      var objectUrl = null;

      input.addEventListener('change', function () {
        var message = checkFile(input);
        var file = input.files && input.files[0];

        if (error) {
          error.textContent = message;
          error.hidden = message === '';
        }
        input.setAttribute('aria-invalid', message ? 'true' : 'false');

        if (objectUrl) {
          URL.revokeObjectURL(objectUrl);
          objectUrl = null;
        }

        if (preview) {
          if (file && !message && window.URL && URL.createObjectURL) {
            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
            preview.alt = 'Pratinjau file baru';
            preview.hidden = false;
          } else {
            preview.hidden = true;
            preview.removeAttribute('src');
          }
        }

        if (holder) {
          holder.classList.toggle('is-pending', !!file && !message);
        }
      });
    });

    // Tolak kirim bila ada file tidak valid atau total melebihi post_max_size
    // (server akan membuang seluruh isi form bila batas itu terlewati).
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!form.matches || !form.matches('form[data-upload-form]')) {
        return;
      }

      var inputs = form.querySelectorAll('input[type="file"][data-file-input]');
      var total = 0;
      var firstBad = null;

      inputs.forEach(function (input) {
        if (input.files && input.files[0]) {
          total += input.files[0].size;
        }
        if (!firstBad && checkFile(input)) {
          firstBad = input;
        }
      });

      var limit = parseInt(form.getAttribute('data-post-max'), 10) || 0;
      var totalError = form.querySelector('[data-upload-total-error]');
      var message = '';

      if (!firstBad && limit && total > limit * 0.95) {
        message = 'Total ukuran file (' + formatBytes(total) + ') melebihi batas server ' + formatBytes(limit) + '. Unggah sebagian dulu, simpan, lalu lanjutkan.';
      }

      if (totalError) {
        totalError.textContent = message;
        totalError.hidden = message === '';
      }

      if (firstBad || message) {
        event.preventDefault();
        event.stopImmediatePropagation();
        var focusTarget = firstBad || form.querySelector('[type="submit"]');
        if (focusTarget) {
          focusTarget.focus();
        }
      }
    }, true);
  }

  /* -- warna aksen & pratinjau --------------------------------------------- */
  function luminance(hex) {
    var channels = [1, 3, 5].map(function (offset) {
      var c = parseInt(hex.substr(offset, 2), 16) / 255;
      return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
  }

  function contrast(a, b) {
    var la = luminance(a);
    var lb = luminance(b);
    return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
  }

  function initAccent() {
    var picker = document.querySelector('[data-accent-picker]');
    var input = document.querySelector('[data-accent-input]');
    var preview = document.querySelector('[data-accent-preview]');

    if (!picker || !input || !preview) {
      return;
    }

    function normalize(value) {
      var v = (value || '').trim();
      if (/^[0-9a-f]{6}$/i.test(v)) {
        v = '#' + v;
      }
      return /^#[0-9a-f]{6}$/i.test(v) ? v.toUpperCase() : null;
    }

    function apply(hex) {
      preview.style.setProperty('--accent', hex);
      preview.style.setProperty('--accent-ink', contrast(hex, WHITE) >= contrast(hex, INK) ? WHITE : INK);
      preview.style.setProperty('--accent-text', contrast(hex, PAPER) >= 3 ? hex : INK);
    }

    picker.addEventListener('input', function () {
      input.value = picker.value.toUpperCase();
      apply(input.value);
    });

    input.addEventListener('input', function () {
      var hex = normalize(input.value);
      if (hex) {
        picker.value = hex.toLowerCase();
        apply(hex);
      }
    });

    var number = document.getElementById('nomor_urut');
    var name = document.getElementById('nama_ketua');
    var block = preview.querySelector('.accent-preview__block');
    var strong = preview.querySelector('.accent-preview__text strong');

    if (number && block) {
      number.addEventListener('input', function () {
        var n = parseInt(number.value, 10);
        block.textContent = n > 0 && n < 100 ? (n < 10 ? '0' + n : String(n)) : '00';
      });
    }
    if (name && strong) {
      name.addEventListener('input', function () {
        strong.textContent = name.value.trim() || 'Nama ketua';
      });
    }
  }

  /* -- tampilkan / samarkan kode unik -------------------------------------- */
  function initSecrets() {
    document.querySelectorAll('[data-secret-toggle]').forEach(function (button) {
      var target = document.getElementById(button.getAttribute('aria-controls'));
      var label = button.querySelector('[data-secret-toggle-label]');

      if (!target || !label) {
        return;
      }

      var showText = label.textContent;
      button.hidden = false;

      button.addEventListener('click', function () {
        var revealed = target.classList.toggle('is-revealed');
        button.setAttribute('aria-pressed', revealed ? 'true' : 'false');
        label.textContent = revealed ? showText.replace('Tampilkan', 'Samarkan') : showText;
      });
    });
  }

  /* -- filter: pilihan dropdown langsung diterapkan ------------------------ */
  function initAutosubmit() {
    document.querySelectorAll('form[data-autosubmit]').forEach(function (form) {
      form.querySelectorAll('select').forEach(function (select) {
        select.addEventListener('change', function () {
          if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
          } else {
            form.submit();
          }
        });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initDrawer();
    initConfirm();
    initFileInputs();
    initAccent();
    initSecrets();
    initAutosubmit();
  });
})();
