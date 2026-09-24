<?php
/**
 * Tabel rekap per kelompok (jenis pemilih, jenis kelamin, jenjang, kelas).
 * Stage 11: di UI jenjang (key grade) disebut "Kelas" (7/8/9) dan kelas
 * (key class, 7A/8B) disebut "Rombel"; key data & JSON live count tetap.
 *
 * Kolom pasangan: jumlah suara + persentase di dalam kelompok (dari yang
 * sudah memilih). Kolom komposisi: batang bertumpuk suara tiap pasangan +
 * belum memilih terhadap total kelompok. admin-live.js membangun ulang
 * isi tabel dengan struktur yang sama dari endpoint live count.
 *
 * @var list<array<string, mixed>> $groups     Snapshot::groups[<key>]
 * @var list<array<string, mixed>> $candidates Snapshot::candidates
 * @var string                     $key        type|gender|grade|class
 * @var string                     $label      Judul kolom pertama
 * @var string                     $caption    Keterangan tabel (pembaca layar)
 * @var bool                       $bars       Tampilkan kolom komposisi
 * @var string|null                $link       URL dasar tautan baris (key ditambahkan di belakang)
 */
$bars = $bars ?? true;
$link = $link ?? null;
?>
<div class="table-scroll" role="region" aria-label="<?= esc($caption, 'attr') ?>" tabindex="0">
  <table class="data-table data-table--recap" data-live-table="<?= esc($key, 'attr') ?>" data-live-label="<?= esc($label, 'attr') ?>"<?= $bars ? ' data-live-bars' : '' ?><?= $link !== null ? ' data-live-link="' . esc($link, 'attr') . '"' : '' ?>>
    <caption class="visually-hidden"><?= esc($caption) ?></caption>
    <thead>
      <tr>
        <th scope="col"><?= esc($label) ?></th>
        <th scope="col" class="num">Total</th>
        <th scope="col" class="num">Sudah</th>
        <th scope="col" class="num">Belum</th>
        <th scope="col" class="num">Partisipasi</th>
        <?php foreach ($candidates as $c): ?>
          <th scope="col" class="num cand-col" style="--accent: <?= esc($c['accent'], 'attr') ?>; --accent-ink: <?= esc($c['accent_ink'], 'attr') ?>;">
            <span class="cand-chip"><?= esc($c['label']) ?></span><span class="visually-hidden"> Pasangan <?= esc($c['label']) ?></span>
          </th>
        <?php endforeach; ?>
        <?php if ($bars): ?><th scope="col" class="dist">Komposisi</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if ($groups === []): ?>
        <tr><td colspan="<?= 5 + count($candidates) + ($bars ? 1 : 0) ?>" class="data-table__empty">Belum ada data.</td></tr>
      <?php endif; ?>
      <?php foreach ($groups as $g): ?>
        <tr>
          <th scope="row">
            <?php if ($link !== null): ?>
              <a href="<?= esc($link . rawurlencode((string) $g['key']), 'attr') ?>"><?= esc($g['label']) ?></a>
            <?php else: ?>
              <?= esc($g['label']) ?>
            <?php endif; ?>
          </th>
          <td class="num"><?= angka($g['total']) ?></td>
          <td class="num"><?= angka($g['voted']) ?></td>
          <td class="num"><?= angka($g['not_voted']) ?></td>
          <td class="num"><?= persen($g['participation']) ?></td>
          <?php foreach ($candidates as $c): ?>
            <td class="num">
              <span class="cell-n"><?= angka($g['votes'][$c['id']] ?? 0) ?></span>
              <span class="cell-pct"><?= persen($g['shares'][$c['id']] ?? 0) ?></span>
            </td>
          <?php endforeach; ?>
          <?php if ($bars): ?>
            <td class="dist">
              <span class="stackbar" aria-hidden="true">
                <?php foreach ($candidates as $c): ?>
                  <?php $w = $g['total'] > 0 ? ($g['votes'][$c['id']] ?? 0) / $g['total'] * 100 : 0; ?>
                  <span class="stackbar__seg" style="width: <?= esc(number_format($w, 2, '.', ''), 'attr') ?>%; background: <?= esc($c['accent'], 'attr') ?>;"></span>
                <?php endforeach; ?>
              </span>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
