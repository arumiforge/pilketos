<?php
/**
 * PDF hasil akhir (Stage 13, dirender App\Libraries\ResultPdf / dompdf).
 *
 * dompdf hanya memahami CSS 2.1 (+ sebagian CSS3): tata letak memakai tabel,
 * tanpa flex/grid. Halaman A4 tegak:
 * - halaman 1: logo SMP 1 DAWE (pengganti judul halaman), kicker & judul rata
 *   tengah, stempel waktu/suara/partisipasi, pasangan terpilih, perolehan
 *   suara, partisipasi;
 * - halaman 2: rekap per pemilih, kelas, dan jenis kelamin siswa;
 * - setiap halaman: catatan kaki miring rata tengah selebar kertas (elemen
 *   fixed, FinalResult::footnote()) + nomor halaman (ResultPdf).
 * Catatan seri (perolehan tertinggi sama) sengaja tidak dicetak.
 *
 * @var array                            $election
 * @var array                            $snapshot AnalyticsService::snapshot()
 * @var array<string, mixed>             $result   FinalResult::build()
 * @var string|null                      $logo     Data URI logo sekolah
 * @var array<int, array<string, mixed>> $photos   id pasangan => [hero, ketua, wakil] data URI|null
 */
use App\Services\FinalResult;

$all    = $snapshot['summary']['all'];
$winner = $result['winner'];
$cands  = $snapshot['candidates'];
$recaps = [
    ['Pemilih', $snapshot['groups']['type'], 'Hasil akhir per pemilih (siswa dan guru)'],
    ['Kelas', $snapshot['groups']['grade'], 'Hasil akhir siswa per kelas (7/8/9)'],
    ['Jenis Kelamin Siswa', $snapshot['groups']['gender'], 'Hasil akhir siswa per jenis kelamin'],
];
$initials = static fn (string $name): string => mb_strtoupper(implode('', array_map(
    static fn (string $part): string => mb_substr($part, 0, 1),
    array_slice(preg_split('/\s+/u', trim($name)) ?: [], 0, 2),
)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Hasil Akhir Pemilihan Ketua &amp; Wakil Ketua OSIS SMP 1 DAWE <?= esc((string) $election['tahun']) ?></title>
<style>
  @page { margin: 12mm 16mm 26mm 16mm; }

  * { box-sizing: border-box; }

  body {
    margin: 0;
    font-family: 'PlusJakartaSans', sans-serif;
    font-size: 9.5pt;
    line-height: 1.4;
    color: #15141A;
  }

  .serif { font-family: 'Newsreader', serif; }

  /* catatan kaki di setiap halaman */
  .foot {
    position: fixed;
    right: 0;
    bottom: -16mm;
    left: 0;
    padding-top: 2mm;
    border-top: 0.6pt solid #15141A;
    font-size: 7.5pt;
    font-style: italic;
    line-height: 1.45;
    color: #514E45;
    text-align: center;
  }

  .masthead { text-align: center; }

  .logo { width: 18mm; height: 18mm; }

  .kicker {
    margin: 2.5mm 0 0;
    font-size: 7.5pt;
    font-weight: bold;
    letter-spacing: 1.6pt;
    text-transform: uppercase;
    color: #514E45;
  }

  .title {
    margin: 1.5mm 0 0;
    font-family: 'Newsreader', serif;
    font-size: 19pt;
    font-weight: normal;
    line-height: 1.12;
  }

  .rule-double {
    margin-top: 3.5mm;
    border-top: 2.4pt double #15141A;
  }

  table { width: 100%; border-collapse: collapse; }

  .stamp td {
    width: 30%;
    padding: 2mm 3mm 2mm 0;
    border-bottom: 0.6pt solid #DCD8CD;
    vertical-align: top;
  }

  .stamp td + td { padding-left: 3mm; border-left: 0.6pt solid #DCD8CD; }

  .label {
    font-size: 6.8pt;
    font-weight: bold;
    letter-spacing: 1.1pt;
    text-transform: uppercase;
    color: #514E45;
  }

  .stamp .value {
    margin-top: 0.8mm;
    font-family: 'Newsreader', serif;
    font-size: 12pt;
  }

  .muted { color: #514E45; }
  .small { font-size: 8pt; }

  h2 {
    margin: 4.5mm 0 2mm;
    padding-bottom: 1.5mm;
    border-bottom: 0.6pt solid #DCD8CD;
    font-family: 'Newsreader', serif;
    font-size: 13pt;
    font-weight: normal;
  }

  /* pasangan terpilih */
  .victor {
    margin-top: 5mm;
    padding: 4mm 5mm;
    border: 1.8pt solid #15141A;
    page-break-inside: avoid;
  }

  .victor .label { margin-bottom: 2.5mm; }

  /* dompdf: tinggi = isi (padding di luar tinggi); total 21 mm */
  .victor-no {
    width: 21mm;
    height: 15.1mm;
    font-family: 'Newsreader', serif;
    padding-top: 5.9mm;
    font-size: 26pt;
    font-weight: bold;
    line-height: 1;
    text-align: center;
  }

  .victor td { vertical-align: middle; }

  .photo {
    width: 20mm;
    height: 25mm;
    border: 0.6pt solid #B9B4A6;
  }

  .hero { width: 44mm; border: 0.6pt solid #B9B4A6; }

  .mono-box {
    width: 20mm;
    height: 15.2mm;
    border: 0.6pt solid #B9B4A6;
    font-family: 'Newsreader', serif;
    padding-top: 9.8mm;
    font-size: 15pt;
    font-weight: bold;
    line-height: 1;
    text-align: center;
  }

  .photo-cap {
    margin-top: 1mm;
    font-size: 6.5pt;
    font-weight: bold;
    letter-spacing: 1pt;
    text-transform: uppercase;
    text-align: center;
  }

  .role {
    font-size: 6.8pt;
    font-weight: bold;
    letter-spacing: 1pt;
    text-transform: uppercase;
    color: #514E45;
  }

  .person {
    font-family: 'Newsreader', serif;
    font-size: 14pt;
    line-height: 1.15;
  }

  .person + .role { margin-top: 2.5mm; }

  .tally {
    margin-top: 3mm;
    padding-top: 2mm;
    border-top: 0.6pt solid #15141A;
  }

  .tally strong { font-family: 'Newsreader', serif; font-size: 12pt; }

  .note {
    margin-top: 6mm;
    padding: 3mm 4mm;
    border: 0.6pt solid #B9B4A6;
    border-left: 3pt solid #15141A;
    background: #F1EFEA;
  }

  /* perolehan suara */
  .standings { border-top: 1.4pt solid #15141A; }

  .standings td {
    padding: 1.9mm 2mm;
    border-bottom: 0.6pt solid #DCD8CD;
    vertical-align: middle;
  }

  .rank {
    width: 8mm;
    font-family: 'Newsreader', serif;
    font-size: 12pt;
    color: #514E45;
  }

  .chip {
    width: 10mm;
    height: 7.1mm;
    font-family: 'Newsreader', serif;
    padding-top: 2.9mm;
    font-size: 12pt;
    font-weight: bold;
    line-height: 1;
    text-align: center;
  }

  .names strong { font-size: 10pt; }

  .names .split { font-size: 8pt; color: #514E45; }

  /* judul bagian tidak terpisah dari isinya di akhir halaman */
  .keep { page-break-inside: avoid; }

  .track {
    height: 2.4mm;
    margin-top: 1.4mm;
    background: #E3E0D8;
  }

  .fill { height: 2.4mm; }

  .votes {
    width: 26mm;
    text-align: right;
  }

  .votes .n {
    font-family: 'Newsreader', serif;
    font-size: 15pt;
    line-height: 1;
  }

  .pill {
    padding: 0.4mm 1.8mm;
    border: 0.6pt solid #15141A;
    font-size: 6.8pt;
    font-weight: bold;
    letter-spacing: 0.6pt;
    text-transform: uppercase;
  }

  .is-winner .rank { color: #15141A; font-weight: bold; }

  /* partisipasi */
  .turnout { border-top: 1.4pt solid #15141A; }

  .turnout td {
    width: 33.33%;
    padding: 2.5mm 3mm 2.5mm 0;
    border-bottom: 0.6pt solid #DCD8CD;
    vertical-align: top;
  }

  .turnout .big {
    margin-top: 1mm;
    font-family: 'Newsreader', serif;
    font-size: 18pt;
    line-height: 1;
  }

  /* rekap (halaman 2) */
  .recap-page { page-break-before: always; }

  .recap-page h2:first-child { margin-top: 0; }

  .recap-title {
    margin: 6mm 0 2mm;
    font-size: 8pt;
    font-weight: bold;
    letter-spacing: 1pt;
    text-transform: uppercase;
  }

  .recap { border-top: 1.4pt solid #15141A; font-size: 8.5pt; page-break-inside: avoid; }

  .recap th,
  .recap td {
    padding: 1.8mm 1.6mm;
    border-bottom: 0.6pt solid #DCD8CD;
    text-align: right;
    vertical-align: top;
  }

  .recap th:first-child,
  .recap td:first-child { text-align: left; }

  .recap thead th {
    font-size: 6.8pt;
    font-weight: bold;
    letter-spacing: 0.6pt;
    text-transform: uppercase;
    color: #514E45;
    vertical-align: bottom;
  }

  .recap .mini {
    display: inline-block;
    width: 6.5mm;
    height: 3.7mm;
    padding-top: 0.9mm;
    font-family: 'Newsreader', serif;
    font-size: 8pt;
    font-weight: bold;
    line-height: 1;
    text-align: center;
  }

  .recap .pct { display: block; font-size: 7pt; color: #514E45; }

  .num { font-variant-numeric: tabular-nums; }
</style>
</head>
<body>

<div class="foot"><?= esc(FinalResult::footnote($snapshot['generated_at'])) ?></div>

<div class="masthead">
  <?php if ($logo !== null): ?><img class="logo" src="<?= $logo ?>" alt="Logo SMP 1 DAWE"><?php endif; ?>
  <p class="kicker">Rekapitulasi resmi penghitungan suara</p>
  <h1 class="title">Pemilihan Ketua &amp; Wakil Ketua OSIS<br>SMP 1 DAWE <?= esc((string) $election['tahun']) ?></h1>
</div>

<div class="rule-double"></div>
<table class="stamp">
  <tr>
    <td style="width: 40%;"><div class="label">Ditutup</div><div class="value"><?= esc(format_waktu($election['end_at'])) ?></div></td>
    <td><div class="label">Suara sah</div><div class="value"><?= angka($all['voted']) ?></div></td>
    <td><div class="label">Partisipasi</div><div class="value"><?= persen($all['participation']) ?> <span class="small muted">dari <?= angka($all['total']) ?> pemilih</span></div></td>
  </tr>
</table>

<?php if ($result['state'] === FinalResult::WINNER): ?>
  <?php $p = $photos[(int) $winner['id']] ?? []; ?>
  <div class="victor">
    <div class="label">Pasangan terpilih</div>
    <table>
      <tr>
        <td style="width: 25mm;"><div class="victor-no" style="background: <?= esc($winner['accent'], 'attr') ?>; color: <?= esc($winner['accent_ink'], 'attr') ?>;"><?= esc($winner['label']) ?></div></td>
        <?php if (($p['hero'] ?? null) !== null): ?>
          <td style="width: 48mm;"><img class="hero" src="<?= $p['hero'] ?>" alt="Foto pasangan <?= esc($winner['label'], 'attr') ?>"></td>
        <?php else: ?>
          <?php foreach ([['Ketua', $winner['ketua'], $p['ketua'] ?? null], ['Wakil', $winner['wakil'], $p['wakil'] ?? null]] as [$role, $name, $photo]): ?>
            <td style="width: 24mm;">
              <?php if ($photo !== null): ?>
                <img class="photo" src="<?= $photo ?>" alt="Foto <?= esc($name, 'attr') ?>">
              <?php else: ?>
                <div class="mono-box"><?= esc($initials($name)) ?></div>
              <?php endif; ?>
              <div class="photo-cap"><?= esc($role) ?></div>
            </td>
          <?php endforeach; ?>
        <?php endif; ?>
        <td style="padding-left: 3mm;">
          <div class="role">Ketua OSIS</div>
          <div class="person"><?= esc($winner['ketua']) ?></div>
          <div class="role">Wakil Ketua OSIS</div>
          <div class="person"><?= esc($winner['wakil']) ?></div>
        </td>
      </tr>
    </table>
    <div class="tally">
      <strong><?= angka($winner['votes']) ?></strong> suara &middot; <strong><?= persen($winner['percent']) ?></strong> dari suara sah<?php if ($result['margin'] > 0 && count($result['ranking']) > 1): ?> &middot; unggul <?= angka($result['margin']) ?> suara dari peringkat 2<?php endif; ?>
    </div>
  </div>
<?php elseif ($result['state'] === FinalResult::NO_VOTES): ?>
  <div class="note"><strong>Tidak ada suara sah.</strong> Pemilihan selesai tanpa suara sah, sehingga tidak ada pasangan yang ditetapkan.</div>
<?php endif; ?>

<div class="keep">
<h2>Perolehan suara</h2>
<?php if ($result['ranking'] === []): ?>
  <p class="muted">Tidak ada pasangan calon.</p>
<?php else: ?>
  <table class="standings">
    <?php foreach ($result['ranking'] as $c): ?>
      <?php $isWinner = $winner !== null && (int) $winner['id'] === (int) $c['id']; ?>
      <tr class="<?= $isWinner ? 'is-winner' : '' ?>">
        <td class="rank"><?= (int) $c['rank'] ?></td>
        <td style="width: 14mm;"><div class="chip" style="background: <?= esc($c['accent'], 'attr') ?>; color: <?= esc($c['accent_ink'], 'attr') ?>;"><?= esc($c['label']) ?></div></td>
        <td class="names">
          <strong><?= esc($c['ketua']) ?></strong> &amp; <?= esc($c['wakil']) ?>
          <?php if ($isWinner): ?>&nbsp; <span class="pill">Terpilih</span><?php endif; ?>
          <?php if (! $c['active']): ?>&nbsp; <span class="pill">Nonaktif</span><?php endif; ?>
          &nbsp; <span class="split">Siswa <?= angka($c['student_votes']) ?> &middot; Guru <?= angka($c['teacher_votes']) ?></span>
          <div class="track"><div class="fill" style="width: <?= esc(number_format(min(100, max(0, $c['percent'])), 2, '.', ''), 'attr') ?>%; background: <?= esc($c['accent'], 'attr') ?>;"></div></div>
        </td>
        <td class="votes"><div class="n"><?= angka($c['votes']) ?></div><div class="small"><strong><?= persen($c['percent']) ?></strong></div></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
</div>

<div class="keep">
<h2>Partisipasi</h2>
<table class="turnout">
  <tr>
    <?php foreach (['all' => 'Seluruh pemilih', 'students' => 'Siswa', 'teachers' => 'Guru'] as $key => $label): ?>
      <?php $s = $snapshot['summary'][$key]; ?>
      <td>
        <div class="label"><?= esc($label) ?></div>
        <div class="big"><?= persen($s['participation']) ?></div>
        <div class="small muted" style="margin-top: 1.2mm;"><?= angka($s['voted']) ?> memilih dari <?= angka($s['total']) ?> &middot; <?= angka($s['not_voted']) ?> tidak memilih</div>
      </td>
    <?php endforeach; ?>
  </tr>
</table>
</div>

<div class="recap-page">
  <h2>Rekap</h2>
  <?php foreach ($recaps as [$label, $groups, $caption]): ?>
    <p class="recap-title"><?= esc($caption) ?></p>
    <table class="recap">
      <thead>
        <tr>
          <th><?= esc($label) ?></th>
          <th>Total</th>
          <th>Sudah</th>
          <th>Belum</th>
          <th>Partisipasi</th>
          <?php foreach ($cands as $c): ?>
            <th><span class="mini" style="background: <?= esc($c['accent'], 'attr') ?>; color: <?= esc($c['accent_ink'], 'attr') ?>;"><?= esc($c['label']) ?></span></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if ($groups === []): ?>
          <tr><td colspan="<?= 5 + count($cands) ?>" class="muted">Belum ada data.</td></tr>
        <?php endif; ?>
        <?php foreach ($groups as $g): ?>
          <tr>
            <td><strong><?= esc($g['label']) ?></strong></td>
            <td class="num"><?= angka($g['total']) ?></td>
            <td class="num"><?= angka($g['voted']) ?></td>
            <td class="num"><?= angka($g['not_voted']) ?></td>
            <td class="num"><?= persen($g['participation']) ?></td>
            <?php foreach ($cands as $c): ?>
              <td class="num"><?= angka($g['votes'][$c['id']] ?? 0) ?><span class="pct"><?= persen($g['shares'][$c['id']] ?? 0) ?></span></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; ?>
</div>

</body>
</html>
