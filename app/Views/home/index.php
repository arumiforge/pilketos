<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$statusLabel = [
    'UPCOMING' => 'Belum Dibuka',
    'ONGOING'  => 'Sedang Berlangsung',
    'FINISHED' => 'Sudah Selesai',
];
$badgeClass = [
    'UPCOMING' => 'badge--upcoming',
    'ONGOING'  => 'badge--ongoing',
    'FINISHED' => 'badge--finished',
];
?>

<section class="hero">
  <div class="container">
    <p class="eyebrow">Pemilihan Ketua &amp; Wakil Ketua OSIS</p>
    <h1>SMP 1 Dawe<br>Tahun 2026</h1>

    <?php if ($election): ?>
      <p class="hero__meta">
        <span class="badge <?= $badgeClass[$status] ?? '' ?>">
          <span class="badge__dot"></span>
          <?= esc($statusLabel[$status] ?? $status) ?>
        </span>
      </p>
      <dl class="hero__meta">
        <div>
          <dt class="text-muted text-small">Mulai</dt>
          <dd><?= esc(date('d M Y, H:i', strtotime($election['start_at']))) ?> WIB</dd>
        </div>
        <div>
          <dt class="text-muted text-small">Selesai</dt>
          <dd><?= esc(date('d M Y, H:i', strtotime($election['end_at']))) ?> WIB</dd>
        </div>
      </dl>
    <?php else: ?>
      <p class="text-muted" style="margin-top: var(--space-5);">Jadwal pemilihan belum tersedia. Silakan cek kembali nanti, ya.</p>
    <?php endif; ?>

    <div class="hero__actions">
      <a href="<?= base_url('student/login') ?>" class="btn">Masuk sebagai Siswa</a>
      <a href="<?= base_url('teacher/login') ?>" class="btn btn--outline">Masuk sebagai Guru</a>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
