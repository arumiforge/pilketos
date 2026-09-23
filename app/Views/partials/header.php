<?php
/**
 * Navigasi situs (redesign beranda): logo lengkap di tengah pada desktop & HP,
 * tanpa tombol "Masuk Siswa/Guru" (pintu masuk ada di scene "Masuk" beranda).
 * Pengguna yang sudah login tetap mendapat Dasbor (kiri) dan Keluar (kanan):
 * penting di komputer lab yang dipakai bergantian.
 *
 * Logo diganti lewat Config\Homepage ($logoOnLight / $logoOnDark); ukuran
 * intrinsik dibaca otomatis (asset_size) untuk atribut width/height.
 *
 * @var bool $immersive Beranda gelap: navigasi melayang + logo versi terang.
 */
$navUser     = session()->get('user_type');
$navSignedIn = in_array($navUser, ['student', 'teacher', 'admin'], true);
$navBrand    = config(\Config\Homepage::class);
$navLogo     = ($immersive ?? false) ? $navBrand->logoOnDark : $navBrand->logoOnLight;
$navLogoSize = asset_size($navLogo);
?>
<header class="site-nav<?= ($immersive ?? false) ? ' site-nav--immersive' : '' ?>" data-site-nav>
  <div class="site-nav__row">
    <?php if ($navSignedIn): ?>
      <nav class="site-nav__start" aria-label="Navigasi akun">
        <a class="site-nav__action" href="<?= base_url($navUser . '/dashboard') ?>">
          <?= icon('grid') ?>
          <span class="site-nav__label">Dasbor<span class="site-nav__role"> <?= esc(['student' => 'Siswa', 'teacher' => 'Guru', 'admin' => 'Admin'][$navUser]) ?></span></span>
        </a>
      </nav>
    <?php endif; ?>

    <a class="site-nav__brand" href="<?= base_url('/') ?>">
      <img class="site-nav__logo" src="<?= asset_url($navLogo) ?>"
           alt="Pemilihan Ketua OSIS SMP 1 DAWE 2026, ke beranda"
           <?= $navLogoSize !== null ? 'width="' . $navLogoSize[0] . '" height="' . $navLogoSize[1] . '"' : '' ?>
           decoding="async" data-nav-logo>
    </a>

    <?php if ($navSignedIn): ?>
      <form class="site-nav__end" action="<?= base_url($navUser . '/logout') ?>" method="post">
        <?= csrf_field() ?>
        <button type="submit" class="site-nav__action"><?= icon('logout') ?><span class="site-nav__label">Keluar</span></button>
      </form>
    <?php endif; ?>
  </div>
</header>
