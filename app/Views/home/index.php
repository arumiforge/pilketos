<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $status = $election['status'] ?? null; ?>

<section class="hero">
  <div class="container">
    <p class="eyebrow">Pemilihan Ketua &amp; Wakil Ketua OSIS</p>
    <h1>SMP 1 Dawe<br>Tahun 2026</h1>

    <?php if ($election): ?>
      <p class="hero__meta">
        <span class="badge badge--<?= esc(strtolower($status), 'attr') ?>">
          <span class="badge__dot" aria-hidden="true"></span>
          <?= esc(election_status_label($status)) ?>
        </span>
      </p>
      <dl class="hero__meta">
        <div>
          <dt class="text-muted text-small">Mulai</dt>
          <dd><time datetime="<?= esc($election['start_at'], 'attr') ?>"><?= esc(format_waktu($election['start_at'])) ?></time></dd>
        </div>
        <div>
          <dt class="text-muted text-small">Selesai</dt>
          <dd><time datetime="<?= esc($election['end_at'], 'attr') ?>"><?= esc(format_waktu($election['end_at'])) ?></time></dd>
        </div>
      </dl>
    <?php else: ?>
      <p class="text-muted hero__empty">Jadwal pemilihan belum tersedia. Silakan cek kembali nanti.</p>
    <?php endif; ?>

    <div class="hero__actions">
      <a href="<?= base_url('student/login') ?>" class="btn">Masuk sebagai Siswa</a>
      <a href="<?= base_url('teacher/login') ?>" class="btn btn--outline">Masuk sebagai Guru</a>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
