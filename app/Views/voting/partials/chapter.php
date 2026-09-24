<?php
/**
 * Satu "bab" pasangan calon: panggung visual + visi-misi interaktif.
 *
 * Art direction per pasangan ditentukan theme_layout (split / poster / column),
 * warna aksen solid, pola/texture, dan asset unggahan admin (background, hero,
 * artwork, poster). Struktur DOM sama (shared component), susunan & perlakuan
 * visualnya berbeda per layout.
 *
 * Interaksi (candidates.js, semuanya opsional/progressive enhancement):
 * - data-parallax : lapisan panggung bergerak beda kecepatan saat scroll;
 * - data-tilt     : kedalaman ringan mengikuti pointer (mouse saja);
 * - data-reveal-words : tipografi visi menyala kata demi kata mengikuti scroll;
 * - data-misi     : panel misi dapat dibuka-tutup (expand), item muncul bertahap.
 *
 * Stage 7: CTA akhir bab langsung menuju kotak pasangan ini di surat suara
 * (#coblos-0X), bukan sekadar ke surat suara.
 *
 * @var array $c       Hasil CandidateTheme::present()
 * @var bool  $canVote
 */
$misiId = 'misi-list-' . $c['id'];
?>
<article class="chapter chapter--<?= esc($c['layout'], 'attr') ?>" id="pasangan-<?= esc($c['label'], 'attr') ?>"
         style="<?= esc($c['style'], 'attr') ?>" data-chapter aria-labelledby="chapter-title-<?= esc((string) $c['id'], 'attr') ?>">
  <div class="container chapter__grid">
    <div class="chapter__stage">
      <span class="chapter__pattern chapter__pattern--<?= esc($c['pattern'], 'attr') ?><?= $c['texture'] !== null ? ' chapter__pattern--texture' : '' ?>"
            <?php if ($c['texture'] !== null): ?>style="background-image: url('<?= esc($c['texture'], 'attr') ?>');"<?php endif; ?>
            aria-hidden="true" data-parallax="0.06"></span>

      <?php if ($c['background'] !== null): ?>
        <img class="chapter__backdrop" src="<?= esc($c['background'], 'attr') ?>" alt="" loading="lazy" decoding="async" data-parallax="0.12">
      <?php endif; ?>

      <span class="chapter__numeral" aria-hidden="true" data-parallax="-0.16"><?= esc($c['label']) ?></span>

      <?php if ($c['artwork'] !== null): ?>
        <img class="chapter__artwork" src="<?= esc($c['artwork'], 'attr') ?>"
             alt="Artwork kampanye pasangan <?= esc($c['label'], 'attr') ?>" loading="lazy" decoding="async" data-parallax="0.22">
      <?php endif; ?>

      <div class="chapter__visual" data-tilt>
        <?php if ($c['hero'] !== null): ?>
          <img class="chapter__hero" src="<?= esc($c['hero'], 'attr') ?>"
               alt="Foto pasangan <?= esc($c['label'], 'attr') ?>: <?= esc($c['ketua'], 'attr') ?> dan <?= esc($c['wakil'], 'attr') ?>"
               loading="lazy" decoding="async">
        <?php else: ?>
          <?= view('voting/partials/portraits', ['c' => $c, 'size' => 'lg', 'lazy' => true]) ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="chapter__body">
      <p class="chapter__eyebrow">
        <span>Pasangan <strong><?= esc($c['label']) ?></strong></span>
        <span class="chapter__theme"><?= esc($c['theme_name']) ?></span>
      </p>

      <h2 class="chapter__names" id="chapter-title-<?= esc((string) $c['id'], 'attr') ?>">
        <span class="chapter__person">
          <span class="chapter__role">Calon Ketua</span>
          <span class="chapter__name"><?= esc($c['ketua']) ?></span>
        </span>
        <span class="chapter__person">
          <span class="chapter__role">Calon Wakil</span>
          <span class="chapter__name"><?= esc($c['wakil']) ?></span>
        </span>
      </h2>

      <section class="vm vm--visi" aria-labelledby="visi-<?= esc((string) $c['id'], 'attr') ?>">
        <h3 class="vm__label" id="visi-<?= esc((string) $c['id'], 'attr') ?>">Visi<span class="visually-hidden"> pasangan <?= esc($c['label']) ?></span></h3>
        <?php if ($c['visi'] !== ''): ?>
          <blockquote class="vm__visi" data-reveal-words>
            <p><?= esc($c['visi']) ?></p>
          </blockquote>
        <?php else: ?>
          <p class="text-muted">Visi belum diisi.</p>
        <?php endif; ?>
      </section>

      <section class="vm vm--misi" aria-labelledby="misi-<?= esc((string) $c['id'], 'attr') ?>">
        <h3 class="vm__label" id="misi-<?= esc((string) $c['id'], 'attr') ?>">Misi<span class="visually-hidden"> pasangan <?= esc($c['label']) ?></span></h3>
        <?php if ($c['misi'] !== []): ?>
          <div class="misi" data-misi>
            <ol class="misi__list" id="<?= esc($misiId, 'attr') ?>">
              <?php foreach ($c['misi'] as $i => $item): ?>
                <li class="misi__item" style="--i: <?= (int) $i ?>;">
                  <span class="misi__n" aria-hidden="true"><?= sprintf('%02d', $i + 1) ?></span>
                  <p class="misi__text"><?= esc($item) ?></p>
                </li>
              <?php endforeach; ?>
            </ol>
            <?php if (count($c['misi']) > 1): ?>
              <button type="button" class="misi__toggle" aria-expanded="true" aria-controls="<?= esc($misiId, 'attr') ?>" data-misi-toggle hidden>
                <span data-misi-toggle-label>Tutup misi</span>
                <span class="misi__count"><?= count($c['misi']) ?> poin</span>
              </button>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <p class="text-muted">Misi belum diisi.</p>
        <?php endif; ?>
      </section>

      <?php if ($c['poster'] !== null): ?>
        <figure class="chapter__poster">
          <img src="<?= esc($c['poster'], 'attr') ?>" alt="Poster kampanye pasangan <?= esc($c['label'], 'attr') ?>" loading="lazy" decoding="async">
          <figcaption>Poster kampanye pasangan <?= esc($c['label']) ?></figcaption>
        </figure>
      <?php endif; ?>

      <?php if ($canVote): ?>
        <a class="chapter__cta" href="#coblos-<?= esc($c['label'], 'attr') ?>" data-pick>
          Pilih pasangan <?= esc($c['label']) ?> <?= icon('arrow-down') ?>
        </a>
      <?php else: ?>
        <a class="chapter__cta" href="#surat-suara">Lihat surat suara <?= icon('arrow-down') ?></a>
      <?php endif; ?>
    </div>
  </div>
</article>
