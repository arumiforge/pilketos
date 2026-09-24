<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Unlock satu pemilih: status suara + alasan + konfirmasi.
 *
 * @var \App\Services\VoterType    $type
 * @var array                      $voter
 * @var array|null                 $vote
 * @var list<array<string, mixed>> $unlocks
 * @var int                        $reasonMin
 * @var int                        $reasonMax
 * @var array<string, string>      $errors
 * @var array|null                 $election
 */
use App\Libraries\CandidateTheme;

$ongoing = ($election['status'] ?? null) === 'ONGOING';
$old     = session()->getFlashdata('_ci_old_input')['post'] ?? [];
$c       = $vote === null ? null : CandidateTheme::present([
    'id'           => $vote['candidate_id'],
    'nomor_urut'   => $vote['nomor_urut'],
    'nama_ketua'   => $vote['nama_ketua'],
    'nama_wakil'   => $vote['nama_wakil'],
    'theme_accent' => $vote['theme_accent'],
]);
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <div class="admin-head__heading">
        <h1 class="admin-head__title"><?= esc($voter['name']) ?></h1>
        <?= view('admin/partials/head_hint') ?>
      </div>
      <p class="admin-head__lede" id="admin-head-lede" data-lede>
        <?= esc($type->identifierLabel()) ?> <span class="mono"><?= esc($voter[$type->identifierColumn()]) ?></span>
        <?= isset($voter['kelas']) ? ' &middot; Rombel ' . esc($voter['kelas']) : '' ?>
        <?php if ((int) $voter['status_aktif'] === 0): ?><span class="pill pill--muted">Akun nonaktif</span><?php endif; ?>
      </p>
    </div>
  </header>

  <ol class="steps" aria-label="Tahapan unlock">
    <li class="steps__item is-done"><span class="steps__node" aria-hidden="true"><?= icon('check') ?></span><span class="steps__label">Cari pemilih<span class="visually-hidden"> (selesai)</span></span></li>
    <li class="steps__item is-current" aria-current="step"><span class="steps__node" aria-hidden="true">02</span><span class="steps__label">Lihat status suara</span></li>
    <li class="steps__item is-current"><span class="steps__node" aria-hidden="true">03</span><span class="steps__label">Isi alasan</span></li>
    <li class="steps__item is-current"><span class="steps__node" aria-hidden="true">04</span><span class="steps__label">Konfirmasi</span></li>
  </ol>

  <?php if ($vote === null): ?>
    <p class="notice"><?= icon('info') ?><span>Pemilih ini tidak memiliki suara terkunci pada pemilihan berjalan, sehingga tidak ada yang perlu dibuka.</span></p>
    <p><a class="btn btn--outline" href="<?= site_url($type->adminPath((string) $voter['id'])) ?>">Lihat detail pemilih</a></p>
  <?php else: ?>
    <div class="detail-grid">
      <section class="panel" aria-labelledby="current-title">
        <header class="panel__head"><h2 class="panel__title" id="current-title">Suara saat ini</h2></header>
        <div class="vote-state vote-state--locked" style="<?= esc($c['style'], 'attr') ?>">
          <p class="vote-state__label"><?= icon('lock') ?> Terkunci (LOCKED)</p>
          <p class="vote-state__choice"><span class="cand-chip"><?= esc($c['label']) ?></span> <?= esc($c['ketua']) ?> &amp; <?= esc($c['wakil']) ?></p>
          <dl class="vote-state__meta">
            <div><dt>Waktu memilih</dt><dd><?= esc(format_waktu($vote['voted_at'], 'd MMMM yyyy, HH.mm.ss')) ?></dd></div>
            <div><dt>Perangkat</dt><dd><?= esc($vote['device_info'] ?? '-') ?></dd></div>
            <div><dt>Browser</dt><dd><?= esc($vote['browser_info'] ?? '-') ?></dd></div>
          </dl>
        </div>
        <h3 class="panel__subtitle">Yang terjadi setelah unlock</h3>
        <ul class="check-list">
          <li>Baris suara ini berubah menjadi riwayat <strong>UNLOCKED</strong> (tidak dihapus, pilihan tidak diubah) dan tidak dihitung lagi.</li>
          <li>Unlock dicatat di log unlock dan audit log: admin, pemilih, pemilihan, alasan, waktu.</li>
          <li>Pemilih harus login sendiri lalu mencoblos lagi. Admin tidak dapat memilihkan.</li>
        </ul>
      </section>

      <section class="panel" aria-labelledby="unlock-title">
        <header class="panel__head"><h2 class="panel__title" id="unlock-title">Unlock Hak Suara</h2></header>
        <?php if (! $ongoing): ?>
          <p class="notice"><?= icon('lock') ?><span>Unlock hanya dapat dilakukan saat pemilihan sedang berlangsung.</span></p>
        <?php else: ?>
          <form action="<?= site_url($type->unlockPath($voter['id'])) ?>" method="post" class="stack" novalidate
                data-confirm="<?= esc('Unlock hak suara ' . $voter['name'] . '? Suara Pasangan ' . $c['label'] . ' menjadi riwayat dan tidak dihitung. Pemilih harus memilih ulang sendiri.', 'attr') ?>"
                data-confirm-button="Unlock Hak Suara" data-confirm-danger>
            <?= csrf_field() ?>
            <input type="hidden" name="vote_id" value="<?= (int) $vote['id'] ?>">
            <div class="field">
              <label for="reason">Alasan unlock (wajib)</label>
              <textarea id="reason" name="reason" rows="4" minlength="<?= $reasonMin ?>" maxlength="<?= $reasonMax ?>" required
                        aria-describedby="reason-hint<?= isset($errors['reason']) ? ' err-reason' : '' ?>"<?= isset($errors['reason']) ? ' aria-invalid="true"' : '' ?>><?= esc((string) ($old['reason'] ?? '')) ?></textarea>
              <p class="field-hint" id="reason-hint"><?= $reasonMin ?>&ndash;<?= $reasonMax ?> karakter. Contoh: "Pemilih salah menekan pasangan, dikonfirmasi wali kelas pukul 09.20."</p>
              <?php if (isset($errors['reason'])): ?><p class="field-error" id="err-reason"><?= esc($errors['reason']) ?></p><?php endif; ?>
            </div>
            <label class="check check--block">
              <input type="checkbox" name="confirm" value="1" required<?= isset($errors['confirm']) ? ' aria-invalid="true" aria-describedby="err-confirm"' : '' ?>>
              <span>Saya memastikan pemilih akan login dan memilih sendiri. Saya tidak memilihkan pasangan untuk pemilih.</span>
            </label>
            <?php if (isset($errors['confirm'])): ?><p class="field-error" id="err-confirm"><?= esc($errors['confirm']) ?></p><?php endif; ?>
            <?php if (isset($errors['vote_id'])): ?><p class="field-error"><?= esc($errors['vote_id']) ?></p><?php endif; ?>
            <button type="submit" class="btn btn--danger btn--lg" data-loading-text="Memproses..."><?= icon('unlock') ?> Unlock Hak Suara</button>
          </form>
        <?php endif; ?>
      </section>
    </div>
  <?php endif; ?>

  <?php if ($unlocks !== []): ?>
    <section class="panel" aria-labelledby="history-title">
      <header class="panel__head"><h2 class="panel__title" id="history-title">Unlock sebelumnya untuk pemilih ini</h2></header>
      <ol class="log-list">
        <?php foreach ($unlocks as $u): ?>
          <li class="log-list__item">
            <p class="log-list__meta"><?= esc(format_waktu($u['unlocked_at'], 'd MMM yyyy, HH.mm.ss')) ?> &middot; oleh <?= esc($u['admin_name']) ?></p>
            <p class="log-list__text"><?= esc($u['reason']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('crumbs') ?>
<?= view('admin/partials/crumbs', ['trail' => [['Unlock hak suara', 'admin/buka-kunci'], [$type->label()]]]) ?>
<?= $this->endSection() ?>
