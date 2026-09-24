<?php
/**
 * @var array $snapshot AnalyticsService::snapshot()
 */
?>
<?= view('admin/partials/scoreboard', ['summary' => $snapshot['summary']]) ?>
<?= view('admin/partials/candidate_results', ['candidates' => $snapshot['candidates'], 'total' => $snapshot['summary']['all']['voted']]) ?>
