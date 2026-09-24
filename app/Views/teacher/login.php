<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= asset_url('assets/css/auth.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('partials/voter_login', [
    'action'  => 'guru/masuk',
    'title'   => 'Selamat datang, silakan masuk untuk memilih',
    'idField' => 'nip',
    'idLabel' => 'NIP',
    'idMax'   => 30,
]) ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('assets/js/auth.js') ?>" defer></script>
<?= $this->endSection() ?>
