<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Dasbor admin + live count.
 *
 * @var array|null $election
 * @var array      $snapshot AnalyticsService::snapshot()
 * @var array      $final    FinalResult::build() (Stage 4)
 */
$status   = $election['status'] ?? null;
$summary  = $snapshot['summary'];
$heading  = match ($status) {
    'ONGOING'  => 'Hasil sementara',
    'FINISHED' => 'Hasil akhir',
    default    => 'Perolehan suara',
};
?>
<div class="admin-page" data-live data-live-url="<?= esc(site_url('admin/hitung-suara'), 'attr') ?>" data-live-status="<?= esc((string) $status, 'attr') ?>">

  <header class="admin-head">
    <div class="admin-head__text">
      <p class="eyebrow-x">Dasbor &middot; Live count</p>
      <h1 class="admin-head__title"><?= $election ? esc($election['nama']) : 'Belum ada jadwal pemilihan' ?></h1>
      <?php if ($election): ?>
        <p class="admin-head__lede">Tahun <?= esc((string) $election['tahun']) ?> &middot; seluruh angka dihitung dari suara terkunci pemilih aktif.</p>
      <?php endif; ?>
    </div>
    <?= $this->include('admin/partials/live_status') ?>
  </header>

  <?php if ($final['available']): ?>
    <?= view('admin/partials/final_banner', ['final' => $final]) ?>
  <?php endif; ?>

  <?php if (! $election): ?>
    <div class="notice notice--action">
      <?= icon('calendar') ?>
      <p>Pemilihan belum dijadwalkan. Tentukan nama, tahun, waktu mulai, dan waktu selesai agar siswa dan guru dapat mencoblos.</p>
      <a class="btn btn--sm" href="<?= site_url('admin/jadwal') ?>">Atur jadwal</a>
    </div>
  <?php else: ?>
    <section class="schedule-strip" aria-label="Status dan jadwal pemilihan">
      <dl class="schedule-strip__list">
        <div>
          <dt>Status</dt>
          <dd>
            <span class="badge badge--<?= esc(strtolower((string) $status), 'attr') ?>" data-live-badge-class>
              <span class="badge__dot" aria-hidden="true"></span>
              <span data-live-badge-label><?= esc(election_status_label($status)) ?></span>
            </span>
          </dd>
        </div>
        <div>
          <dt>Mulai</dt>
          <dd><time datetime="<?= esc($election['start_at'], 'attr') ?>"><?= esc(format_waktu($election['start_at'])) ?></time></dd>
        </div>
        <div>
          <dt>Selesai</dt>
          <dd><time datetime="<?= esc($election['end_at'], 'attr') ?>"><?= esc(format_waktu($election['end_at'])) ?></time></dd>
        </div>
      </dl>
      <?php if (in_array($status, ['UPCOMING', 'ONGOING'], true)): ?>
        <?= view('partials/countdown', ['election' => $election, 'variant' => 'compact']) ?>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?= view('admin/partials/scoreboard', ['summary' => $summary]) ?>

  <div class="dash-cols">
    <section class="panel" aria-labelledby="results-title">
      <header class="panel__head">
        <h2 class="panel__title" id="results-title" data-live-results-title><?= esc($heading) ?></h2>
        <a class="panel__link" href="<?= site_url('admin/analitik') ?>">Analitik lengkap <?= icon('arrow-right') ?></a>
      </header>
      <?= view('admin/partials/candidate_results', ['candidates' => $snapshot['candidates'], 'total' => $summary['all']['voted']]) ?>
    </section>

    <section class="panel" aria-labelledby="voters-title">
      <header class="panel__head">
        <h2 class="panel__title" id="voters-title">Pemilih</h2>
      </header>
      <div class="voter-split">
        <?php foreach (['students' => ['Siswa', 'admin/siswa'], 'teachers' => ['Guru', 'admin/guru']] as $key => [$label, $path]): ?>
          <?php $t = $summary[$key]; ?>
          <article class="voter-split__item">
            <h3 class="voter-split__title"><?= esc($label) ?></h3>
            <p class="voter-split__big"><span data-live-value="summary.<?= $key ?>.participation" data-live-format="percent"><?= persen($t['participation']) ?></span> <span class="voter-split__unit">partisipasi</span></p>
            <span class="meter" aria-hidden="true"><span class="meter__fill" style="width: <?= esc(number_format($t['participation'], 2, '.', ''), 'attr') ?>%;" data-live-width="summary.<?= $key ?>.participation"></span></span>
            <dl class="voter-split__nums">
              <div><dt>Total aktif</dt><dd data-live-value="summary.<?= $key ?>.total"><?= angka($t['total']) ?></dd></div>
              <div><dt>Sudah</dt><dd data-live-value="summary.<?= $key ?>.voted"><?= angka($t['voted']) ?></dd></div>
              <div><dt>Belum</dt><dd data-live-value="summary.<?= $key ?>.not_voted"><?= angka($t['not_voted']) ?></dd></div>
            </dl>
            <p class="voter-split__links">
              <a href="<?= site_url($path . '?vote=belum') ?>">Lihat yang belum memilih</a>
              <a href="<?= site_url($path . '/impor') ?>">Impor data</a>
            </p>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <section class="panel" aria-labelledby="grade-title">
    <header class="panel__head">
      <h2 class="panel__title" id="grade-title">Rekap jenjang</h2>
      <p class="panel__note">Siswa aktif, jenjang dibaca dari nama kelas.</p>
    </header>
    <?= view('admin/partials/recap_table', [
        'groups'     => $snapshot['groups']['grade'],
        'candidates' => $snapshot['candidates'],
        'key'        => 'grade',
        'label'      => 'Jenjang',
        'caption'    => 'Rekap suara siswa per jenjang',
    ]) ?>
  </section>

  <section class="panel" aria-labelledby="class-title">
    <header class="panel__head">
      <h2 class="panel__title" id="class-title">Rekap kelas</h2>
      <a class="panel__link" href="<?= site_url('admin/analitik/suara') ?>">Detail suara <?= icon('arrow-right') ?></a>
    </header>
    <?= view('admin/partials/recap_table', [
        'groups'     => $snapshot['groups']['class'],
        'candidates' => $snapshot['candidates'],
        'key'        => 'class',
        'label'      => 'Kelas',
        'caption'    => 'Rekap suara siswa per kelas',
        'link'       => site_url('admin/siswa') . '?status=aktif&kelas=',
    ]) ?>
  </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('assets/js/countdown.js') ?>" defer></script>
<script src="<?= asset_url('assets/js/admin-live.js') ?>" defer></script>
<?= $this->endSection() ?>
