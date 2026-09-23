<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $status = $election['status'] ?? null; ?>

<section class="hero-x" aria-labelledby="hero-title">
  <div class="container">
    <div class="hero-x__top">
      <p class="hero-x__kicker">Pemilihan Ketua &amp; Wakil Ketua OSIS</p>
      <?php if ($election): ?>
        <span class="badge badge--<?= esc(strtolower((string) $status), 'attr') ?>">
          <span class="badge__dot" aria-hidden="true"></span>
          <?= esc(election_status_label($status)) ?>
        </span>
      <?php endif; ?>
    </div>

    <h1 class="hero-x__title" id="hero-title">
      <span class="hero-x__school">SMP 1 Dawe</span>
      <span class="hero-x__year"><span class="visually-hidden">Tahun </span>2026</span>
    </h1>

    <?php if ($candidates !== []): ?>
      <div class="hero-x__bands" aria-hidden="true">
        <?php foreach ($candidates as $c): ?>
          <span class="hero-x__band" style="<?= esc($c['style'], 'attr') ?>"><span><?= esc($c['label']) ?></span></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="hero-x__lede">Satu pemilih, satu suara. Kenali visi dan misi setiap pasangan, lalu coblos pilihanmu di surat suara digital.</p>
  </div>
</section>

<section class="clock-band" aria-label="Waktu pemilihan">
  <div class="container">
    <?php if ($election): ?>
      <?= view('partials/countdown', ['election' => $election, 'variant' => 'hero']) ?>
      <?php if ($status === 'FINISHED'): ?>
        <p class="clock-band__note">Pencoblosan sudah ditutup. Hasil resmi diumumkan oleh panitia pemilihan OSIS.</p>
      <?php endif; ?>
    <?php else: ?>
      <p class="clock-band__empty">Jadwal pemilihan belum tersedia. Silakan cek kembali nanti.</p>
    <?php endif; ?>
  </div>
</section>

<section class="entry" aria-label="Masuk untuk memilih">
  <div class="container entry__grid">
    <a class="entry__link" href="<?= base_url('student/login') ?>">
      <span class="entry__who">Masuk sebagai Siswa</span>
      <span class="entry__how">NISN &amp; kode unik (tanggal lahir)</span>
      <?= icon('arrow-right', 'entry__arrow') ?>
    </a>
    <a class="entry__link" href="<?= base_url('teacher/login') ?>">
      <span class="entry__who">Masuk sebagai Guru</span>
      <span class="entry__how">NIP &amp; kode unik (tanggal lahir)</span>
      <?= icon('arrow-right', 'entry__arrow') ?>
    </a>
  </div>
</section>

<?php if ($candidates !== []): ?>
  <section class="teaser" aria-labelledby="teaser-title">
    <div class="container">
      <div class="teaser__head">
        <h2 class="teaser__title" id="teaser-title">Pasangan calon</h2>
        <p class="text-muted text-small">Visi, misi, dan surat suara tersedia setelah masuk.</p>
      </div>
      <ol class="teaser__list">
        <?php foreach ($candidates as $c): ?>
          <li class="teaser__item" style="<?= esc($c['style'], 'attr') ?>">
            <span class="teaser__no" aria-hidden="true"><?= esc($c['label']) ?></span>
            <span class="teaser__body">
              <span class="visually-hidden">Pasangan <?= esc($c['label']) ?>:</span>
              <span class="teaser__name"><?= esc($c['ketua']) ?></span>
              <span class="teaser__name teaser__name--wakil"><?= esc($c['wakil']) ?></span>
            </span>
            <span class="teaser__theme"><?= esc($c['theme_name']) ?></span>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('assets/js/countdown.js') ?>" defer></script>
<?= $this->endSection() ?>
