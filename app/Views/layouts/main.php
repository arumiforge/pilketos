<?php
/**
 * Layout halaman publik & pemilih (beranda, login, dasbor, voting).
 *
 * @var bool        $immersive Beranda imersif (redesign beranda): latar gelap,
 *                             viewport-fit=cover (safe-area HP), footer dirender
 *                             di dalam scene terakhir, layar pembuka sekali per
 *                             sesi tab (penanda non-sensitif di sessionStorage).
 * @var string|null $bodyClass
 */
$immersive = (bool) ($immersive ?? false);
?>
<!DOCTYPE html>
<html lang="id" class="no-js<?= $immersive ? ' is-immersive' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1<?= $immersive ? ', viewport-fit=cover' : '' ?>">
<meta name="description" content="Pemilihan Ketua dan Wakil Ketua OSIS SMP 1 Dawe Tahun 2026">
<meta name="theme-color" content="<?= $immersive ? '#0F0E13' : '#FAF9F6' ?>">
<?= csrf_meta() ?>
<title><?= isset($title) ? esc($title) . ' — Pemilihan OSIS SMP 1 Dawe' : 'Pemilihan Ketua OSIS — SMP 1 Dawe 2026' ?></title>
<script <?= csp_script_nonce() ?>>document.documentElement.className = document.documentElement.className.replace('no-js', 'js');<?php if ($immersive): ?>
try { if (window.sessionStorage.getItem('osis2026.intro') === '1') { document.documentElement.className += ' intro-seen'; } } catch (e) {}<?php endif; ?></script>
<link rel="preload" href="<?= base_url('assets/fonts/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= base_url('assets/fonts/newsreader-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset_url('assets/css/app.css') ?>">
<?= $this->renderSection('head') ?>
</head>
<body class="<?= esc($bodyClass ?? '', 'attr') ?>">
<a class="skip-link" href="#main">Langsung ke konten</a>
<?= $this->renderSection('overlay') ?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/flash') ?>
<main id="main" tabindex="-1">
<?= $this->renderSection('content') ?>
</main>
<?php if (! $immersive): ?>
<?= $this->include('partials/footer') ?>
<?php endif; ?>
<script src="<?= asset_url('assets/js/app.js') ?>" defer></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
