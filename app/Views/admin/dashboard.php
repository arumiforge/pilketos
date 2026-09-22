<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="page-header">
  <div class="container">
    <p class="eyebrow">Dasbor Admin</p>
    <h2><?= esc($admin['name']) ?></h2>
  </div>
</section>

<section class="section">
  <div class="container stack-lg">
    <?= $this->include('partials/election_status') ?>

    <p class="text-muted">Pengelolaan kandidat, impor data siswa dan guru, analitik, hitung suara langsung, dan buka kunci hak suara tersedia pada panel admin tahap berikutnya.</p>

    <form action="<?= base_url('admin/logout') ?>" method="post">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--outline">Keluar</button>
    </form>
  </div>
</section>

<?= $this->endSection() ?>
