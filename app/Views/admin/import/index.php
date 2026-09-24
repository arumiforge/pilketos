<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Import Excel: unduh template + unggah file.
 *
 * @var \App\Services\VoterType                $type
 * @var \App\Services\Import\VoterImporter    $importer
 * @var int                                    $maxBytes
 * @var bool                                   $resultsLocked Stage 4: pemilihan selesai, unggah dikunci
 */
use App\Libraries\CandidateAssets;
use App\Services\Import\VoterImporter;
use App\Services\VoterType;

$isStudent = $type === VoterType::Student;
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <p class="eyebrow-x"><a href="<?= site_url($type->adminPath()) ?>">Data <?= esc(strtolower($type->label())) ?></a> &middot; Impor Excel</p>
      <h1 class="admin-head__title">Impor data <?= esc(strtolower($type->label())) ?></h1>
      <p class="admin-head__lede">Data yang sudah ada dengan <?= esc($type->identifierLabel()) ?> sama akan diperbarui, bukan dibuat ganda. Data yang tidak ada di file tidak dihapus.</p>
    </div>
  </header>

  <ol class="steps" aria-label="Tahapan impor">
    <li class="steps__item is-current"><span>01</span> Unduh template</li>
    <li class="steps__item is-current"><span>02</span> Unggah</li>
    <li class="steps__item"><span>03</span> Pratinjau &amp; validasi</li>
    <li class="steps__item"><span>04</span> Impor</li>
    <li class="steps__item"><span>05</span> Hasil</li>
  </ol>

  <div class="import-grid">
    <section class="panel" aria-labelledby="template-title">
      <header class="panel__head"><h2 class="panel__title" id="template-title">01 &middot; Template</h2></header>
      <p>Gunakan <strong><?= esc($importer->templateFilename()) ?></strong>. Kolom <?= esc($type->identifierLabel()) ?> dan kodeunik sudah berformat Teks agar angka 0 di depan tidak hilang.</p>
      <table class="spec-table">
        <caption class="visually-hidden">Kolom template</caption>
        <thead><tr><th scope="col">Kolom</th><th scope="col">Contoh</th></tr></thead>
        <tbody>
          <?php foreach ($importer->columns() as $header => $column): ?>
            <tr><th scope="row" class="mono"><?= esc($header) ?></th><td class="mono"><?= esc($column['example']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a class="btn" href="<?= site_url($type->adminPath('impor/templat')) ?>"><?= icon('download') ?> Unduh <?= esc($importer->templateFilename()) ?></a>
    </section>

    <section class="panel" aria-labelledby="upload-title">
      <header class="panel__head"><h2 class="panel__title" id="upload-title">02 &middot; Unggah file</h2></header>
      <?php if ($resultsLocked ?? false): ?>
      <p class="notice"><?= icon('lock') ?><span>Pemilihan sudah selesai: impor dikunci karena dapat mengubah jumlah pemilih dan rekap hasil akhir. Template tetap dapat diunduh.</span></p>
      <?php else: ?>
      <form action="<?= site_url($type->adminPath('impor')) ?>" method="post" enctype="multipart/form-data" class="stack" data-upload-form data-post-max="<?= CandidateAssets::iniBytes((string) ini_get('post_max_size')) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label for="file">File Excel (.xlsx)</label>
          <input type="file" id="file" name="file" required accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                 data-max-bytes="<?= $maxBytes ?>" data-file-input data-file-kind="xlsx" aria-describedby="file-hint">
          <p class="field-hint" id="file-hint">Maks <?= esc(CandidateAssets::formatBytes($maxBytes)) ?> dan <?= angka(VoterImporter::MAX_ROWS) ?> baris. Hanya sheet pertama yang dibaca.</p>
          <p class="field-error" data-file-error hidden></p>
        </div>
        <button type="submit" class="btn btn--block" data-loading-text="Membaca &amp; memvalidasi..."><?= icon('upload') ?> Unggah &amp; periksa</button>
      </form>
      <?php endif; ?>

      <h3 class="panel__subtitle">Yang diperiksa sebelum impor</h3>
      <ul class="check-list">
        <li>Header sesuai template (urutan kolom boleh berbeda).</li>
        <?php if ($isStudent): ?>
          <li>NISN tepat 10 digit, tidak ganda di dalam file.</li>
          <li>Jenis kelamin L atau P; kelas berjenjang 7, 8, atau 9; nomor absen 1&ndash;999 atau kosong.</li>
        <?php else: ?>
          <li>NIP hanya angka, tidak ganda; NIP 18 digit yang terbaca sebagai angka (dibulatkan Excel) ditolak.</li>
        <?php endif; ?>
        <li>Nama wajib diisi; kode unik tanggal lahir DDMMYYYY yang valid.</li>
        <li>Nol di depan yang hilang karena Excel dipulihkan bila pasti, dan ditandai untuk diperiksa.</li>
      </ul>
    </section>
  </div>
</div>
<?= $this->endSection() ?>
