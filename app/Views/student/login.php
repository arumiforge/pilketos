<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= asset_url('assets/css/auth.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('partials/voter_login', [
    'action'  => 'siswa/masuk',
    'title'   => 'Yuk, masuk dulu untuk memilih',
    'idField' => 'nisn',
    'idLabel' => 'NISN',
    'idMax'   => 20,
]) ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('assets/js/auth.js') ?>" defer></script>
<?= $this->endSection() ?>
