<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= base_url('assets/css/voting.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
/**
 * Pratinjau halaman kandidat untuk admin: memakai partial Stage 2 yang sama
 * dengan halaman pemilih (voting/partials/chapter) sehingga tema yang
 * diunggah terlihat persis. Surat suara tidak ditampilkan.
 *
 * @var array      $candidate CandidateTheme::present()
 * @var bool       $active
 * @var list<array> $switcher
 */
?>
<div class="preview-bar">
  <div class="container preview-bar__row">
    <p class="preview-bar__text"><?= icon('eye') ?> <span><strong>Pratinjau admin.</strong> Tampilan sama dengan halaman kandidat pemilih; surat suara tidak ditampilkan.<?= $active ? '' : ' Pasangan ini nonaktif sehingga tidak tampil untuk pemilih.' ?></span></p>
    <nav class="preview-bar__nav" aria-label="Pratinjau pasangan lain">
      <?php foreach ($switcher as $c): ?>
        <a href="<?= site_url('admin/candidates/' . $c['id'] . '/preview') ?>" style="<?= esc($c['style'], 'attr') ?>"<?= $c['id'] === $candidate['id'] ? ' aria-current="page"' : '' ?>><span class="chapter-nav__swatch" aria-hidden="true"></span><?= esc($c['label']) ?></a>
      <?php endforeach; ?>
      <a class="preview-bar__back" href="<?= site_url('admin/candidates/' . $candidate['id'] . '/edit') ?>"><?= icon('edit') ?> Ubah</a>
    </nav>
  </div>
</div>

<?= view('voting/partials/chapter', ['c' => $candidate, 'canVote' => false]) ?>

<section class="section" id="surat-suara">
  <div class="container">
    <p class="notice"><?= icon('info') ?><span>Surat suara dan paku coblos hanya tersedia untuk siswa dan guru yang login. Admin tidak dapat memberikan suara.</span></p>
  </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/candidates.js') ?>" defer></script>
<?= $this->endSection() ?>
