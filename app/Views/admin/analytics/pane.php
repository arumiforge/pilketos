<?php
/**
 * Satu bagian analitik (Stage 11): judul + catatan + isi bagian.
 *
 * Dipakai di halaman penuh (admin/analytics/index) dan sebagai jawaban fetch
 * admin-analytics.js (tanpa layout). data-pane-title = judul tab browser.
 * Judul bab tanpa penomoran (dulu "01"-"05").
 * Stage 13: judul Title Case, catatan bab berbahasa sederhana (tanpa istilah
 * teknis seperti nama kolom database atau User-Agent).
 *
 * @var string                $pane
 * @var array<string, string> $panes
 * @var string                $title
 * @var int|null              $counted Total suara sah (hanya bagian "suara")
 */
$headings = [
    'jenis-kelamin' => 'Jenis Kelamin Siswa',
    'kelas'         => 'Rekap Kelas',
    'rombel'        => 'Rekap Rombel',
] + $panes;
$notes = [
    'keseluruhan'   => 'Gabungan suara siswa dan guru.',
    'jenis-pemilih' => 'Perbandingan suara siswa dan guru.',
    'jenis-kelamin' => 'Suara siswa menurut jenis kelamin. Guru tidak termasuk.',
    'kelas'         => 'Suara siswa menurut kelas 7, 8, dan 9.',
    'rombel'        => 'Pilih nama rombel untuk melihat siswa yang sudah dan belum memilih.',
    'suara'         => 'Daftar suara yang masuk beserta waktu dan perangkatnya. Jumlah suara sah sama dengan di Beranda ('
        . angka($counted ?? 0) . ').',
];
?>
<div class="pane" data-pane="<?= esc($pane, 'attr') ?>" data-pane-title="<?= esc($title . ' — Admin Pemilihan OSIS SMP 1 DAWE', 'attr') ?>">
  <section class="chapter-x" aria-labelledby="pane-title">
    <header class="chapter-x__head">
      <h2 class="chapter-x__title" id="pane-title" tabindex="-1"><?= esc($headings[$pane]) ?></h2>
      <p class="chapter-x__note"><?= esc($notes[$pane]) ?></p>
    </header>
    <?= $this->include('admin/analytics/panes/' . $pane) ?>
  </section>
</div>
