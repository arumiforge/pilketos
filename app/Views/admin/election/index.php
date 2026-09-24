<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Kontrol jadwal pemilihan.
 *
 * @var array|null                $election
 * @var \CodeIgniter\I18n\Time    $now
 * @var array<string, string>     $errors
 */
$old    = session()->getFlashdata('_ci_old_input')['post'] ?? [];
$status = $election['status'] ?? null;
$local  = static fn (?string $datetime): string => $datetime ? str_replace(' ', 'T', substr($datetime, 0, 16)) : '';
$values = [
    'nama'     => $old['nama'] ?? ($election['nama'] ?? 'Pemilihan Ketua dan Wakil Ketua OSIS SMP 1 DAWE'),
    'tahun'    => $old['tahun'] ?? (string) ($election['tahun'] ?? $now->getYear()),
    'start_at' => $old['start_at'] ?? $local($election['start_at'] ?? null),
    'end_at'   => $old['end_at'] ?? $local($election['end_at'] ?? null),
];
$error   = static fn (string $key): string => isset($errors[$key]) ? '<p class="field-error" id="err-' . $key . '">' . esc($errors[$key]) . '</p>' : '';
$invalid = static fn (string $key): string => isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . $key . '"' : '';
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <p class="eyebrow-x">Kontrol &middot; Jadwal</p>
      <h1 class="admin-head__title">Jadwal pemilihan</h1>
      <p class="admin-head__lede">Status dihitung otomatis dari jadwal terhadap <strong>jam server</strong> (WIB), bukan jam perangkat. Menutup lebih awal atau memperpanjang cukup mengubah waktu selesai; semua perubahan tercatat di audit log.</p>
    </div>
  </header>

  <section class="clock-panel" aria-label="Status saat ini">
    <div class="clock-panel__status">
      <p class="clock-panel__label">Status saat ini</p>
      <p class="clock-panel__value">
        <span class="badge badge--<?= esc(strtolower((string) $status), 'attr') ?>"><span class="badge__dot" aria-hidden="true"></span><?= esc(election_status_label($status)) ?></span>
      </p>
    </div>
    <div class="clock-panel__time">
      <p class="clock-panel__label">Jam server</p>
      <p class="clock-panel__value mono"><time datetime="<?= esc($now->toDateTimeString(), 'attr') ?>"><?= esc($now->toLocalizedString('d MMMM yyyy, HH.mm.ss')) ?> WIB</time></p>
    </div>
    <?php if ($election): ?>
      <div class="clock-panel__time">
        <p class="clock-panel__label">Aturan status</p>
        <p class="clock-panel__rule">Sebelum mulai: <strong>Belum Dibuka</strong> &middot; mulai s.d. sebelum selesai: <strong>Sedang Berlangsung</strong> &middot; sejak waktu selesai: <strong>Sudah Selesai</strong></p>
      </div>
    <?php endif; ?>
  </section>

  <div class="detail-grid">
    <section class="panel" aria-labelledby="schedule-title">
      <header class="panel__head">
        <h2 class="panel__title" id="schedule-title"><?= $election ? 'Ubah jadwal' : 'Buat pemilihan' ?></h2>
      </header>
      <form action="<?= site_url('admin/jadwal') ?>" method="post" class="stack" novalidate
            <?php if ($status === 'ONGOING'): ?>data-confirm="Pemilihan sedang berlangsung. Simpan perubahan jadwal? Pemilih langsung mengikuti jadwal baru." data-confirm-button="Simpan jadwal"<?php endif; ?>
            <?php if ($status === 'FINISHED'): ?>data-confirm="Pemilihan sudah selesai dan hasil akhir sudah final. Simpan jadwal baru? Bila waktu selesai dipindah ke masa depan, pencoblosan dibuka kembali." data-confirm-button="Simpan jadwal" data-confirm-danger<?php endif; ?>>
        <?= csrf_field() ?>
        <div class="field">
          <label for="nama">Nama pemilihan</label>
          <input type="text" id="nama" name="nama" maxlength="150" required value="<?= esc($values['nama'], 'attr') ?>"<?= $invalid('nama') ?>>
          <?= $error('nama') ?>
        </div>
        <div class="field field--short">
          <label for="tahun">Tahun</label>
          <input type="number" id="tahun" name="tahun" min="2000" max="2100" inputmode="numeric" required value="<?= esc($values['tahun'], 'attr') ?>"<?= $invalid('tahun') ?>>
          <?= $error('tahun') ?>
        </div>
        <div class="form-grid form-grid--fit">
          <div class="field">
            <label for="start_at">Mulai (WIB)</label>
            <input type="datetime-local" id="start_at" name="start_at" required value="<?= esc($values['start_at'], 'attr') ?>"<?= $invalid('start_at') ?>>
            <?= $error('start_at') ?>
          </div>
          <div class="field">
            <label for="end_at">Selesai (WIB)</label>
            <input type="datetime-local" id="end_at" name="end_at" required value="<?= esc($values['end_at'], 'attr') ?>"<?= $invalid('end_at') ?>>
            <?= $error('end_at') ?>
          </div>
        </div>
        <p class="field-hint">Waktu selesai harus setelah waktu mulai. Tepat pada waktu selesai, pencoblosan ditolak server.</p>
        <?php if ($status === 'FINISHED'): ?>
          <label class="check check--block">
            <input type="checkbox" name="confirm_reopen" value="1"<?= $invalid('confirm_reopen') ?>>
            <span>Saya memahami: bila jadwal baru membuat pemilihan belum selesai, hasil akhir ditarik, pencoblosan dibuka kembali, dan perubahan ini tercatat di audit log.</span>
          </label>
          <?= $error('confirm_reopen') ?>
        <?php endif; ?>
        <button type="submit" class="btn" data-loading-text="Menyimpan..."><?= icon('check') ?> <?= $election ? 'Simpan jadwal' : 'Buat pemilihan' ?></button>
      </form>
    </section>

    <?php if ($election): ?>
      <section class="panel" aria-labelledby="quick-title">
        <header class="panel__head">
          <h2 class="panel__title" id="quick-title">Tindakan cepat</h2>
        </header>

        <?php if ($status === 'UPCOMING'): ?>
          <form action="<?= site_url('admin/jadwal/buka') ?>" method="post" class="quick-action"
                data-confirm="Buka pemilihan sekarang? Waktu mulai diganti menjadi jam server saat ini dan siswa/guru dapat langsung mencoblos."
                data-confirm-button="Buka sekarang">
            <?= csrf_field() ?>
            <h3>Buka sekarang</h3>
            <p>Waktu mulai diganti dengan jam server saat tombol ditekan. Waktu selesai tetap <?= esc(format_waktu($election['end_at'])) ?>.</p>
            <button type="submit" class="btn"><?= icon('clock') ?> Buka pemilihan sekarang</button>
          </form>
        <?php elseif ($status === 'ONGOING'): ?>
          <form action="<?= site_url('admin/jadwal/tutup') ?>" method="post" class="quick-action quick-action--danger"
                data-confirm="Tutup pemilihan sekarang? Waktu selesai diganti menjadi jam server saat ini. Pencoblosan langsung ditolak dan hasil menjadi final."
                data-confirm-button="Tutup sekarang" data-confirm-danger>
            <?= csrf_field() ?>
            <h3>Tutup lebih awal</h3>
            <p>Waktu selesai diganti dengan jam server saat tombol ditekan. Untuk memperpanjang, ubah waktu selesai di formulir.</p>
            <button type="submit" class="btn btn--danger"><?= icon('lock') ?> Tutup pemilihan sekarang</button>
          </form>
        <?php else: ?>
          <div class="quick-action">
            <h3>Pemilihan sudah selesai</h3>
            <p>Lihat <a href="<?= site_url('admin/hasil') ?>">hasil akhir</a>. Untuk membuka kembali, ubah waktu selesai ke waktu mendatang dan centang konfirmasi pembukaan kembali (tercatat di audit log).</p>
          </div>
        <?php endif; ?>

        <dl class="kv-list">
          <div class="kv-list__row"><dt>Mulai</dt><dd><?= esc(format_waktu($election['start_at'], 'd MMMM yyyy, HH.mm.ss')) ?></dd></div>
          <div class="kv-list__row"><dt>Selesai</dt><dd><?= esc(format_waktu($election['end_at'], 'd MMMM yyyy, HH.mm.ss')) ?></dd></div>
          <div class="kv-list__row"><dt>Terakhir diubah</dt><dd><?= esc(format_waktu($election['updated_at'] ?? null)) ?></dd></div>
        </dl>
      </section>
    <?php endif; ?>
  </div>
</div>
<?= $this->endSection() ?>
