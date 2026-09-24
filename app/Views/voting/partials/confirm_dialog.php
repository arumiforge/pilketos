<?php
/**
 * Modal konfirmasi (versi JavaScript). Isi pasangan diisi ballot.js dari
 * kotak surat suara yang dicoblos. Suara baru dikirim setelah tombol
 * KONFIRMASI PILIHAN ditekan; server tetap memvalidasi ulang semuanya.
 *
 * @var \App\Services\VoterType $type
 */
?>
<dialog class="modal confirm" id="vote-confirm" aria-labelledby="confirm-title" aria-describedby="confirm-warning" data-confirm data-modal-static>
  <div class="confirm__state" data-confirm-state="review">
    <p class="confirm__kicker">Konfirmasi pilihan</p>
    <h2 class="confirm__title" id="confirm-title" tabindex="-1">Pasangan <span data-slot="number"></span></h2>
    <div class="confirm__portraits" data-slot="portraits"></div>
    <dl class="confirm__names">
      <div>
        <dt>Calon Ketua</dt>
        <dd data-slot="ketua"></dd>
      </div>
      <div>
        <dt>Calon Wakil</dt>
        <dd data-slot="wakil"></dd>
      </div>
    </dl>
    <p class="confirm__warning" id="confirm-warning">
      <?= icon('lock') ?>
      <span>Setelah dikonfirmasi, pilihan akan <strong>dikunci</strong> dan tidak dapat diubah.</span>
    </p>
    <p class="confirm__error" data-slot="error" role="alert" hidden></p>
    <div class="modal__actions">
      <button type="button" class="btn btn--accent btn--lg" data-confirm-submit data-loading-text="MENYIMPAN SUARA...">KONFIRMASI PILIHAN</button>
      <button type="button" class="btn btn--outline" data-confirm-cancel>Batal, pilih ulang</button>
    </div>
  </div>

  <div class="confirm__state confirm__state--success" data-confirm-state="success" hidden>
    <span class="confirm__seal" aria-hidden="true"><?= icon('check') ?></span>
    <h2 class="confirm__title" tabindex="-1" data-success-title>SUARA BERHASIL DISIMPAN</h2>
    <p class="confirm__lock"><?= icon('lock') ?> Hak suara Anda telah dikunci.</p>
    <p class="confirm__meta" data-slot="success-meta"></p>
    <div class="modal__actions">
      <a class="btn btn--lg" href="<?= esc(site_url($type->path('pilihanku')), 'attr') ?>" data-success-link>Lihat pilihan saya</a>
    </div>
  </div>
</dialog>
