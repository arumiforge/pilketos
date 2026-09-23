<?php
/**
 * Keadaan final di dasbor (Stage 4): tampil hanya bila pemilihan FINISHED.
 * Tanpa confetti; perayaan hanya di halaman hasil akhir.
 *
 * @var array<string, mixed> $final FinalResult::build()
 */
use App\Services\FinalResult;

$winner = $final['winner'];
?>
<section class="final-banner<?= $winner !== null ? ' final-banner--winner' : '' ?>"
  <?php if ($winner !== null): ?>style="--accent: <?= esc($winner['accent'], 'attr') ?>; --accent-ink: <?= esc($winner['accent_ink'], 'attr') ?>;"<?php endif; ?>
  aria-labelledby="final-banner-title">
  <?php if ($winner !== null): ?>
    <span class="final-banner__no" aria-hidden="true"><?= esc($winner['label']) ?></span>
  <?php endif; ?>
  <div class="final-banner__body">
    <p class="final-banner__eyebrow"><?= icon('lock') ?> Pemilihan selesai &middot; hasil akhir</p>
    <h2 class="final-banner__title" id="final-banner-title">
      <?php if ($final['state'] === FinalResult::WINNER): ?>
        Pasangan <?= esc($winner['label']) ?> terpilih: <?= esc($winner['ketua']) ?> &amp; <?= esc($winner['wakil']) ?>
      <?php elseif ($final['state'] === FinalResult::TIE): ?>
        Perolehan suara tertinggi sama
      <?php else: ?>
        Pemilihan selesai tanpa suara sah
      <?php endif; ?>
    </h2>
    <p class="final-banner__text">
      <?php if ($winner !== null): ?>
        <?= angka($winner['votes']) ?> suara (<?= persen($winner['percent']) ?> dari <?= angka($final['total_votes']) ?> suara sah).
      <?php endif; ?>
      Pencoblosan ditolak server sejak waktu selesai; angka di dasbor ini adalah hasil akhir.
    </p>
  </div>
  <a class="btn final-banner__cta" href="<?= site_url('admin/results') ?>"><?= icon('award') ?> Buka hasil akhir</a>
</section>
