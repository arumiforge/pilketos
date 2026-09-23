<?php
/**
 * Papan angka ringkasan pemilih (live). Angka dirender server; admin-live.js
 * memperbarui elemen [data-live-value] dari endpoint live count.
 *
 * @var array $summary Snapshot::summary (students, teachers, all)
 */
$all      = $summary['all'];
$students = $summary['students'];
$teachers = $summary['teachers'];
$split    = static fn (string $key): string => sprintf(
    '<span data-live-value="summary.students.%1$s">%2$s</span> siswa &middot; <span data-live-value="summary.teachers.%1$s">%3$s</span> guru',
    $key,
    angka($students[$key]),
    angka($teachers[$key]),
);
?>
<dl class="scoreboard">
  <div class="scoreboard__cell">
    <dt>Total pemilih</dt>
    <dd class="scoreboard__value" data-live-value="summary.all.total"><?= angka($all['total']) ?></dd>
    <dd class="scoreboard__sub"><?= $split('total') ?></dd>
  </div>
  <div class="scoreboard__cell">
    <dt>Sudah memilih</dt>
    <dd class="scoreboard__value" data-live-value="summary.all.voted"><?= angka($all['voted']) ?></dd>
    <dd class="scoreboard__sub"><?= $split('voted') ?></dd>
  </div>
  <div class="scoreboard__cell">
    <dt>Belum memilih</dt>
    <dd class="scoreboard__value" data-live-value="summary.all.not_voted"><?= angka($all['not_voted']) ?></dd>
    <dd class="scoreboard__sub"><?= $split('not_voted') ?></dd>
  </div>
  <div class="scoreboard__cell scoreboard__cell--accent">
    <dt>Partisipasi</dt>
    <dd class="scoreboard__value" data-live-value="summary.all.participation" data-live-format="percent"><?= persen($all['participation']) ?></dd>
    <dd class="scoreboard__sub">
      <span class="meter" aria-hidden="true"><span class="meter__fill" style="width: <?= esc(number_format($all['participation'], 2, '.', ''), 'attr') ?>%;" data-live-width="summary.all.participation"></span></span>
    </dd>
  </div>
  <div class="scoreboard__cell">
    <dt>Total suara sah</dt>
    <dd class="scoreboard__value" data-live-value="summary.all.voted"><?= angka($all['voted']) ?></dd>
    <dd class="scoreboard__sub">hanya suara terkunci (LOCKED)</dd>
  </div>
</dl>
