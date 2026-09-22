<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="page-header">
  <div class="container">
    <p class="eyebrow">Dasbor Admin</p>
    <h2><?= esc($name) ?></h2>
  </div>
</section>

<section class="section">
  <div class="container stack-lg">
    <p class="text-muted">Kelola kandidat, impor data siswa/guru, lihat analitik, hitung suara langsung, dan buka kunci hak suara akan tersedia pada tahap berikutnya.</p>

    <form action="<?= base_url('admin/logout') ?>" method="post">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--outline">Keluar</button>
    </form>
  </div>
</section>

<?= $this->endSection() ?>
