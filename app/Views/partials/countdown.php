<?php
/**
 * Countdown pemilihan: varian "compact" (dasbor, halaman voting, hasil akhir)
 * dan "dock" (panel status bergaya terminal di beranda, redesign beranda).
 *
 * Angka awal dihitung server saat render (tetap benar tanpa JavaScript);
 * countdown.js melanjutkan hitungan dari selisih jam SERVER (data-now) memakai
 * performance.now(), jadi jam browser yang diubah tidak berpengaruh. Countdown
 * hanya visual: buka/tutup voting tetap diputuskan server.
 *
 * @var array|null $election Hasil ElectionModel::getCurrentElection()
 * @var string     $variant  'compact' | 'dock'
 */
$variant = $variant ?? 'compact';
$clock   = election_clock($election ?? null);
$status  = $clock['status'];
$target  = match ($status) {
    'UPCOMING' => $clock['start'],
    'ONGOING'  => $clock['end'],
    default    => null,
};
$remain = $target === null ? 0 : max(0, intdiv($target - $clock['now'], 1000));
$units  = [
    'days'    => ['Hari', intdiv($remain, 86400)],
    'hours'   => ['Jam', intdiv($remain % 86400, 3600)],
    'minutes' => ['Menit', intdiv($remain % 3600, 60)],
    'seconds' => ['Detik', $remain % 60],
];

// Panel terminal: "hari" hanya tampil bila sisa waktu >= 1 hari (03:12:45).
if ($variant === 'dock' && $remain < 86400) {
    unset($units['days']);
}
$label = match ($status) {
    'UPCOMING' => 'Dibuka dalam',
    'ONGOING'  => 'Ditutup dalam',
    'FINISHED' => 'Pemilihan telah selesai',
    default    => 'Jadwal belum tersedia',
};
$progress = 0.0;
if ($clock['start'] !== null && $clock['end'] > $clock['start']) {
    $progress = min(1, max(0, ($clock['now'] - $clock['start']) / ($clock['end'] - $clock['start'])));
}
$srText = match ($status) {
    'UPCOMING' => 'Pencoblosan dibuka pada ' . format_waktu($election['start_at']) . '.',
    'ONGOING'  => 'Pencoblosan ditutup pada ' . format_waktu($election['end_at']) . '.',
    'FINISHED' => 'Pemilihan telah selesai pada ' . format_waktu($election['end_at']) . '.',
    default    => 'Jadwal pemilihan belum tersedia.',
};
?>
<div class="countdown countdown--<?= esc($variant, 'attr') ?> countdown--<?= esc(strtolower((string) $status), 'attr') ?>"
     role="timer" aria-live="off"
     data-countdown
     data-status="<?= esc((string) $status, 'attr') ?>"
     data-now="<?= esc((string) $clock['now'], 'attr') ?>"
     data-start="<?= esc((string) $clock['start'], 'attr') ?>"
     data-end="<?= esc((string) $clock['end'], 'attr') ?>"
     data-clock-url="<?= esc(site_url('election/clock'), 'attr') ?>">
  <p class="countdown__label" data-countdown-label><?= esc($label) ?></p>

  <?php if ($target !== null): ?>
    <div class="countdown__units" aria-hidden="true">
      <?php foreach ($units as $key => [$name, $value]): ?>
        <div class="countdown__unit countdown__unit--<?= esc($key, 'attr') ?>">
          <span class="countdown__digits" data-unit="<?= esc($key, 'attr') ?>"><?= sprintf('%02d', $value) ?></span>
          <span class="countdown__name"><?= esc($name) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p class="visually-hidden" data-countdown-sr><?= esc($srText) ?></p>

  <?php if ($variant === 'dock' && $election): ?>
    <span class="countdown__progress" aria-hidden="true">
      <span class="countdown__progress-fill" data-timeline-fill style="--progress: <?= esc(number_format($progress, 4, '.', ''), 'attr') ?>;"></span>
    </span>
  <?php endif; ?>
</div>
