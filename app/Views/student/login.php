<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="section">
  <div class="container">
    <div class="card card--auth stack">
      <div>
        <p class="eyebrow">Masuk Siswa</p>
        <h1 class="card__title">Yuk, masuk dulu untuk memilih</h1>
      </div>

      <form action="<?= base_url('siswa/masuk') ?>" method="post" class="stack" novalidate>
        <?= csrf_field() ?>

        <div class="field">
          <label for="nisn">NISN</label>
          <input type="text" id="nisn" name="nisn" inputmode="numeric" autocomplete="off"
                 maxlength="20" value="<?= esc(session()->getFlashdata('old_nisn') ?? '', 'attr') ?>" required>
        </div>

        <div class="field">
          <label for="kodeunik">Kode Unik</label>
          <input type="password" id="kodeunik" name="kodeunik" inputmode="numeric" autocomplete="off"
                 maxlength="10" aria-describedby="kodeunik-hint" required>
          <p class="field-hint" id="kodeunik-hint">Kode unik kamu adalah tanggal lahir (DDMMYYYY), contoh: 01032013.</p>
        </div>

        <button type="submit" class="btn btn--block" data-loading-text="Memeriksa...">Masuk</button>
      </form>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
