<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Analitik (admin saja). Stage 11: "pill section header" + satu bagian aktif.
 *
 * Setiap pill = URL biasa (admin/analitik/<bagian>, detail = admin/analitik/
 * suara) yang dirender penuh server, jadi tanpa JavaScript tetap berpindah
 * halaman. admin-analytics.js memuat isi bagian lewat fetch (header
 * X-Analytics-Pane) lalu mengganti isi [data-pane-host] di tempat.
 * Stage 12: selama bagian diambil tampil kerangka [data-pane-loader] (bukan
 * lagi garis progres hitam di bawah pil).
 *
 * @var array|null            $election
 * @var string                $pane  Bagian aktif (AnalyticsController::PANES)
 * @var array<string, string> $panes slug => label pill
 */
?>
<div class="admin-page" data-live data-live-url="<?= esc(site_url('admin/hitung-suara'), 'attr') ?>" data-live-status="<?= esc((string) ($election['status'] ?? ''), 'attr') ?>">

  <header class="admin-head">
    <div class="admin-head__text">
      <div class="admin-head__heading">
        <h1 class="admin-head__title">Analitik</h1>
        <?= view('admin/partials/head_hint') ?>
      </div>
      <p class="admin-head__lede" id="admin-head-lede" data-lede>Hanya suara berstatus terkunci (LOCKED) dari pemilih aktif. Persentase pasangan dihitung dari pemilih yang sudah memilih di setiap kelompok. Pilih bagian pada deretan pil; isinya dimuat tanpa memuat ulang halaman.</p>
    </div>
    <?= $this->include('admin/partials/live_status') ?>
  </header>

  <nav class="pills" aria-label="Bagian analitik" data-pane-nav>
    <div class="pills__track">
      <span class="pills__glider" aria-hidden="true" data-pane-glider></span>
      <ul class="pills__list">
        <?php foreach ($panes as $slug => $label): ?>
          <li>
            <a class="pills__item" href="<?= site_url($slug === 'keseluruhan' ? 'admin/analitik' : 'admin/analitik/' . $slug) ?>" data-pane-link="<?= esc($slug, 'attr') ?>"<?= $slug === $pane ? ' aria-current="page"' : '' ?>><?= esc($label) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </nav>

  <div class="pane-stage">
    <div class="pane-host" data-pane-host>
      <?= $this->include('admin/analytics/pane') ?>
    </div>
    <div class="pane-loader" data-pane-loader hidden aria-hidden="true">
      <div class="pane-loader__inner">
        <p class="pane-loader__head"><span class="pane-loader__spinner"></span><span data-pane-loader-label>Memuat bagian</span></p>
        <span class="skel skel--title"></span>
        <span class="skel skel--line"></span>
        <span class="skel skel--line skel--short"></span>
        <div class="pane-loader__cards">
          <span class="skel skel--card"></span>
          <span class="skel skel--card"></span>
          <span class="skel skel--card"></span>
        </div>
        <span class="skel skel--row"></span>
        <span class="skel skel--row"></span>
        <span class="skel skel--row"></span>
        <span class="skel skel--row"></span>
      </div>
    </div>
  </div>
  <p class="visually-hidden" role="status" aria-live="polite" data-pane-announce></p>
</div>
<?= $this->endSection() ?>

<?= $this->section('crumbs') ?>
<?= view('admin/partials/crumbs', ['trail' => [['Analitik']]]) ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('assets/js/admin-live.js') ?>" defer></script>
<script src="<?= asset_url('assets/js/admin-analytics.js') ?>" defer></script>
<?= $this->endSection() ?>
