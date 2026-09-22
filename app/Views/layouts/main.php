<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Pemilihan Ketua dan Wakil Ketua OSIS SMP 1 Dawe Tahun 2026">
<meta name="theme-color" content="#FAF9F6">
<?= csrf_meta() ?>
<title><?= isset($title) ? esc($title) . ' — Pemilihan OSIS SMP 1 Dawe' : 'Pemilihan Ketua OSIS — SMP 1 Dawe 2026' ?></title>
<link rel="preload" href="<?= base_url('assets/fonts/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= base_url('assets/fonts/newsreader-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<?= $this->renderSection('head') ?>
</head>
<body>
<a class="skip-link" href="#main">Langsung ke konten</a>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/flash') ?>
<main id="main" tabindex="-1">
<?= $this->renderSection('content') ?>
</main>
<?= $this->include('partials/footer') ?>
<script src="<?= base_url('assets/js/app.js') ?>" defer></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
