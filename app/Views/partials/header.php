<?php
/**
 * Navigasi situs (redesign beranda): logo lengkap di tengah pada desktop & HP,
 * tanpa tombol "Masuk Siswa/Guru" (pintu masuk ada di scene "Masuk" beranda).
 * Pengguna yang sudah login tetap mendapat Dasbor (kiri) dan Keluar (kanan):
 * penting di komputer lab yang dipakai bergantian. Stage 8: di HP (< 720px)
 * keduanya cukup ikon; teksnya tetap ada untuk pembaca layar.
 *
 * Stage 6: logo = lockup acara PILKETOS 2026 (Config\Homepage $logoOnLight /
 * $logoOnDark) + lambang resmi SMP 1 DAWE bila dipasang ($schoolEmblem),
 * apa adanya, dipisah garis tipis. Ukuran intrinsik dibaca otomatis
 * (asset_size) untuk atribut width/height.
 *
 * @var bool $immersive Beranda gelap: navigasi melayang + logo versi terang.
 */
$navUser       = session()->get('user_type');
$navSignedIn   = in_array($navUser, ['student', 'teacher', 'admin'], true);
// Dasbor tiap role: /siswa, /guru, /admin (Stage 7); keluar = <dasbor>/keluar.
$navHome       = $navSignedIn ? ($navUser === 'admin' ? 'admin' : \App\Services\VoterType::from($navUser)->path()) : null;
$navBrand      = config(\Config\Homepage::class);
$navLogo       = ($immersive ?? false) ? $navBrand->logoOnDark : $navBrand->logoOnLight;
$navLogoSize   = asset_size($navLogo);
$navEmblem     = ($navBrand->schoolEmblem ?? '') !== '' ? $navBrand->schoolEmblem : null;
$navEmblemSize = $navEmblem !== null ? asset_size($navEmblem) : null;
?>
<header class="site-nav<?= ($immersive ?? false) ? ' site-nav--immersive' : '' ?>" data-site-nav>
  <div class="site-nav__row">
    <?php if ($navSignedIn): ?>
      <nav class="site-nav__start" aria-label="Navigasi akun">
        <a class="site-nav__action" href="<?= base_url($navHome) ?>">
          <?= icon('grid') ?>
          <span class="site-nav__label">Dasbor<span class="site-nav__role"> <?= esc(['student' => 'Siswa', 'teacher' => 'Guru', 'admin' => 'Admin'][$navUser]) ?></span></span>
        </a>
      </nav>
    <?php endif; ?>

    <a class="site-nav__brand" href="<?= base_url('/') ?>">
      <span class="brandmark" data-nav-brand>
        <?php if ($navEmblem !== null): ?>
          <img class="brandmark__emblem" src="<?= asset_url($navEmblem) ?>" alt=""
               <?= $navEmblemSize !== null ? 'width="' . $navEmblemSize[0] . '" height="' . $navEmblemSize[1] . '"' : '' ?>
               decoding="async" data-nav-logo>
          <span class="brandmark__rule" aria-hidden="true"></span>
        <?php endif; ?>
        <img class="brandmark__logo site-nav__logo" src="<?= asset_url($navLogo) ?>"
             alt="Pemilihan Ketua OSIS SMP 1 DAWE 2026, ke beranda"
             <?= $navLogoSize !== null ? 'width="' . $navLogoSize[0] . '" height="' . $navLogoSize[1] . '"' : '' ?>
             decoding="async" data-nav-logo>
      </span>
    </a>

    <?php if ($navSignedIn): ?>
      <form class="site-nav__end" action="<?= base_url($navHome . '/keluar') ?>" method="post">
        <?= csrf_field() ?>
        <button type="submit" class="site-nav__action"><?= icon('logout') ?><span class="site-nav__label">Keluar</span></button>
      </form>
    <?php endif; ?>
  </div>
</header>
