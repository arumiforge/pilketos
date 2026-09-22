<?php $userType = session()->get('user_type'); ?>
<header class="site-nav">
  <div class="container site-nav__row">
    <a href="<?= base_url('/') ?>" class="site-nav__brand">
      <strong>Pemilihan Ketua OSIS</strong>
      <span>SMP 1 Dawe &middot; 2026</span>
    </a>
    <nav class="site-nav__actions" aria-label="Navigasi utama">
      <?php if (in_array($userType, ['student', 'teacher', 'admin'], true)): ?>
        <a href="<?= base_url($userType . '/dashboard') ?>" class="btn btn--sm btn--outline">
          <?= esc(['student' => 'Dasbor Siswa', 'teacher' => 'Dasbor Guru', 'admin' => 'Dasbor Admin'][$userType]) ?>
        </a>
        <form action="<?= base_url($userType . '/logout') ?>" method="post" class="site-nav__logout">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn--sm">Keluar</button>
        </form>
      <?php else: ?>
        <a href="<?= base_url('student/login') ?>" class="btn btn--sm btn--outline">Masuk Siswa</a>
        <a href="<?= base_url('teacher/login') ?>" class="btn btn--sm">Masuk Guru</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
