<?php
/**
 * Ringkasan status election untuk dasbor.
 *
 * @var array|null $election Hasil ElectionModel::getCurrentElection()
 */
$status = $election['status'] ?? null;
?>
<dl class="kv-list">
  <div class="kv-list__row">
    <dt>Status pemilihan</dt>
    <dd>
      <span class="badge badge--<?= esc(strtolower((string) $status), 'attr') ?>">
        <span class="badge__dot" aria-hidden="true"></span>
        <?= esc(election_status_label($status)) ?>
      </span>
    </dd>
  </div>
  <?php if ($election): ?>
    <div class="kv-list__row">
      <dt>Mulai</dt>
      <dd><?= esc(format_waktu($election['start_at'])) ?></dd>
    </div>
    <div class="kv-list__row">
      <dt>Selesai</dt>
      <dd><?= esc(format_waktu($election['end_at'])) ?></dd>
    </div>
  <?php endif; ?>
</dl>
