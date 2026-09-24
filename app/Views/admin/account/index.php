<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Akun Admin (Stage 13): ganti nama pengguna & kata sandi admin yang sedang
 * masuk. Dua form terpisah; keduanya meminta kata sandi saat ini. Galat per
 * isian hanya ditampilkan pada form yang dikirim ($form).
 *
 * @var array                 $admin
 * @var string                $form   username|password|'' (form yang gagal)
 * @var array<string, string> $errors
 */
$fieldError = static fn (string $which, string $key): ?string => $form === $which ? ($errors[$key] ?? null) : null;
// semua kunci opsional selalu dikirim (lihat partials/password_field)
$password   = static fn (array $field): string => view('partials/password_field', $field + ['error' => null, 'hint' => null, 'attrs' => '']);
$oldUser    = (string) (session()->getFlashdata('old_username') ?? ($admin['username'] ?? ''));
?>
<div class="admin-page account-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <div class="admin-head__heading">
        <h1 class="admin-head__title">Akun Admin</h1>
        <?= view('admin/partials/head_hint') ?>
      </div>
      <p class="admin-head__lede" id="admin-head-lede" data-lede>Ganti nama pengguna atau kata sandi untuk masuk ke panel admin. Setiap perubahan meminta kata sandi saat ini dan tercatat di audit log.</p>
    </div>
  </header>

  <section class="account-id" aria-label="Akun yang sedang masuk">
    <span class="account-id__seal" aria-hidden="true"><?= icon('user') ?></span>
    <div>
      <p class="account-id__name"><?= esc($admin['name'] ?? 'Admin') ?></p>
      <p class="account-id__user">@<?= esc($admin['username'] ?? '') ?></p>
    </div>
  </section>

  <div class="detail-grid account-grid">
    <section class="panel" aria-labelledby="username-title">
      <header class="panel__head">
        <h2 class="panel__title" id="username-title">Ganti Nama Pengguna</h2>
      </header>
      <form action="<?= site_url('admin/akun/nama-pengguna') ?>" method="post" class="stack" novalidate>
        <?= csrf_field() ?>
        <?php $err = $fieldError('username', 'username'); ?>
        <div class="field">
          <label for="acc-username">Nama pengguna baru</label>
          <input type="text" id="acc-username" name="username" value="<?= esc($oldUser, 'attr') ?>" maxlength="50" required
                 autocomplete="username" autocapitalize="off" spellcheck="false"
                 aria-describedby="hint-acc-username<?= $err !== null ? ' err-acc-username' : '' ?>"<?= $err !== null ? ' aria-invalid="true"' : '' ?>>
          <p class="field-hint" id="hint-acc-username">3&ndash;50 karakter: huruf kecil, angka, titik, strip, atau garis bawah.</p>
          <?php if ($err !== null): ?><p class="field-error" id="err-acc-username"><?= esc($err) ?></p><?php endif; ?>
        </div>
        <?= $password([
            'id'           => 'acc-username-current',
            'name'         => 'current_password',
            'label'        => 'Kata sandi saat ini',
            'autocomplete' => 'current-password',
            'error'        => $fieldError('username', 'current_password'),
        ]) ?>
        <div>
          <button type="submit" class="btn" data-loading-text="Menyimpan...">Simpan Nama Pengguna</button>
        </div>
      </form>
    </section>

    <section class="panel" aria-labelledby="password-title">
      <header class="panel__head">
        <h2 class="panel__title" id="password-title">Ganti Kata Sandi</h2>
      </header>
      <form action="<?= site_url('admin/akun/kata-sandi') ?>" method="post" class="stack" novalidate>
        <?= csrf_field() ?>
        <?php /* nama pengguna tersembunyi membantu pengelola kata sandi browser menyimpan pasangan yang benar */ ?>
        <input type="text" name="username_hint" value="<?= esc($admin['username'] ?? '', 'attr') ?>" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
        <?= $password([
            'id'           => 'acc-password-current',
            'name'         => 'current_password',
            'label'        => 'Kata sandi saat ini',
            'autocomplete' => 'current-password',
            'error'        => $fieldError('password', 'current_password'),
        ]) ?>
        <?= $password([
            'id'           => 'acc-password-new',
            'name'         => 'new_password',
            'label'        => 'Kata sandi baru',
            'autocomplete' => 'new-password',
            'hint'         => 'Minimal 8 karakter. Gunakan campuran huruf, angka, dan simbol agar sulit ditebak.',
            'attrs'        => ' minlength="8" maxlength="72"',
            'error'        => $fieldError('password', 'new_password'),
        ]) ?>
        <?= $password([
            'id'           => 'acc-password-confirm',
            'name'         => 'new_password_confirm',
            'label'        => 'Ulangi kata sandi baru',
            'autocomplete' => 'new-password',
            'attrs'        => ' maxlength="72"',
            'error'        => $fieldError('password', 'new_password_confirm'),
        ]) ?>
        <p class="field-hint">Setelah kata sandi diganti, perangkat lain yang masih masuk dengan akun ini otomatis keluar.</p>
        <div>
          <button type="submit" class="btn" data-loading-text="Menyimpan...">Simpan Kata Sandi</button>
        </div>
      </form>
    </section>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('crumbs') ?>
<?= view('admin/partials/crumbs', ['trail' => [['Akun Admin']]]) ?>
<?= $this->endSection() ?>
