<?php
/**
 * Footer minimal, rata tengah (redesign beranda). Halaman biasa: di bawah
 * <main>. Beranda imersif: dirender di dalam scene terakhir
 * ($footerInScene = true) karena halaman beranda tidak menggulir.
 *
 * @var bool $footerInScene
 */
?>
<footer class="site-footer<?= ($footerInScene ?? false) ? ' site-footer--scene' : '' ?>">
  <p class="site-footer__text">&copy; <?= date('Y') ?> SMP 1 Dawe <span aria-hidden="true">&middot;</span> Pemilihan Ketua &amp; Wakil Ketua OSIS</p>
</footer>
