<?php
/**
 * Layar pembuka beranda (seperti launch screen aplikasi native).
 *
 * - Latar dipilih browser lewat <picture>: layar potret (HP/tablet tegak)
 *   memakai $introMobile, layar lanskap (laptop, monitor, HP miring)
 *   memakai $introDesktop. Di atasnya lapisan gelap agar logo menonjol.
 * - Logo di tengah, bilah muat di bawahnya. home.js mengisi bilah sesuai
 *   asset yang benar-benar dimuat (font, logo, gambar scene), lalu logo
 *   "terbang" ke posisi logo navigasi dan layar pembuka memudar.
 * - Hanya tampil sekali per sesi tab (html.intro-seen, layouts/main.php),
 *   tidak tampil tanpa JavaScript (html.no-js), dan hilang sendiri lewat CSS
 *   bila home.js gagal dimuat. loading="lazy": latar tidak diunduh saat
 *   layar pembuka disembunyikan (display: none).
 *
 * @var \Config\Homepage $home
 */
$splashLogoSize = asset_size($home->logoOnDark);
?>
<div class="splash" data-splash aria-hidden="true">
  <picture class="splash__bg">
    <source media="(orientation: portrait)" srcset="<?= esc(asset_url($home->introMobile), 'attr') ?>">
    <img class="splash__img" src="<?= esc(asset_url($home->introDesktop), 'attr') ?>" alt="" loading="lazy" decoding="async" data-splash-bg>
  </picture>
  <span class="splash__shade"></span>
  <div class="splash__center">
    <img class="splash__logo" src="<?= esc(asset_url($home->logoOnDark), 'attr') ?>" alt=""
         <?= $splashLogoSize !== null ? 'width="' . $splashLogoSize[0] . '" height="' . $splashLogoSize[1] . '"' : '' ?>
         decoding="async" data-splash-logo>
    <span class="splash__bar"><span class="splash__fill" data-splash-fill></span></span>
    <span class="splash__pct" data-splash-pct>000</span>
  </div>
</div>
