<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= asset_url('assets/css/voting.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
/**
 * Bilik suara (siswa & guru). Stage 7: pembuka ringkas -> "Sekilas paslon"
 * (bandingkan & lompat) -> bab tiap pasangan (visi-misi interaktif) ->
 * surat suara + paku. Pemilih yang sudah mantap cukup satu ketukan dari
 * kartu ke kotak surat suara (#coblos-0X).
 *
 * @var \App\Services\VoterType $type
 * @var array                   $voter
 * @var array|null              $election
 * @var bool                    $canVote
 * @var list<array>             $candidates Hasil CandidateTheme::presentAll()
 */
$status = $election['status'] ?? null;
?>

<section class="booth-intro" aria-labelledby="booth-title">
  <div class="container booth-intro__grid">
    <div class="booth-intro__main">
      <p class="eyebrow">Halo, <?= esc($voter['name']) ?> &middot; Bilik suara <?= esc(strtolower($type->label())) ?></p>
      <h1 class="booth-intro__title" id="booth-title">Kenali, lalu <span class="booth-intro__verb">coblos</span>.</h1>
      <ol class="booth-steps" aria-label="Langkah memilih">
        <li><span class="booth-steps__no" aria-hidden="true">1</span> Kenali paslon</li>
        <li><span class="booth-steps__no" aria-hidden="true">2</span> Coblos satu</li>
        <li><span class="booth-steps__no" aria-hidden="true">3</span> Konfirmasi &amp; kunci</li>
      </ol>
    </div>
    <div class="booth-intro__status">
      <?php if ($election && in_array($status, ['UPCOMING', 'ONGOING'], true)): ?>
        <?= view('partials/countdown', ['election' => $election, 'variant' => 'compact']) ?>
      <?php endif; ?>
      <?php if (! $canVote): ?>
        <p class="notice" role="status">
          <?= icon($status === 'FINISHED' ? 'lock' : 'clock') ?>
          <span>
            <?php if ($status === 'UPCOMING'): ?>
              Pencoblosan belum dibuka. Halaman ini hanya untuk mengenal pasangan calon.
            <?php elseif ($status === 'FINISHED'): ?>
              Pemilihan telah ditutup. Pencoblosan tidak tersedia.
            <?php else: ?>
              Jadwal pemilihan belum tersedia.
            <?php endif; ?>
          </span>
        </p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($candidates !== []): ?>
  <?= view('voting/partials/lineup', ['candidates' => $candidates, 'canVote' => $canVote]) ?>

  <nav class="chapter-nav" aria-label="Lompat ke pasangan calon" data-chapter-nav>
    <div class="container chapter-nav__row">
      <?php foreach ($candidates as $c): ?>
        <a class="chapter-nav__link" href="#pasangan-<?= esc($c['label'], 'attr') ?>" style="<?= esc($c['style'], 'attr') ?>">
          <span class="chapter-nav__swatch" aria-hidden="true"></span><?= esc($c['label']) ?>
          <span class="chapter-nav__name"><?= esc($c['ketua']) ?></span>
        </a>
      <?php endforeach; ?>
      <a class="chapter-nav__link chapter-nav__link--ballot" href="#surat-suara"><?= icon('nail') ?> <?= $canVote ? 'Coblos' : 'Surat suara' ?></a>
    </div>
  </nav>

  <?php foreach ($candidates as $c): ?>
    <?= view('voting/partials/chapter', ['c' => $c, 'canVote' => $canVote]) ?>
  <?php endforeach; ?>
<?php endif; ?>

<?= view('voting/partials/ballot', [
    'candidates' => $candidates,
    'canVote'    => $canVote,
    'election'   => $election,
    'type'       => $type,
]) ?>

<?php if ($canVote && $candidates !== []): ?>
  <?= view('voting/partials/confirm_dialog', ['type' => $type]) ?>
  <div class="nail2d" data-nail2d hidden aria-hidden="true">
    <span class="nail2d__shadow"></span>
    <span class="nail2d__body"><?= view('voting/partials/nail_svg') ?></span>
  </div>
  <canvas class="nail3d" data-nail3d hidden aria-hidden="true"></canvas>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('assets/js/countdown.js') ?>" defer></script>
<script src="<?= asset_url('assets/js/candidates.js') ?>" defer></script>
<?php if ($canVote && $candidates !== []): ?>
  <script src="<?= asset_url('assets/js/ballot.js') ?>" defer></script>
<?php endif; ?>
<?= $this->endSection() ?>
