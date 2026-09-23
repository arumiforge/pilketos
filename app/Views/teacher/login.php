<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="section">
  <div class="container">
    <div class="card card--auth stack">
      <div>
        <p class="eyebrow">Masuk Guru</p>
        <h1 class="card__title">Selamat datang, silakan masuk untuk memilih</h1>
      </div>

      <form action="<?= base_url('teacher/login') ?>" method="post" class="stack" novalidate>
        <?= csrf_field() ?>

        <div class="field">
          <label for="nip">NIP</label>
          <input type="text" id="nip" name="nip" inputmode="numeric" autocomplete="off"
                 maxlength="30" value="<?= esc(session()->getFlashdata('old_nip') ?? '', 'attr') ?>" required>
        </div>

        <div class="field">
          <label for="kodeunik">Kode Unik</label>
          <input type="password" id="kodeunik" name="kodeunik" inputmode="numeric" autocomplete="off"
                 maxlength="10" aria-describedby="kodeunik-hint" required>
          <p class="field-hint" id="kodeunik-hint">Kode unik adalah tanggal lahir Anda (DDMMYYYY), contoh: 01032006.</p>
        </div>

        <button type="submit" class="btn btn--block" data-loading-text="Memeriksa...">Masuk</button>
      </form>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
