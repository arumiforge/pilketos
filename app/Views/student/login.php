<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="section">
  <div class="container">
    <div class="card card--auth stack">
      <div>
        <p class="eyebrow">Masuk Siswa</p>
        <h2>Yuk, masuk dulu untuk memilih</h2>
      </div>

      <form action="<?= base_url('student/login') ?>" method="post" class="stack" novalidate>
        <?= csrf_field() ?>

        <div class="field">
          <label for="nisn">NISN</label>
          <input type="text" id="nisn" name="nisn" inputmode="numeric" autocomplete="off"
                 value="<?= esc(old('nisn')) ?>" required>
        </div>

        <div class="field">
          <label for="kodeunik">Kode Unik</label>
          <input type="password" id="kodeunik" name="kodeunik" inputmode="numeric" autocomplete="off" required>
          <p class="field-hint">Kode unik kamu adalah tanggal lahir, contoh: 01032013.</p>
        </div>

        <button type="submit" class="btn btn--block">Masuk</button>
      </form>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
