<?php
/**
 * @var array $snapshot AnalyticsService::snapshot()
 */
?>
<?= view('admin/partials/recap_table', [
    'groups'     => $snapshot['groups']['type'],
    'candidates' => $snapshot['candidates'],
    'key'        => 'type',
    'label'      => 'Jenis',
    'caption'    => 'Rekap suara per jenis pemilih',
]) ?>
