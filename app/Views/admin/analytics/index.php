<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Analitik lengkap (admin saja).
 *
 * @var array|null $election
 * @var array      $snapshot AnalyticsService::snapshot()
 */
$summary    = $snapshot['summary'];
$candidates = $snapshot['candidates'];
$groups     = $snapshot['groups'];
?>
<div class="admin-page" data-live data-live-url="<?= esc(site_url('admin/live-count'), 'attr') ?>" data-live-status="<?= esc((string) ($election['status'] ?? ''), 'attr') ?>">

  <header class="admin-head">
    <div class="admin-head__text">
      <p class="eyebrow-x">Analitik</p>
      <h1 class="admin-head__title">Rekap suara</h1>
      <p class="admin-head__lede">Hanya suara berstatus terkunci (LOCKED) dari pemilih aktif. Persentase pasangan dihitung dari pemilih yang sudah memilih di setiap kelompok.</p>
    </div>
    <?= $this->include('admin/partials/live_status') ?>
  </header>

  <nav class="toc" aria-label="Bagian analitik">
    <a href="#keseluruhan">01 Keseluruhan</a>
    <a href="#jenis-pemilih">02 Jenis pemilih</a>
    <a href="#jenis-kelamin">03 Jenis kelamin siswa</a>
    <a href="#jenjang">04 Jenjang</a>
    <a href="#kelas">05 Kelas</a>
    <a href="<?= site_url('admin/analytics/votes') ?>">06 Detail suara <?= icon('arrow-right') ?></a>
  </nav>

  <section class="chapter-x" id="keseluruhan" aria-labelledby="overall-title">
    <header class="chapter-x__head">
      <span class="chapter-x__no" aria-hidden="true">01</span>
      <h2 class="chapter-x__title" id="overall-title">Keseluruhan</h2>
      <p class="chapter-x__note">Siswa dan guru digabung sebagai total partisipasi pemilihan.</p>
    </header>
    <?= view('admin/partials/scoreboard', ['summary' => $summary]) ?>
    <?= view('admin/partials/candidate_results', ['candidates' => $candidates, 'total' => $summary['all']['voted']]) ?>
  </section>

  <section class="chapter-x" id="jenis-pemilih" aria-labelledby="type-title">
    <header class="chapter-x__head">
      <span class="chapter-x__no" aria-hidden="true">02</span>
      <h2 class="chapter-x__title" id="type-title">Jenis pemilih</h2>
      <p class="chapter-x__note">Siswa dan guru memakai tabel identitas dan suara terpisah.</p>
    </header>
    <?= view('admin/partials/recap_table', [
        'groups'     => $groups['type'],
        'candidates' => $candidates,
        'key'        => 'type',
        'label'      => 'Jenis',
        'caption'    => 'Rekap suara per jenis pemilih',
    ]) ?>
  </section>

  <section class="chapter-x" id="jenis-kelamin" aria-labelledby="gender-title">
    <header class="chapter-x__head">
      <span class="chapter-x__no" aria-hidden="true">03</span>
      <h2 class="chapter-x__title" id="gender-title">Jenis kelamin siswa</h2>
      <p class="chapter-x__note">Dari kolom jenis_kelamin, bukan ditebak dari nama. Guru tidak memiliki data jenis kelamin.</p>
    </header>
    <?= view('admin/partials/recap_table', [
        'groups'     => $groups['gender'],
        'candidates' => $candidates,
        'key'        => 'gender',
        'label'      => 'Jenis kelamin',
        'caption'    => 'Rekap suara siswa per jenis kelamin',
    ]) ?>
  </section>

  <section class="chapter-x" id="jenjang" aria-labelledby="grade-title">
    <header class="chapter-x__head">
      <span class="chapter-x__no" aria-hidden="true">04</span>
      <h2 class="chapter-x__title" id="grade-title">Jenjang</h2>
      <p class="chapter-x__note">Jenjang 7/8/9 dibaca dari awal nama kelas (7A, VIII-B, IX C). Kelas lain masuk "Lainnya".</p>
    </header>
    <?= view('admin/partials/recap_table', [
        'groups'     => $groups['grade'],
        'candidates' => $candidates,
        'key'        => 'grade',
        'label'      => 'Jenjang',
        'caption'    => 'Rekap suara siswa per jenjang',
    ]) ?>
  </section>

  <section class="chapter-x" id="kelas" aria-labelledby="class-title">
    <header class="chapter-x__head">
      <span class="chapter-x__no" aria-hidden="true">05</span>
      <h2 class="chapter-x__title" id="class-title">Kelas</h2>
      <p class="chapter-x__note">Pilih nama kelas untuk melihat daftar siswanya beserta status memilih.</p>
    </header>
    <?= view('admin/partials/recap_table', [
        'groups'     => $groups['class'],
        'candidates' => $candidates,
        'key'        => 'class',
        'label'      => 'Kelas',
        'caption'    => 'Rekap suara siswa per kelas',
        'link'       => site_url('admin/students') . '?status=aktif&kelas=',
    ]) ?>
  </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/admin-live.js') ?>" defer></script>
<?= $this->endSection() ?>
