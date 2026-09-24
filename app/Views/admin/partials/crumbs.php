<?php
/**
 * Breadcrumb bergaya stepper di atas judul halaman admin (Stage 9, pengganti
 * eyebrow "Kelompok · Halaman"). Setiap langkah = titik kecil di rel tipis;
 * langkah terakhir = halaman ini (titik tinta bercincin, aria-current).
 * "Beranda" (dasbor admin) selalu menjadi langkah pertama.
 *
 * Di HP rel digeser horizontal bila tidak muat (tanpa scrollbar).
 *
 * @var list<array{0: string, 1?: string|null}> $trail [label, path admin|null] setelah Beranda
 */
$trail = array_merge([['Beranda', 'admin']], $trail ?? []);
$last  = count($trail) - 1;
?>
<nav class="crumbs" aria-label="Breadcrumb">
  <ol class="crumbs__list">
    <?php foreach ($trail as $i => $crumb): ?>
      <?php $label = $crumb[0]; $path = $crumb[1] ?? null; ?>
      <li class="crumbs__item<?= $i === $last ? ' is-current' : '' ?>">
        <?php if ($i === $last): ?>
          <span class="crumbs__step" aria-current="page"><span class="crumbs__dot" aria-hidden="true"></span><?= esc($label) ?></span>
        <?php elseif ($path !== null): ?>
          <a class="crumbs__step" href="<?= site_url($path) ?>"><span class="crumbs__dot" aria-hidden="true"></span><?= esc($label) ?></a>
        <?php else: ?>
          <span class="crumbs__step"><span class="crumbs__dot" aria-hidden="true"></span><?= esc($label) ?></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
