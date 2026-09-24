<?php
/**
 * Isian kata sandi dengan tombol "lihat kata sandi" (Stage 13).
 *
 * Tombol mata tersembunyi sampai app.js siap (initPasswordToggles): tanpa
 * JavaScript isian tetap kata sandi biasa. Dipakai di login admin dan
 * halaman Akun Admin.
 *
 * @var string      $id
 * @var string      $name
 * @var string      $label
 * @var string      $autocomplete current-password|new-password
 * @var string|null $error        Pesan galat isian (opsional)
 * @var string|null $hint         Keterangan di bawah isian (opsional)
 * @var string      $attrs        Atribut tambahan input (sudah di-escape), mis. ' minlength="8"'
 *
 * Pemanggil selalu mengirim error/hint/attrs (boleh null/''): data view()
 * CodeIgniter tersimpan antarpanggilan, jadi kunci yang tidak dikirim akan
 * memakai nilai dari pemanggilan sebelumnya.
 */
$error = $error ?? null;
$hint  = $hint ?? null;
$attrs = $attrs ?? '';
$ids   = array_filter([$hint !== null ? 'hint-' . $id : '', $error !== null ? 'err-' . $id : '']);
?>
<div class="field">
  <label for="<?= esc($id, 'attr') ?>"><?= esc($label) ?></label>
  <span class="password-field">
    <input type="password" id="<?= esc($id, 'attr') ?>" name="<?= esc($name, 'attr') ?>" autocomplete="<?= esc($autocomplete, 'attr') ?>"
           spellcheck="false" autocapitalize="off" required<?= $attrs ?><?= $ids !== [] ? ' aria-describedby="' . esc(implode(' ', $ids), 'attr') . '"' : '' ?><?= $error !== null ? ' aria-invalid="true"' : '' ?>>
    <button type="button" class="password-field__toggle" data-password-toggle aria-controls="<?= esc($id, 'attr') ?>" aria-pressed="false" hidden>
      <?= icon('eye', 'password-field__show') ?><?= icon('eye-off', 'password-field__hide') ?>
      <span class="visually-hidden" data-password-toggle-label>Tampilkan kata sandi</span>
    </button>
  </span>
  <?php if ($hint !== null): ?><p class="field-hint" id="hint-<?= esc($id, 'attr') ?>"><?= esc($hint) ?></p><?php endif; ?>
  <?php if ($error !== null): ?><p class="field-error" id="err-<?= esc($id, 'attr') ?>"><?= esc($error) ?></p><?php endif; ?>
</div>
