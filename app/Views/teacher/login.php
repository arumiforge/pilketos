<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="section">
  <div class="container">
    <div class="card card--auth stack">
      <div>
        <p class="eyebrow">Masuk Guru</p>
        <h2>Selamat datang, silakan masuk untuk memilih</h2>
      </div>

      <form action="<?= base_url('teacher/login') ?>" method="post" class="stack" novalidate>
        <?= csrf_field() ?>

        <div class="field">
          <label for="nip">NIP</label>
          <input type="text" id="nip" name="nip" inputmode="numeric" autocomplete="off"
                 value="<?= esc(old('nip')) ?>" required>
        </div>

        <div class="field">
          <label for="kodeunik">Kode Unik</label>
          <input type="password" id="kodeunik" name="kodeunik" inputmode="numeric" autocomplete="off" required>
          <p class="field-hint">Kode unik adalah tanggal lahir Anda, contoh: 01032006.</p>
        </div>

        <button type="submit" class="btn btn--block">Masuk</button>
      </form>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
