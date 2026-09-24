<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="section">
  <div class="container">
    <div class="card card--auth stack">
      <div>
        <p class="eyebrow">Masuk Admin</p>
        <h1 class="card__title">Masuk ke panel admin</h1>
      </div>

      <form action="<?= base_url('admin/masuk') ?>" method="post" class="stack" novalidate>
        <?= csrf_field() ?>

        <div class="field">
          <label for="username">Nama Pengguna</label>
          <input type="text" id="username" name="username" autocomplete="username"
                 maxlength="100" value="<?= esc(session()->getFlashdata('old_username') ?? '', 'attr') ?>" required>
        </div>

        <div class="field">
          <label for="password">Kata Sandi</label>
          <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn btn--block" data-loading-text="Memeriksa...">Masuk</button>
      </form>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
