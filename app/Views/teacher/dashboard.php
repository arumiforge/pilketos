<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="page-header">
  <div class="container">
    <p class="eyebrow">Dasbor Guru</p>
    <h2><?= esc($teacher['name']) ?></h2>
  </div>
</section>

<section class="section">
  <div class="container stack-lg">
    <dl class="kv-list">
      <div class="kv-list__row">
        <dt>NIP</dt>
        <dd><?= esc($teacher['nip']) ?></dd>
      </div>
    </dl>

    <?= $this->include('partials/election_status') ?>

    <p class="text-muted">Halaman untuk memilih pasangan calon akan tersedia di sini begitu pemilihan dibuka sesuai jadwal.</p>

    <form action="<?= base_url('teacher/logout') ?>" method="post">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--outline">Keluar</button>
    </form>
  </div>
</section>

<?= $this->endSection() ?>
