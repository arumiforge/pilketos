<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="page-header">
  <div class="container">
    <p class="eyebrow">Dasbor Siswa</p>
    <h2><?= esc($student['name']) ?></h2>
  </div>
</section>

<section class="section">
  <div class="container stack-lg">
    <dl class="kv-list">
      <div class="kv-list__row">
        <dt>NISN</dt>
        <dd><?= esc($student['nisn']) ?></dd>
      </div>
      <div class="kv-list__row">
        <dt>Kelas</dt>
        <dd><?= esc($student['kelas']) ?></dd>
      </div>
      <div class="kv-list__row">
        <dt>Nomor Absen</dt>
        <dd><?= esc($student['nomor_absen'] ?? '-') ?></dd>
      </div>
    </dl>

    <?= $this->include('partials/election_status') ?>

    <p class="text-muted">Halaman untuk memilih pasangan calon akan muncul di sini begitu pemilihan dibuka sesuai jadwal.</p>

    <form action="<?= base_url('student/logout') ?>" method="post">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--outline">Keluar</button>
    </form>
  </div>
</section>

<?= $this->endSection() ?>
