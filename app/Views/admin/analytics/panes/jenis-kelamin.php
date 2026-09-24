<?php
/**
 * @var array $snapshot AnalyticsService::snapshot()
 */
?>
<?= view('admin/partials/recap_table', [
    'groups'     => $snapshot['groups']['gender'],
    'candidates' => $snapshot['candidates'],
    'key'        => 'gender',
    'label'      => 'Jenis Kelamin',
    'caption'    => 'Rekap suara siswa per jenis kelamin',
]) ?>
