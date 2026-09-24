<?php
/**
 * Catatan kecil di kepala panel (Stage 9): cukup ikon "i" di samping judul;
 * teksnya muncul sebagai tooltip saat ikon diarahkan kursor (hover) atau
 * difokus/diketuk (focus-within). Tanpa JavaScript tetap berfungsi; Escape
 * menutupnya (admin.js). Teks tetap terbaca pembaca layar lewat
 * aria-describedby.
 *
 * @var string $id   id unik tooltip di halaman
 * @var string $text Isi catatan (teks biasa)
 */
?>
<span class="panel__note note-tip" data-note-tip>
  <button type="button" class="note-tip__icon" aria-describedby="<?= esc($id, 'attr') ?>"><?= icon('info') ?><span class="visually-hidden">Keterangan</span></button>
  <span class="note-tip__text" role="tooltip" id="<?= esc($id, 'attr') ?>"><?= esc($text) ?></span>
</span>
