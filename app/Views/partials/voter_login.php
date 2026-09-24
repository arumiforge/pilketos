<?php
/**
 * Login pemilih dua tahap (Stage 9), dipakai siswa & guru.
 *
 * Tahap 1 NISN/NIP -> "Lanjut" -> tahap 2 kode unik -> "Masuk". Indikator
 * langkah (1 identitas, 2 kode unik, 3 terbuka) di atas form. Setelah server
 * menerima, gembok di kepala kartu terbuka dulu, baru pindah ke dasbor
 * (assets/js/auth.js, kirim form lewat fetch; server menjawab JSON).
 *
 * Tahap 1 tidak memeriksa apa pun ke server (tidak ada celah menebak NISN/NIP
 * terdaftar); kredensial tetap diperiksa sekali saat masuk, dengan throttle
 * yang sama. Tanpa JavaScript (atau bila auth.js gagal dimuat) kedua isian
 * tampil sekaligus dan form terkirim biasa.
 *
 * @var string $action   Path POST, mis. 'siswa/masuk'
 * @var string $title    Judul kartu
 * @var string $idField  'nisn' | 'nip'
 * @var string $idLabel  'NISN' | 'NIP'
 * @var int    $idMax    Panjang maksimal identitas
 */
$old = (string) (session()->getFlashdata('old_' . $idField) ?? '');
?>
<section class="section auth-page">
  <div class="container">
    <div class="card card--auth auth" data-auth>
      <div class="auth__lock" aria-hidden="true">
        <svg class="padlock" viewBox="0 0 64 64" width="64" height="64" fill="none" focusable="false">
          <path class="padlock__shackle" d="M22 31V21a10 10 0 0 1 20 0v14" />
          <rect class="padlock__body" x="12" y="29" width="40" height="28" rx="6" />
          <g class="padlock__key">
            <circle cx="32" cy="41" r="3.5" />
            <path d="M32 44v5" />
          </g>
        </svg>
      </div>

      <h1 class="card__title auth__title"><?= esc($title) ?></h1>

      <ol class="auth-steps" aria-label="Langkah masuk" data-auth-steps>
        <?php foreach ([$idLabel, 'Kode unik', 'Terbuka'] as $i => $stepLabel): ?>
          <li class="auth-steps__item<?= $i === 0 ? ' is-current' : '' ?>"<?= $i === 0 ? ' aria-current="step"' : '' ?> data-auth-step>
            <span class="auth-steps__dot" aria-hidden="true">
              <span class="auth-steps__no"><?= $i + 1 ?></span>
              <?= icon('check', 'auth-steps__check') ?>
            </span>
            <span class="auth-steps__label"><?= esc($stepLabel) ?></span>
          </li>
        <?php endforeach; ?>
      </ol>

      <form action="<?= base_url($action) ?>" method="post" class="auth__form" novalidate data-auth-form>
        <?= csrf_field() ?>

        <div class="auth__panel is-active" data-auth-panel="1">
          <div class="field">
            <label for="<?= esc($idField, 'attr') ?>"><?= esc($idLabel) ?></label>
            <input type="text" id="<?= esc($idField, 'attr') ?>" name="<?= esc($idField, 'attr') ?>" inputmode="numeric" autocomplete="off"
                   maxlength="<?= (int) $idMax ?>" value="<?= esc($old, 'attr') ?>" required data-auth-id
                   data-auth-numeric="<?= $idField === 'nisn' ? '1' : '0' ?>">
          </div>
          <button type="button" class="btn btn--block auth__next" data-auth-next>Lanjut <?= icon('arrow-right') ?></button>
        </div>

        <div class="auth__panel" data-auth-panel="2">
          <p class="auth__who" data-auth-who>
            <span class="auth__who-label"><?= esc($idLabel) ?></span>
            <strong class="auth__who-value" data-auth-echo></strong>
            <button type="button" class="auth__back" data-auth-back><?= icon('arrow-left') ?> Ubah</button>
          </p>
          <div class="field">
            <label for="kodeunik">Kode Unik</label>
            <input type="password" id="kodeunik" name="kodeunik" inputmode="numeric" autocomplete="off"
                   maxlength="10" required data-auth-code>
          </div>
          <button type="submit" class="btn btn--block" data-loading-text="Memeriksa..." data-auth-submit>Masuk</button>
        </div>

        <p class="auth__error" role="alert" data-auth-error hidden></p>
      </form>

      <p class="visually-hidden" role="status" aria-live="polite" data-auth-status></p>
    </div>
  </div>
</section>
