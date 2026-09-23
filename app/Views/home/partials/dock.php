<?php
/**
 * Panel status pemilihan bergaya terminal (menggantikan bagian "Sedang
 * Berlangsung" di alur scene): selalu terlihat, lebar penuh, menempel di
 * bawah layar beranda. Berisi status (teks + lampu, bukan warna saja),
 * countdown jam server (partials/countdown varian "dock"), jadwal, dan garis
 * progres pemilihan di tepi atas panel. Scene memberi ruang sebesar tinggi
 * panel (--dock-h) sehingga tombol & konten tidak pernah tertutup.
 *
 * @var array|null $election
 */

use CodeIgniter\I18n\Time;

$dockStatus = $election['status'] ?? null;
$dockTimes  = null;

if ($election) {
    $dockStart = Time::parse($election['start_at']);
    $dockEnd   = Time::parse($election['end_at']);
    $dockTimes = [
        'start'    => $dockStart,
        'end'      => $dockEnd,
        'same_day' => $dockStart->toDateString() === $dockEnd->toDateString(),
    ];
}
?>
<aside class="dock dock--<?= esc(strtolower($dockStatus ?? 'none'), 'attr') ?>" aria-label="Status pemilihan" data-dock>
  <div class="dock__row">
    <p class="dock__status">
      <span class="dock__led" aria-hidden="true"></span>
      <span class="dock__state"><?= esc(election_status_label($dockStatus)) ?></span>
    </p>

    <div class="dock__line">
      <span class="dock__prompt" aria-hidden="true">pilketos:~$</span>
      <?= view('partials/countdown', ['election' => $election, 'variant' => 'dock']) ?>
      <span class="dock__cursor" aria-hidden="true"></span>
    </div>

    <?php if ($dockTimes !== null): ?>
      <p class="dock__meta">
        <span class="visually-hidden">Jadwal pencoblosan:</span>
        <?php if ($dockTimes['same_day']): ?>
          <time datetime="<?= esc($election['start_at'], 'attr') ?>"><?= esc($dockTimes['start']->toLocalizedString('d MMM yyyy')) ?> <span class="dock__sep" aria-hidden="true">&middot;</span> <?= esc($dockTimes['start']->toLocalizedString('HH.mm')) ?></time><span aria-hidden="true">&ndash;</span><span class="visually-hidden"> sampai </span><time datetime="<?= esc($election['end_at'], 'attr') ?>"><?= esc($dockTimes['end']->toLocalizedString('HH.mm')) ?></time> WIB
        <?php else: ?>
          <time datetime="<?= esc($election['start_at'], 'attr') ?>"><?= esc($dockTimes['start']->toLocalizedString('d MMM, HH.mm')) ?></time> <span aria-hidden="true">&rarr;</span><span class="visually-hidden"> sampai </span> <time datetime="<?= esc($election['end_at'], 'attr') ?>"><?= esc($dockTimes['end']->toLocalizedString('d MMM yyyy, HH.mm')) ?></time> WIB
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>
</aside>
