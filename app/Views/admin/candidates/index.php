<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Daftar pasangan calon + kelengkapan tema.
 *
 * @var list<array{raw: array, theme: array, votes: int, vote_rows: int, assets: array<string, string>}> $rows
 * @var int $activeCount
 */
use App\Libraries\CandidateAssets;

$layoutNames = ['split' => 'Split', 'poster' => 'Poster', 'column' => 'Kolom'];
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <p class="eyebrow-x">Data &middot; Pasangan calon</p>
      <h1 class="admin-head__title">Pasangan calon</h1>
      <p class="admin-head__lede">Identitas, visi-misi, dan tema visual yang tampil di halaman kandidat pemilih. Setiap pasangan punya warna aksen solid, layout, dan asset sendiri.</p>
    </div>
    <?php if (! ($resultsLocked ?? false)): ?>
      <div class="admin-head__actions">
        <a class="btn" href="<?= site_url('admin/candidates/new') ?>"><?= icon('plus') ?> Tambah pasangan</a>
      </div>
    <?php endif; ?>
  </header>

  <?php if ($resultsLocked ?? false): ?>
    <p class="notice"><?= icon('lock') ?><span>Pemilihan sudah selesai: susunan pasangan (tambah, hapus, nomor urut, status aktif) dikunci agar hasil akhir tidak berubah.</span></p>
  <?php endif; ?>

  <?php if ($activeCount !== 3): ?>
    <p class="notice"><?= icon('info') ?><span>Pemilihan ini dirancang untuk <strong>3 pasangan</strong>; saat ini ada <strong><?= $activeCount ?></strong> pasangan aktif.</span></p>
  <?php endif; ?>

  <?php if ($rows === []): ?>
    <p class="empty">Belum ada pasangan calon. <a href="<?= site_url('admin/candidates/new') ?>">Tambah pasangan pertama</a>.</p>
  <?php endif; ?>

  <ol class="cand-list">
    <?php foreach ($rows as $row): ?>
      <?php
        $t      = $row['theme'];
        $active = (int) $row['raw']['status_aktif'] === 1;
        $filled = count($row['assets']);
      ?>
      <li class="cand-card<?= $active ? '' : ' cand-card--inactive' ?>" style="<?= esc($t['style'], 'attr') ?>">
        <div class="cand-card__no" aria-hidden="true"><?= esc($t['label']) ?></div>

        <div class="cand-card__body">
          <p class="cand-card__eyebrow">
            Pasangan <?= esc($t['label']) ?> &middot; <?= esc($t['theme_name']) ?>
            <?php if (! $active): ?><span class="pill pill--muted">Nonaktif</span><?php endif; ?>
          </p>
          <h2 class="cand-card__names">
            <span><?= esc($t['ketua']) ?></span>
            <span class="cand-card__wakil">&amp; <?= esc($t['wakil']) ?></span>
          </h2>

          <dl class="cand-card__meta">
            <div><dt>Aksen</dt><dd><span class="swatch" aria-hidden="true"></span><span class="mono"><?= esc($t['accent']) ?></span></dd></div>
            <div><dt>Layout</dt><dd><?= esc($layoutNames[$t['layout']]) ?><?= $row['raw']['theme_layout'] === null ? ' (otomatis)' : '' ?></dd></div>
            <div><dt>Suara sah</dt><dd><?= angka($row['votes']) ?></dd></div>
            <div><dt>Asset</dt><dd><?= $filled ?> / <?= count(CandidateAssets::SLOTS) ?></dd></div>
          </dl>

          <ul class="asset-dots" aria-label="Kelengkapan asset">
            <?php foreach (CandidateAssets::SLOTS as $slot => $config): ?>
              <li class="asset-dots__item<?= isset($row['assets'][$slot]) ? ' is-filled' : '' ?>">
                <?= isset($row['assets'][$slot]) ? icon('check') : '<span class="asset-dots__empty" aria-hidden="true"></span>' ?>
                <span><?= esc($config['label']) ?><span class="visually-hidden"><?= isset($row['assets'][$slot]) ? ': sudah diunggah' : ': belum ada' ?></span></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="cand-card__actions">
          <a class="btn btn--sm" href="<?= site_url('admin/candidates/' . $t['id'] . '/edit') ?>"><?= icon('edit') ?> Ubah</a>
          <a class="btn btn--sm btn--outline" href="<?= site_url('admin/candidates/' . $t['id'] . '/preview') ?>"><?= icon('eye') ?> Pratinjau</a>
          <?php if ($resultsLocked ?? false): ?>
            <p class="cand-card__lock"><?= icon('lock') ?> Dikunci: pemilihan sudah selesai</p>
          <?php elseif ($row['vote_rows'] === 0): ?>
            <form action="<?= site_url('admin/candidates/' . $t['id'] . '/delete') ?>" method="post"
                  data-confirm="Hapus Pasangan <?= esc($t['label'], 'attr') ?> (<?= esc($t['ketua'], 'attr') ?> &amp; <?= esc($t['wakil'], 'attr') ?>) beserta semua file temanya? Tindakan ini tidak dapat dibatalkan."
                  data-confirm-button="Hapus pasangan" data-confirm-danger>
              <?= csrf_field() ?>
              <button type="submit" class="btn btn--sm btn--ghost-danger"><?= icon('trash') ?> Hapus</button>
            </form>
          <?php else: ?>
            <p class="cand-card__lock"><?= icon('lock') ?> Sudah menerima suara, tidak dapat dihapus</p>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
</div>
<?= $this->endSection() ?>
