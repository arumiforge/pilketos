<?php $userType = session()->get('user_type'); ?>
<header class="site-nav">
  <div class="container site-nav__row">
    <a href="<?= base_url('/') ?>" class="site-nav__brand">
      <strong>Pemilihan Ketua OSIS</strong>
      <span>SMP 1 Dawe &middot; 2026</span>
    </a>
    <nav class="site-nav__actions">
      <?php if ($userType === 'student'): ?>
        <a href="<?= base_url('student/dashboard') ?>" class="btn btn--outline">Dasbor Siswa</a>
      <?php elseif ($userType === 'teacher'): ?>
        <a href="<?= base_url('teacher/dashboard') ?>" class="btn btn--outline">Dasbor Guru</a>
      <?php elseif ($userType === 'admin'): ?>
        <a href="<?= base_url('admin/dashboard') ?>" class="btn btn--outline">Dasbor Admin</a>
      <?php else: ?>
        <a href="<?= base_url('student/login') ?>" class="btn btn--outline">Masuk Siswa</a>
        <a href="<?= base_url('teacher/login') ?>" class="btn">Masuk Guru</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
