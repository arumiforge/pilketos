<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Pratinjau import: ringkasan, pesan per baris, konfirmasi impor.
 *
 * @var \App\Services\VoterType             $type
 * @var \App\Services\Import\VoterImporter $importer
 * @var string                              $token
 * @var string                              $fileName
 * @var array<string, int>                  $summary
 * @var list<string>                        $notices
 * @var string                              $show
 * @var list<array<string, mixed>>          $rows
 * @var int                                 $total
 * @var string                              $pager
 * @var int                                 $expires
 */
use App\Controllers\Admin\ImportController;
use App\Services\Import\VoterImporter;
use App\Services\VoterType;

$isStudent = $type === VoterType::Student;
$idColumn  = $type->identifierColumn();
$labels    = [
    VoterImporter::ACTION_CREATE  => ['Baru', 'pill--ink'],
    VoterImporter::ACTION_UPDATE  => ['Diperbarui', 'pill--outline'],
    VoterImporter::ACTION_SAME    => ['Tidak berubah', 'pill--muted'],
    VoterImporter::ACTION_INVALID => ['Bermasalah', 'pill--danger'],
];
$fieldNames = ['name' => 'nama', 'jenis_kelamin' => 'jenis kelamin', 'kelas' => 'kelas', 'nomor_absen' => 'nomor absen', 'kodeunik' => 'kode unik'];
$counts = [
    'issues'                      => $summary['issues'],
    VoterImporter::ACTION_INVALID => $summary[VoterImporter::ACTION_INVALID],
    VoterImporter::ACTION_CREATE  => $summary[VoterImporter::ACTION_CREATE],
    VoterImporter::ACTION_UPDATE  => $summary[VoterImporter::ACTION_UPDATE],
    VoterImporter::ACTION_SAME    => $summary[VoterImporter::ACTION_SAME],
    'all'                         => $summary['rows'],
];
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <?= view('admin/partials/crumbs', ['trail' => [[$type->label(), $type->adminPath()], ['Impor Excel', $type->adminPath('impor')], ['Pratinjau']]]) ?>
      <div class="admin-head__heading">
        <h1 class="admin-head__title">Periksa sebelum impor</h1>
        <?= view('admin/partials/head_hint') ?>
      </div>
      <p class="admin-head__lede" id="admin-head-lede" data-lede>File <strong><?= esc($fileName) ?></strong> &middot; belum ada data yang disimpan. Pratinjau berlaku sampai pukul <?= esc(\CodeIgniter\I18n\Time::createFromTimestamp($expires, config('App')->appTimezone)->toLocalizedString('HH.mm')) ?> WIB.</p>
    </div>
  </header>

  <ol class="steps" aria-label="Tahapan impor">
    <li class="steps__item is-done"><span>01</span> Unduh template</li>
    <li class="steps__item is-done"><span>02</span> Unggah</li>
    <li class="steps__item is-current"><span>03</span> Pratinjau &amp; validasi</li>
    <li class="steps__item"><span>04</span> Impor</li>
    <li class="steps__item"><span>05</span> Hasil</li>
  </ol>

  <dl class="scoreboard scoreboard--compact">
    <div class="scoreboard__cell"><dt>Baris dibaca</dt><dd class="scoreboard__value"><?= angka($summary['rows']) ?></dd></div>
    <div class="scoreboard__cell"><dt>Baru</dt><dd class="scoreboard__value"><?= angka($summary[VoterImporter::ACTION_CREATE]) ?></dd></div>
    <div class="scoreboard__cell"><dt>Diperbarui</dt><dd class="scoreboard__value"><?= angka($summary[VoterImporter::ACTION_UPDATE]) ?></dd></div>
    <div class="scoreboard__cell"><dt>Tidak berubah</dt><dd class="scoreboard__value"><?= angka($summary[VoterImporter::ACTION_SAME]) ?></dd></div>
    <div class="scoreboard__cell<?= $summary[VoterImporter::ACTION_INVALID] > 0 ? ' scoreboard__cell--danger' : '' ?>"><dt>Bermasalah</dt><dd class="scoreboard__value"><?= angka($summary[VoterImporter::ACTION_INVALID]) ?></dd><dd class="scoreboard__sub">tidak diimpor</dd></div>
    <div class="scoreboard__cell"><dt>Dengan peringatan</dt><dd class="scoreboard__value"><?= angka($summary['warnings']) ?></dd><dd class="scoreboard__sub">tetap diimpor, mohon cek</dd></div>
  </dl>

  <?php foreach ($notices as $notice): ?>
    <p class="notice"><?= icon('info') ?><span><?= esc($notice) ?></span></p>
  <?php endforeach; ?>

  <nav class="tabs" aria-label="Saring baris pratinjau">
    <?php foreach (ImportController::PREVIEW_FILTERS as $key => $label): ?>
      <a class="tabs__link" href="<?= site_url($type->adminPath('impor/cek/' . $token)) ?>?show=<?= esc($key, 'url') ?>"<?= $show === $key ? ' aria-current="page"' : '' ?>>
        <?= esc($label) ?> <span class="tabs__count"><?= angka($counts[$key]) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($rows === []): ?>
    <p class="empty">Tidak ada baris pada saringan ini.</p>
  <?php else: ?>
    <div class="table-scroll" role="region" aria-label="Baris pratinjau impor" tabindex="0">
      <table class="data-table data-table--import">
        <thead>
          <tr>
            <th scope="col" class="num">Baris</th>
            <th scope="col">Status</th>
            <th scope="col"><?= esc($type->identifierLabel()) ?></th>
            <th scope="col">Nama</th>
            <?php if ($isStudent): ?>
              <th scope="col">JK</th>
              <th scope="col">Kelas</th>
              <th scope="col" class="num">Absen</th>
            <?php endif; ?>
            <th scope="col">Kode unik</th>
            <th scope="col">Catatan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php [$label, $class] = $labels[$row['action']]; $v = $row['values']; ?>
            <tr class="<?= $row['action'] === VoterImporter::ACTION_INVALID ? 'is-invalid' : '' ?>">
              <td class="num"><?= (int) $row['row'] ?></td>
              <td><span class="pill <?= $class ?>"><?= esc($label) ?></span></td>
              <td class="mono"><?= esc((string) ($v[$idColumn] ?? '-')) ?></td>
              <td><?= esc((string) ($v['name'] ?? '-')) ?></td>
              <?php if ($isStudent): ?>
                <td><?= esc((string) ($v['jenis_kelamin'] ?? '-')) ?></td>
                <td><?= esc((string) ($v['kelas'] ?? '-')) ?></td>
                <td class="num"><?= esc((string) ($v['nomor_absen'] ?? '-')) ?></td>
              <?php endif; ?>
              <td class="mono"><?= esc((string) ($v['kodeunik'] ?? '-')) ?></td>
              <td class="notes">
                <?php foreach ($row['errors'] as $message): ?>
                  <p class="notes__error"><?= icon('alert') ?> <?= esc($message) ?></p>
                <?php endforeach; ?>
                <?php foreach ($row['warnings'] as $message): ?>
                  <p class="notes__warn"><?= icon('info') ?> <?= esc($message) ?></p>
                <?php endforeach; ?>
                <?php if ($row['changes'] !== []): ?>
                  <p class="notes__change">Berubah: <?= esc(implode(', ', array_map(static fn ($f) => $fieldNames[$f] ?? $f, array_keys($row['changes'])))) ?></p>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= $pager ?>
  <?php endif; ?>

  <section class="panel commit-panel" aria-labelledby="commit-title">
    <header class="panel__head"><h2 class="panel__title" id="commit-title">04 &middot; Impor</h2></header>
    <?php if ($summary['importable'] === 0): ?>
      <p>Tidak ada baris baru atau berubah untuk diimpor. <a href="<?= site_url($type->adminPath('impor')) ?>">Unggah file lain</a>.</p>
    <?php else: ?>
      <form action="<?= site_url($type->adminPath('impor/simpan')) ?>" method="post" class="stack"
            data-confirm="<?= esc(sprintf('Impor %d baris (%d baru, %d diperbarui) ke data %s?', $summary['importable'], $summary['create'], $summary['update'], strtolower($type->label())), 'attr') ?>"
            data-confirm-button="Impor sekarang">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">
        <p><strong><?= angka($summary['importable']) ?></strong> baris akan disimpan: <?= angka($summary['create']) ?> baru dan <?= angka($summary['update']) ?> diperbarui. Semua baris diproses dalam satu transaksi.</p>
        <?php if ($summary[VoterImporter::ACTION_INVALID] > 0): ?>
          <label class="check check--block">
            <input type="checkbox" name="confirm_skip" value="1" required>
            <span>Saya mengerti <?= angka($summary[VoterImporter::ACTION_INVALID]) ?> baris bermasalah tidak akan diimpor.</span>
          </label>
        <?php endif; ?>
        <div class="cluster">
          <button type="submit" class="btn btn--lg" data-loading-text="Mengimpor..."><?= icon('check') ?> Impor <?= angka($summary['importable']) ?> baris</button>
          <a class="btn btn--lg btn--outline" href="<?= site_url($type->adminPath('impor')) ?>">Batal &amp; unggah ulang</a>
        </div>
      </form>
    <?php endif; ?>
  </section>
</div>
<?= $this->endSection() ?>
