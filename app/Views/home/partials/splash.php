<?php
/**
 * Layar pembuka beranda (seperti launch screen aplikasi native), Stage 6:
 * pintu masuk ke dunia visual "lereng Muria" (10-MOTION §10).
 *
 * - Selalu tampil setiap kali beranda dimuat penuh, dengan durasi yang sama
 *   (home.js: minimal 1,5 detik, maksimal 5,2 detik mengikuti aset layar
 *   pertama). Satu-satunya pengecualian: muat ulang otomatis oleh live count
 *   (penanda sekali pakai sessionStorage "osis2026.skipIntro" dibaca
 *   layouts/main.php -> html.intro-seen). Tidak tampil tanpa JavaScript
 *   (html.no-js) dan hilang sendiri lewat CSS bila home.js gagal dimuat.
 * - Latar = pemandangan hero yang SAMA berkabut pekat (A01/A02), dipilih
 *   <picture> seperti latar hero; sebingkai sehingga saat keluar kabut
 *   "tersingkap" menjadi hero yang jernih. Dimuat segera (bukan lazy),
 *   diminta sejak <head> lewat preload (home/index.php) dan langsung terlihat
 *   tanpa menunggu home.js; lockup juga di-preload agar tidak muncul telat.
 * - Lockup acara (+ lambang resmi sekolah bila dipasang) di tengah, bilah
 *   muat tipis berwarna parijoto (--parijoto; netral bila mirip warna
 *   pasangan mana pun) + angka mono. Tanpa teks lain.
 *
 * @var \Config\Homepage $home
 * @var string|null      $identity Warna parijoto tervalidasi (#RRGGBB) atau null
 */
$splashLogoSize   = asset_size($home->logoOnDark);
$splashEmblem     = ($home->schoolEmblem ?? '') !== '' ? $home->schoolEmblem : null;
$splashEmblemSize = $splashEmblem !== null ? asset_size($splashEmblem) : null;
?>
<div class="splash" data-splash aria-hidden="true"<?= $identity !== null ? ' style="--parijoto: ' . esc($identity, 'attr') . ';"' : '' ?>>
  <picture class="splash__bg">
    <source media="(orientation: portrait)" srcset="<?= esc(asset_url($home->introMobile), 'attr') ?>">
    <img class="splash__img" src="<?= esc(asset_url($home->introDesktop), 'attr') ?>" alt="" fetchpriority="high" data-splash-bg>
  </picture>
  <span class="splash__shade"></span>
  <div class="splash__center">
    <span class="brandmark brandmark--splash" data-splash-brand>
      <?php if ($splashEmblem !== null): ?>
        <img class="brandmark__emblem" src="<?= esc(asset_url($splashEmblem), 'attr') ?>" alt=""
             <?= $splashEmblemSize !== null ? 'width="' . $splashEmblemSize[0] . '" height="' . $splashEmblemSize[1] . '"' : '' ?>
             data-splash-wait>
        <span class="brandmark__rule"></span>
      <?php endif; ?>
      <img class="brandmark__logo" src="<?= esc(asset_url($home->logoOnDark), 'attr') ?>" alt=""
           <?= $splashLogoSize !== null ? 'width="' . $splashLogoSize[0] . '" height="' . $splashLogoSize[1] . '"' : '' ?>
           data-splash-logo data-splash-wait>
    </span>
    <span class="splash__bar"><span class="splash__fill" data-splash-fill></span></span>
    <span class="splash__pct" data-splash-pct>000</span>
  </div>
</div>
