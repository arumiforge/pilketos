<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($title) ? esc($title) . ' — SMP 1 Dawe' : 'Pemilihan Ketua OSIS — SMP 1 Dawe' ?></title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/flash') ?>
<main>
<?= $this->renderSection('content') ?>
</main>
<?= $this->include('partials/footer') ?>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
