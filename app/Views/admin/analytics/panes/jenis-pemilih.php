<?php
/**
 * @var array $snapshot AnalyticsService::snapshot()
 */
?>
<?= view('admin/partials/recap_table', [
    'groups'     => $snapshot['groups']['type'],
    'candidates' => $snapshot['candidates'],
    'key'        => 'type',
    'label'      => 'Pemilih',
    'caption'    => 'Rekap suara per pemilih (siswa dan guru)',
]) ?>
