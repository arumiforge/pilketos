<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="section">
  <div class="container">
    <div class="card card--auth stack">
      <div>
        <p class="eyebrow">Masuk Admin</p>
        <h2>Masuk ke panel admin</h2>
      </div>

      <form action="<?= base_url('admin/login') ?>" method="post" class="stack" novalidate>
        <?= csrf_field() ?>

        <div class="field">
          <label for="username">Nama Pengguna</label>
          <input type="text" id="username" name="username" autocomplete="username"
                 value="<?= esc(old('username')) ?>" required>
        </div>

        <div class="field">
          <label for="password">Kata Sandi</label>
          <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn btn--block">Masuk</button>
      </form>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
