<?php
/**
 * Navigasi panel admin. Di layar lebar menjadi sidebar tetap; di HP menjadi
 * drawer (admin.js). Tanpa JavaScript tampil sebagai menu biasa di atas konten.
 *
 * Stage 9: brand satu baris "SMP 1 DAWE" + "Panel Admin"; menu tanpa nomor
 * dan tanpa judul kelompok (kelompok hanya dipisah garis tipis); dasbor
 * bernama "Beranda"; tautan situs pemilih bernama "Home".
 *
 * @var array  $admin
 * @var string $nav
 */
$groups = [
    [
        'dashboard' => ['admin', 'grid', 'Beranda'],
        'analytics' => ['admin/analitik', 'chart', 'Analitik'],
        'votes'     => ['admin/analitik/suara', 'list', 'Detail suara'],
        'results'   => ['admin/hasil', 'award', 'Hasil akhir'],
    ],
    [
        'candidates' => ['admin/paslon', 'flag', 'Pasangan calon'],
        'students'   => ['admin/siswa', 'users', 'Siswa'],
        'teachers'   => ['admin/guru', 'user', 'Guru'],
    ],
    [
        'election' => ['admin/jadwal', 'calendar', 'Jadwal pemilihan'],
        'unlock'   => ['admin/buka-kunci', 'unlock', 'Unlock hak suara'],
        'audit'    => ['admin/riwayat', 'file', 'Audit log'],
    ],
];
?>
<aside class="admin-side" id="admin-nav" aria-label="Menu admin" data-admin-nav>
  <div class="admin-side__brand">
    <a href="<?= site_url('admin') ?>" class="admin-side__mark">SMP 1 DAWE</a>
    <p class="admin-side__school">Panel Admin</p>
    <button type="button" class="admin-side__close" data-admin-menu-close aria-label="Tutup menu"><?= icon('close') ?></button>
  </div>

  <nav class="admin-side__nav">
    <?php foreach ($groups as $items): ?>
      <ul class="admin-side__list">
        <?php foreach ($items as $key => [$path, $iconName, $label]): ?>
          <li>
            <a href="<?= site_url($path) ?>" class="admin-side__link"<?= ($nav ?? '') === $key ? ' aria-current="page"' : '' ?>>
              <?= icon($iconName, 'admin-side__icon') ?>
              <span><?= esc($label) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endforeach; ?>
  </nav>

  <div class="admin-side__foot">
    <p class="admin-side__who">
      <span class="admin-side__who-label">Masuk sebagai</span>
      <strong><?= esc($admin['name'] ?? 'Admin') ?></strong>
      <span class="admin-side__who-user">@<?= esc($admin['username'] ?? '') ?></span>
    </p>
    <div class="admin-side__actions">
      <a href="<?= base_url('/') ?>" class="admin-side__site" target="_blank" rel="noopener">Home <?= icon('external') ?></a>
      <form action="<?= site_url('admin/keluar') ?>" method="post">
        <?= csrf_field() ?>
        <button type="submit" class="admin-side__logout"><?= icon('logout') ?> Keluar</button>
      </form>
    </div>
  </div>
</aside>
