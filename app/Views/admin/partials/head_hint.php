<?php
/**
 * Tombol ikon keterangan di samping judul halaman admin (Stage 9).
 * Keterangan (.admin-head__lede#admin-head-lede) tersembunyi sampai tombol
 * ini ditekan (admin.js). Tanpa JavaScript tombol tidak tampil dan
 * keterangan terlihat seperti biasa.
 */
?>
<button type="button" class="admin-head__hint" aria-expanded="false" aria-controls="admin-head-lede" data-lede-toggle>
  <?= icon('info') ?><span class="visually-hidden">Keterangan halaman</span>
</button>
