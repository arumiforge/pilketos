<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Hasil import.
 *
 * @var \App\Services\VoterType $type
 * @var array{created: int, updated: int, unchanged: int, skipped: int, file_name: string, rows: int} $result
 */
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <p class="eyebrow-x">Impor <?= esc(strtolower($type->label())) ?> &middot; Hasil</p>
      <h1 class="admin-head__title">Impor selesai</h1>
      <p class="admin-head__lede">File <strong><?= esc($result['file_name']) ?></strong> &middot; <?= angka($result['rows']) ?> baris dibaca. Tindakan ini tercatat di audit log.</p>
    </div>
  </header>

  <ol class="steps" aria-label="Tahapan impor">
    <li class="steps__item is-done"><span>01</span> Unduh template</li>
    <li class="steps__item is-done"><span>02</span> Unggah</li>
    <li class="steps__item is-done"><span>03</span> Pratinjau &amp; validasi</li>
    <li class="steps__item is-done"><span>04</span> Impor</li>
    <li class="steps__item is-current"><span>05</span> Hasil</li>
  </ol>

  <dl class="scoreboard scoreboard--compact" role="status">
    <div class="scoreboard__cell"><dt>Ditambahkan</dt><dd class="scoreboard__value"><?= angka($result['created']) ?></dd></div>
    <div class="scoreboard__cell"><dt>Diperbarui</dt><dd class="scoreboard__value"><?= angka($result['updated']) ?></dd></div>
    <div class="scoreboard__cell"><dt>Tidak berubah</dt><dd class="scoreboard__value"><?= angka($result['unchanged']) ?></dd></div>
    <div class="scoreboard__cell<?= $result['skipped'] > 0 ? ' scoreboard__cell--danger' : '' ?>"><dt>Dilewati</dt><dd class="scoreboard__value"><?= angka($result['skipped']) ?></dd><dd class="scoreboard__sub">baris bermasalah</dd></div>
  </dl>

  <div class="cluster">
    <a class="btn" href="<?= site_url($type->adminPath()) ?>"><?= icon('users') ?> Lihat data <?= esc(strtolower($type->label())) ?></a>
    <a class="btn btn--outline" href="<?= site_url($type->adminPath('impor')) ?>"><?= icon('upload') ?> Impor file lain</a>
  </div>
</div>
<?= $this->endSection() ?>
