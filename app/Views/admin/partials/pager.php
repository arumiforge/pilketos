<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * Template pagination panel admin (Config\Pager::$templates['admin']).
 * Query string filter dipertahankan oleh Pager.
 *
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);

if ($pager->getPageCount() <= 1) {
    return;
}
?>
<nav class="pager" aria-label="Halaman">
  <p class="pager__info">Halaman <?= (int) $pager->getCurrentPageNumber() ?> dari <?= (int) $pager->getPageCount() ?></p>
  <ul class="pager__list">
    <?php if ($pager->hasPreviousPage()): ?>
      <li><a class="pager__link" href="<?= esc($pager->getFirst(), 'attr') ?>">Pertama</a></li>
      <li><a class="pager__link" href="<?= esc((string) $pager->getPreviousPage(), 'attr') ?>" rel="prev"><?= icon('arrow-left') ?><span class="visually-hidden">Sebelumnya</span></a></li>
    <?php endif; ?>

    <?php foreach ($pager->links() as $link): ?>
      <li>
        <a class="pager__link" href="<?= esc($link['uri'], 'attr') ?>"<?= $link['active'] ? ' aria-current="page"' : '' ?>><?= (int) $link['title'] ?></a>
      </li>
    <?php endforeach; ?>

    <?php if ($pager->hasNextPage()): ?>
      <li><a class="pager__link" href="<?= esc((string) $pager->getNextPage(), 'attr') ?>" rel="next"><?= icon('arrow-right') ?><span class="visually-hidden">Berikutnya</span></a></li>
      <li><a class="pager__link" href="<?= esc($pager->getLast(), 'attr') ?>">Terakhir</a></li>
    <?php endif; ?>
  </ul>
</nav>
