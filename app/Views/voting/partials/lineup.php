<?php
/**
 * "Sekilas paslon" (Stage 7): ketiga pasangan dalam satu pandangan, tepat di
 * bawah pembuka, supaya pemilih bisa membandingkan tanpa menggulir tiga bab
 * panjang dan yang sudah mantap bisa langsung ke kotaknya di surat suara.
 *
 * - HP: kartu digeser horizontal (scroll-snap), kartu berikutnya mengintip;
 * - >= 720px: tiga kolom berdampingan.
 * - "Baca visi & misi" -> bab pasangan (#pasangan-0X);
 * - "Pilih 0X" -> kotak surat suara (#coblos-0X, disorot lewat :target),
 *   hanya tampil saat pencoblosan dibuka. Tanpa JavaScript tetap berfungsi.
 *
 * @var list<array> $candidates Hasil CandidateTheme::presentAll()
 * @var bool        $canVote
 */
?>
<section class="lineup" id="sekilas" aria-labelledby="lineup-title">
  <div class="container">
    <header class="lineup__head">
      <h2 class="lineup__title" id="lineup-title">Sekilas paslon</h2>
      <p class="lineup__hint">Bandingkan ketiganya, lalu baca visi &amp; misi lengkapnya<?= $canVote ? ' atau langsung pilih' : '' ?>.</p>
    </header>

    <ol class="lineup__list" aria-label="Pasangan calon">
      <?php foreach ($candidates as $c): ?>
        <li class="pair-card" style="<?= esc($c['style'], 'attr') ?>">
          <div class="pair-card__band">
            <p class="pair-card__no"><span class="visually-hidden">Pasangan </span><?= esc($c['label']) ?></p>
            <?= view('voting/partials/portraits', ['c' => $c, 'size' => 'sm', 'lazy' => false]) ?>
          </div>

          <p class="pair-card__theme">
            <span><?= esc($c['theme_name']) ?></span>
            <span class="pair-card__meta"><?= count($c['misi']) ?> poin misi</span>
          </p>
          <h3 class="pair-card__names">
            <span><span class="pair-card__role">Ketua</span> <?= esc($c['ketua']) ?></span>
            <span><span class="pair-card__role">Wakil</span> <?= esc($c['wakil']) ?></span>
          </h3>

          <?php if ($c['visi'] !== ''): ?>
            <p class="pair-card__visi"><?= esc($c['visi']) ?></p>
          <?php endif; ?>

          <div class="pair-card__actions">
            <a class="pair-card__read" href="#pasangan-<?= esc($c['label'], 'attr') ?>">Baca visi &amp; misi<span class="visually-hidden"> pasangan <?= esc($c['label']) ?></span></a>
            <?php if ($canVote): ?>
              <a class="pair-card__pick" href="#coblos-<?= esc($c['label'], 'attr') ?>" data-pick>
                Pilih <?= esc($c['label']) ?> <?= icon('arrow-down') ?>
              </a>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
