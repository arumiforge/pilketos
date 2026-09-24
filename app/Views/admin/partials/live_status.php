<?php
/**
 * Indikator live count + tombol perbarui manual.
 * Tanpa JavaScript tombol berupa tautan muat ulang halaman.
 *
 * Stage 9: waktu "diperbarui" + tombol Perbarui dibungkus .live__bar; di HP
 * bar itu menempel di bawah layar (desktop: tetap sebaris, display: contents).
 * Stage 11: status "Live" (dulu "Langsung") dengan ikon siaran SVG (gelombang
 * berdenyut saat pemilihan berlangsung); ikon + status ikut masuk .live__bar
 * sehingga di HP seluruh indikator menjadi satu bar di bawah layar.
 *
 * @var array|null $election
 */
$status = $election['status'] ?? null;
$state  = match ($status) {
    'ONGOING'  => 'Live',
    'UPCOMING' => 'Menunggu dibuka',
    'FINISHED' => 'Hasil akhir',
    default    => 'Belum ada jadwal',
};
?>
<div class="live live--<?= esc(strtolower((string) ($status ?? 'none')), 'attr') ?>" data-live-indicator>
  <div class="live__bar">
    <span class="live__signal"><?= icon('live') ?></span>
    <span class="live__text">
      <span class="live__state" data-live-state><?= esc($state) ?></span>
      <span class="live__time">diperbarui <time data-live-updated><?= esc(\CodeIgniter\I18n\Time::now()->toLocalizedString('HH.mm.ss')) ?> WIB</time></span>
    </span>
    <a class="btn btn--sm btn--outline live__refresh" href="<?= esc(current_url(), 'attr') ?>" data-live-refresh><?= icon('refresh') ?> Perbarui</a>
  </div>
  <p class="visually-hidden" role="status" aria-live="polite" data-live-announce></p>
</div>
