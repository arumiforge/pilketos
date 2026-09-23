<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= asset_url('assets/css/voting.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
/**
 * Pilihan milik pemilih sendiri (setelah memilih / login ulang).
 * Sengaja TIDAK menampilkan jumlah suara, peringkat, atau pasangan lain.
 *
 * @var \App\Services\VoterType $type
 * @var array                   $voter
 * @var array                   $election
 * @var array                   $vote      Baris suara LOCKED milik pemilih ini
 * @var array                   $candidate Hasil CandidateTheme::present()
 */
$c = $candidate;
?>
<section class="receipt" style="<?= esc($c['style'], 'attr') ?>" aria-labelledby="receipt-title">
  <div class="container receipt__grid">
    <div class="receipt__head">
      <p class="eyebrow">Pilihan saya &middot; <?= esc($type->label()) ?></p>
      <h1 class="receipt__title" id="receipt-title">
        Anda memilih <span class="receipt__pair">Pasangan <?= esc($c['label']) ?></span>
      </h1>
      <p class="lock-badge"><?= icon('lock') ?> Status: suara terkunci</p>
    </div>

    <div class="receipt__card">
      <div class="receipt__ballot" aria-hidden="true">
        <span class="receipt__no"><?= esc($c['label']) ?></span>
        <span class="receipt__hole"></span>
      </div>
      <div class="receipt__portraits">
        <?= view('voting/partials/portraits', ['c' => $c, 'size' => 'sm', 'lazy' => false]) ?>
      </div>
      <dl class="kv-list receipt__list">
        <div class="kv-list__row">
          <dt>Calon Ketua</dt>
          <dd><?= esc($c['ketua']) ?></dd>
        </div>
        <div class="kv-list__row">
          <dt>Calon Wakil</dt>
          <dd><?= esc($c['wakil']) ?></dd>
        </div>
        <div class="kv-list__row">
          <dt>Waktu memilih</dt>
          <dd><time datetime="<?= esc($vote['voted_at'], 'attr') ?>"><?= esc(format_waktu($vote['voted_at'], 'd MMMM yyyy, HH.mm.ss')) ?></time></dd>
        </div>
        <div class="kv-list__row">
          <dt>Status</dt>
          <dd><?= icon('lock') ?> Suara terkunci</dd>
        </div>
      </dl>
    </div>

    <div class="receipt__voter">
      <h2 class="receipt__subtitle">Identitas pemilih</h2>
      <dl class="kv-list">
        <div class="kv-list__row">
          <dt>Nama</dt>
          <dd><?= esc($voter['name']) ?></dd>
        </div>
        <?php if ($type === \App\Services\VoterType::Student): ?>
          <div class="kv-list__row">
            <dt>Kelas</dt>
            <dd><?= esc($voter['kelas']) ?></dd>
          </div>
          <div class="kv-list__row">
            <dt>Nomor Absen</dt>
            <dd><?= esc($voter['nomor_absen'] ?? '-') ?></dd>
          </div>
        <?php else: ?>
          <div class="kv-list__row">
            <dt>NIP</dt>
            <dd><?= esc($voter['nip']) ?></dd>
          </div>
        <?php endif; ?>
      </dl>
      <p class="receipt__note">Pilihan tidak dapat diubah. Bila benar-benar terjadi kesalahan, hubungi panitia: hanya admin yang dapat membuka kembali hak pilih, lalu Anda mencoblos sendiri.</p>
      <div class="cluster">
        <a class="btn btn--outline" href="<?= base_url($type->path('dashboard')) ?>">Kembali ke dasbor</a>
        <form action="<?= base_url($type->path('logout')) ?>" method="post">
          <?= csrf_field() ?>
          <button type="submit" class="btn">Selesai &amp; keluar</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
