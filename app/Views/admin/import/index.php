<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Import Excel: unduh template + unggah file.
 *
 * Bahasa halaman dibuat ramah: halaman siswa cocok dibaca anak SMP (panitia
 * OSIS), halaman guru tanpa istilah teknis. Contoh kode unik diambil dari
 * contoh kolom template agar tabel & keterangan selalu sama.
 *
 * @var \App\Services\VoterType                $type
 * @var \App\Services\Import\VoterImporter    $importer
 * @var int                                    $maxBytes
 * @var bool                                   $resultsLocked Stage 4: pemilihan selesai, unggah dikunci
 */
use App\Libraries\CandidateAssets;
use App\Services\Import\VoterImporter;
use App\Services\VoterType;

$isStudent  = $type === VoterType::Student;
$who        = $type->label();
$idLabel    = $type->identifierLabel();
$kodeContoh = (string) $importer->columns()['kodeunik']['example'];
$kodeTgl    = \CodeIgniter\I18n\Time::createFromFormat('!dmY', $kodeContoh)->toLocalizedString('d MMMM yyyy');
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <div class="admin-head__heading">
        <h1 class="admin-head__title">Impor data <?= esc(strtolower($type->label())) ?></h1>
        <?= view('admin/partials/head_hint') ?>
      </div>
      <p class="admin-head__lede" id="admin-head-lede" data-lede><?= esc($who) ?> yang <?= esc($idLabel) ?>-nya sudah ada cukup diperbarui datanya, jadi tidak dobel. <?= esc($who) ?> yang tidak ada di file tetap aman, tidak ikut terhapus.</p>
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
      <?php if ($isStudent): ?>
        <p>Pakai file <strong><?= esc($importer->templateFilename()) ?></strong>, lalu isi satu baris untuk satu siswa. Kolom NISN dan kodeunik sudah diatur supaya angka 0 di depan tidak hilang, jadi tinggal ketik saja.</p>
      <?php else: ?>
        <p>Pakai file <strong><?= esc($importer->templateFilename()) ?></strong>, lalu isi satu baris untuk satu guru. Kolom NIP dan kodeunik sudah disiapkan agar angkanya tersimpan utuh, cukup ketik seperti biasa.</p>
      <?php endif; ?>
      <table class="spec-table">
        <caption class="visually-hidden">Kolom template</caption>
        <thead><tr><th scope="col">Kolom</th><th scope="col">Contoh</th></tr></thead>
        <tbody>
          <?php foreach ($importer->columns() as $header => $column): ?>
            <tr><th scope="row" class="mono"><?= esc($header) ?></th><td class="mono"><?= esc($column['example']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a class="btn import-download" href="<?= site_url($type->adminPath('impor/templat')) ?>"><?= icon('download') ?> Unduh <?= esc($importer->templateFilename()) ?></a>
    </section>

    <section class="panel" aria-labelledby="upload-title">
      <header class="panel__head"><h2 class="panel__title" id="upload-title">02 &middot; Unggah file</h2></header>
      <?php if ($resultsLocked ?? false): ?>
      <p class="notice"><?= icon('lock') ?><span>Pemilihan sudah selesai, jadi impor dikunci supaya jumlah pemilih dan hasil akhir tidak berubah. Template masih bisa diunduh.</span></p>
      <?php else: ?>
      <form action="<?= site_url($type->adminPath('impor')) ?>" method="post" enctype="multipart/form-data" class="stack" data-upload-form data-post-max="<?= CandidateAssets::iniBytes((string) ini_get('post_max_size')) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label for="file">File Excel (.xlsx)</label>
          <input type="file" id="file" name="file" required accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                 data-max-bytes="<?= $maxBytes ?>" data-file-input data-file-kind="xlsx" aria-describedby="file-hint">
          <p class="field-hint" id="file-hint">Ukuran paling besar <?= esc(CandidateAssets::formatBytes($maxBytes)) ?>, paling banyak <?= angka(VoterImporter::MAX_ROWS) ?> baris. Isi data di sheet pertama saja, sheet lain tidak dibaca.</p>
          <p class="field-error" data-file-error hidden></p>
        </div>
        <button type="submit" class="btn btn--block" data-loading-text="Sedang membaca &amp; memeriksa..."><?= icon('upload') ?> Unggah &amp; periksa</button>
      </form>
      <?php endif; ?>

      <h3 class="panel__subtitle">Yang dicek sebelum data disimpan</h3>
      <ul class="check-list">
        <?php if ($isStudent): ?>
          <li>Judul kolom harus sama dengan template (urutannya boleh beda). File lama yang masih memakai kolom "kelas" juga tetap bisa dipakai.</li>
          <li>NISN harus 10 angka dan tidak boleh ada yang sama di dalam file.</li>
          <li>Jenis kelamin cukup ditulis L (laki-laki) atau P (perempuan).</li>
          <li>Rombel diawali kelas 7, 8, atau 9, misalnya 7A, 8B, atau VIII-B.</li>
          <li>Nomor absen berupa angka 1 sampai 999, atau boleh dikosongkan.</li>
          <li>Nama tidak boleh kosong.</li>
          <li>Kode unik adalah tanggal lahir yang ditulis tanggal, bulan, lalu tahun tanpa spasi, misalnya <?= esc($kodeContoh) ?> untuk <?= esc($kodeTgl) ?>.</li>
          <li>Kalau angka 0 di depan NISN atau kode unik hilang gara-gara Excel, sistem melengkapinya bila yakin, lalu memberi tanda supaya dicek lagi.</li>
        <?php else: ?>
          <li>Judul kolom harus sama dengan template (urutannya boleh berbeda).</li>
          <li>NIP berisi angka saja dan tidak boleh ada yang sama di dalam file.</li>
          <li>NIP yang angka terakhirnya sudah berubah sendiri oleh Excel akan ditolak. Agar aman, ketik NIP langsung di template yang disediakan.</li>
          <li>Nama wajib diisi.</li>
          <li>Kode unik adalah tanggal lahir yang ditulis tanggal, bulan, lalu tahun tanpa spasi, misalnya <?= esc($kodeContoh) ?> untuk <?= esc($kodeTgl) ?>.</li>
          <li>Angka 0 di depan yang hilang karena Excel dilengkapi bila memungkinkan, lalu diberi tanda agar bisa dicek ulang.</li>
        <?php endif; ?>
      </ul>
    </section>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('crumbs') ?>
<?= view('admin/partials/crumbs', ['trail' => [[$type->label(), $type->adminPath()], ['Impor Excel']]]) ?>
<?= $this->endSection() ?>
