<?php
/**
 * Rekap rombel (7A, 8B, ...; dulu "Kelas"). Stage 11.
 *
 * @var array $snapshot AnalyticsService::snapshot()
 */
?>
<?= view('admin/partials/recap_table', [
    'groups'     => $snapshot['groups']['class'],
    'candidates' => $snapshot['candidates'],
    'key'        => 'class',
    'label'      => 'Rombel',
    'caption'    => 'Rekap suara siswa per rombel',
    'link'       => site_url('admin/siswa') . '?status=aktif&kelas=',
]) ?>
