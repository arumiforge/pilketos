<?php
/**
 * Topbar admin: tombol menu (HP), judul halaman, status pemilihan.
 *
 * @var array|null $election
 * @var string     $title
 */
$status = $election['status'] ?? null;
?>
<header class="admin-top">
  <button type="button" class="admin-top__menu" aria-controls="admin-nav" aria-expanded="false" data-admin-menu>
    <?= icon('menu') ?><span>Menu</span>
  </button>
  <p class="admin-top__title"><?= esc($title ?? 'Panel Admin') ?></p>
  <a class="admin-top__status" href="<?= site_url('admin/jadwal') ?>" data-live-badge>
    <span class="badge badge--<?= esc(strtolower((string) $status), 'attr') ?>" data-live-badge-class>
      <span class="badge__dot" aria-hidden="true"></span>
      <span data-live-badge-label><?= esc(election_status_label($status)) ?></span>
    </span>
  </a>
</header>
