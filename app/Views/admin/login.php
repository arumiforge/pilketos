<?= $this->extend('layouts/split') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= asset_url('assets/css/auth.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
/**
 * Login admin (Stage 13): layar terbelah. Kiri (HP: atas) foto surat suara
 * di meja kelas, kanan formulir "Panel Admin" dengan tombol lihat kata sandi.
 * Form tetap POST biasa ke admin/masuk (tanpa JavaScript tetap berfungsi).
 */
$errors  = session()->getFlashdata('errors');
$error   = session()->getFlashdata('error');
$success = session()->getFlashdata('success');
$messages = array_values(array_filter(array_merge((array) $errors, [$error])));
?>
<div class="split-login">
  <figure class="split-login__visual">
    <picture>
      <source type="image/webp" srcset="<?= base_url('assets/img/auth/admin-login-960.webp') ?> 960w, <?= base_url('assets/img/auth/admin-login-1600.webp') ?> 1600w" sizes="(min-width: 900px) 56vw, 100vw">
      <img src="<?= base_url('assets/img/auth/admin-login-1600.jpg') ?>"
           srcset="<?= base_url('assets/img/auth/admin-login-960.jpg') ?> 960w, <?= base_url('assets/img/auth/admin-login-1600.jpg') ?> 1600w"
           sizes="(min-width: 900px) 56vw, 100vw" width="1600" height="893"
           alt="Surat suara dan pensil di atas meja kayu kelas" decoding="async" fetchpriority="high">
    </picture>
    <figcaption class="split-login__caption">
      <span class="split-login__caption-kicker">Pemilihan Ketua &amp; Wakil Ketua OSIS</span>
      <span class="split-login__caption-title">SMP 1 DAWE 2026</span>
    </figcaption>
  </figure>

  <main id="main" class="split-login__panel" tabindex="-1">
    <div class="split-login__inner">
      <a class="split-login__brand" href="<?= base_url('/') ?>">
        <img src="<?= base_url('assets/img/brand/logo-smp1dawe.png') ?>" alt="" width="52" height="52">
        <span>SMP 1 DAWE</span>
      </a>

      <h1 class="split-login__title">Panel Admin</h1>

      <?php if ($messages !== []): ?>
        <div class="split-login__alert" role="alert">
          <?php foreach ($messages as $message): ?>
            <p><?= esc((string) $message) ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <p class="split-login__notice" role="status"><?= esc($success) ?></p>
      <?php endif; ?>

      <form action="<?= base_url('admin/masuk') ?>" method="post" class="split-login__form" novalidate>
        <?= csrf_field() ?>

        <div class="field">
          <label for="username">Nama Pengguna</label>
          <input type="text" id="username" name="username" autocomplete="username" autocapitalize="off" spellcheck="false"
                 maxlength="100" value="<?= esc(session()->getFlashdata('old_username') ?? '', 'attr') ?>" required>
        </div>

        <?= view('partials/password_field', [
            'id'           => 'password',
            'name'         => 'password',
            'label'        => 'Kata Sandi',
            'autocomplete' => 'current-password',
            'error'        => null,
            'hint'         => null,
            'attrs'        => ' maxlength="255"',
        ]) ?>

        <button type="submit" class="btn btn--block" data-loading-text="Memeriksa...">Masuk</button>
      </form>

      <a class="split-login__back" href="<?= base_url('/') ?>"><?= icon('arrow-left') ?> Halaman Utama</a>
    </div>
  </main>
</div>
<?= $this->endSection() ?>
