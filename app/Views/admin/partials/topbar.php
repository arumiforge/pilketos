<?php
/**
 * Topbar admin: tombol menu (HP), breadcrumb (Stage 12, dulu judul halaman),
 * status pemilihan.
 *
 * Breadcrumb = section "crumbs" halaman (admin/partials/crumbs) yang sudah
 * dirender layout; tampil mulai 720 px. Di HP breadcrumb ikon saja tampil di
 * atas judul (layouts/admin, .admin-crumbs).
 *
 * @var array|null $election
 * @var string     $crumbs   HTML breadcrumb
 */
$status = $election['status'] ?? null;
?>
<header class="admin-top">
  <button type="button" class="admin-top__menu" aria-controls="admin-nav" aria-expanded="false" data-admin-menu>
    <?= icon('menu') ?><span>Menu</span>
  </button>
  <div class="admin-top__title"><?= $crumbs ?? '' ?></div>
  <a class="admin-top__status" href="<?= site_url('admin/jadwal') ?>" data-live-badge>
    <span class="badge badge--<?= esc(strtolower((string) $status), 'attr') ?>" data-live-badge-class>
      <span class="badge__dot" aria-hidden="true"></span>
      <span data-live-badge-label><?= esc(election_status_label($status)) ?></span>
    </span>
  </a>
</header>
