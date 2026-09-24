<?php
/**
 * Rekap kelas (jenjang 7/8/9; dulu "Jenjang"). Stage 11.
 *
 * @var array $snapshot AnalyticsService::snapshot()
 */
?>
<?= view('admin/partials/recap_table', [
    'groups'     => $snapshot['groups']['grade'],
    'candidates' => $snapshot['candidates'],
    'key'        => 'grade',
    'label'      => 'Kelas',
    'caption'    => 'Rekap suara siswa per kelas',
]) ?>
