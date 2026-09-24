<?php
/**
 * Satu bagian analitik (Stage 11): judul + catatan + isi bagian.
 *
 * Dipakai di halaman penuh (admin/analytics/index) dan sebagai jawaban fetch
 * admin-analytics.js (tanpa layout). data-pane-title = judul tab browser.
 * Judul bab tanpa penomoran (dulu "01"-"05").
 *
 * @var string                $pane
 * @var array<string, string> $panes
 * @var string                $title
 * @var int|null              $counted Total suara sah (hanya bagian "suara")
 */
$headings = [
    'jenis-kelamin' => 'Jenis kelamin siswa',
    'kelas'         => 'Rekap kelas',
    'rombel'        => 'Rekap rombel',
] + $panes;
$notes = [
    'keseluruhan'   => 'Siswa dan guru digabung sebagai total partisipasi pemilihan.',
    'jenis-pemilih' => 'Siswa dan guru memakai tabel identitas dan suara terpisah.',
    'jenis-kelamin' => 'Dari kolom jenis_kelamin, bukan ditebak dari nama. Guru tidak memiliki data jenis kelamin.',
    'kelas'         => 'Kelas 7/8/9 dibaca dari awal nama rombel (7A, VIII-B, IX C). Rombel lain masuk "Lainnya".',
    'rombel'        => 'Pilih nama rombel untuk melihat daftar siswanya beserta status memilih.',
    'suara'         => 'Bawaan: suara sah (terkunci) dari pemilih aktif, jumlahnya sama dengan total suara di dasbor ('
        . angka($counted ?? 0) . '). Perangkat dan browser dibaca server dari User-Agent (dan Client Hints bila ada) saat mencoblos.',
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
