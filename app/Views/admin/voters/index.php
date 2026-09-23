<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Daftar siswa / guru.
 *
 * @var \App\Services\VoterType    $type
 * @var array                      $filters
 * @var list<string>               $classes
 * @var list<array<string, mixed>> $rows
 * @var int                        $total
 * @var int                        $offset
 * @var string                     $pager
 * @var array                      $summary Tally jenis pemilih ini (aktif)
 */
use App\Services\VoterType;

$isStudent = $type === VoterType::Student;
$idLabel   = $type->identifierLabel();
$idColumn  = $type->identifierColumn();
$filtered  = $filters['q'] !== '' || $filters['kelas'] !== '' || $filters['jk'] !== '' || $filters['vote'] !== '' || $filters['status'] !== 'aktif';
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <p class="eyebrow-x">Data &middot; <?= esc($type->label()) ?></p>
      <h1 class="admin-head__title">Data <?= esc(strtolower($type->label())) ?></h1>
      <p class="admin-head__lede">
        <?= angka($summary['total']) ?> <?= esc(strtolower($type->label())) ?> aktif &middot;
        <?= angka($summary['voted']) ?> sudah memilih &middot; <?= angka($summary['not_voted']) ?> belum memilih.
        <?= $isStudent ? '' : 'Guru adalah pemilih dengan identitas terpisah dari siswa, bukan admin.' ?>
      </p>
    </div>
    <div class="admin-head__actions">
      <a class="btn" href="<?= site_url($type->adminPath('import')) ?>"><?= icon('upload') ?> Impor Excel</a>
    </div>
  </header>

  <form class="filters" method="get" action="<?= site_url($type->adminPath()) ?>" role="search" data-autosubmit>
    <div class="field filters__search">
      <label for="f-q">Cari nama / <?= esc($idLabel) ?></label>
      <input type="search" id="f-q" name="q" value="<?= esc($filters['q'], 'attr') ?>" maxlength="100" autocomplete="off">
    </div>
    <?php if ($isStudent): ?>
      <div class="field">
        <label for="f-kelas">Kelas</label>
        <select id="f-kelas" name="kelas">
          <option value="">Semua</option>
          <?php foreach ($classes as $kelas): ?>
            <option value="<?= esc($kelas, 'attr') ?>"<?= $filters['kelas'] === $kelas ? ' selected' : '' ?>><?= esc($kelas) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="f-jk">Jenis kelamin</label>
        <select id="f-jk" name="jk">
          <option value="">Semua</option>
          <option value="L"<?= $filters['jk'] === 'L' ? ' selected' : '' ?>>Laki-laki</option>
          <option value="P"<?= $filters['jk'] === 'P' ? ' selected' : '' ?>>Perempuan</option>
        </select>
      </div>
    <?php endif; ?>
    <div class="field">
      <label for="f-vote">Status memilih</label>
      <select id="f-vote" name="vote">
        <option value="">Semua</option>
        <option value="sudah"<?= $filters['vote'] === 'sudah' ? ' selected' : '' ?>>Sudah memilih</option>
        <option value="belum"<?= $filters['vote'] === 'belum' ? ' selected' : '' ?>>Belum memilih</option>
      </select>
    </div>
    <div class="field">
      <label for="f-status">Akun</label>
      <select id="f-status" name="status">
        <option value="aktif"<?= $filters['status'] === 'aktif' ? ' selected' : '' ?>>Aktif</option>
        <option value="nonaktif"<?= $filters['status'] === 'nonaktif' ? ' selected' : '' ?>>Nonaktif</option>
        <option value="semua"<?= $filters['status'] === 'semua' ? ' selected' : '' ?>>Semua</option>
      </select>
    </div>
    <div class="filters__actions">
      <button type="submit" class="btn btn--sm"><?= icon('search') ?> Terapkan</button>
      <?php if ($filtered): ?><a class="btn btn--sm btn--outline" href="<?= site_url($type->adminPath()) ?>">Reset</a><?php endif; ?>
    </div>
  </form>

  <div class="list-bar">
    <p class="result-count" role="status"><strong><?= angka($total) ?></strong> <?= esc(strtolower($type->label())) ?><?= $filtered ? ' sesuai filter' : '' ?>.</p>
    <button type="button" class="btn btn--sm btn--outline" data-secret-toggle aria-pressed="false" aria-controls="voter-table" hidden><?= icon('eye') ?> <span data-secret-toggle-label>Tampilkan kode unik</span></button>
  </div>

  <?php if ($rows === []): ?>
    <p class="empty">
      <?php if ($filtered): ?>
        Tidak ada <?= esc(strtolower($type->label())) ?> yang cocok dengan filter.
      <?php else: ?>
        Belum ada data <?= esc(strtolower($type->label())) ?>. <a href="<?= site_url($type->adminPath('import')) ?>">Impor dari Excel</a>.
      <?php endif; ?>
    </p>
  <?php else: ?>
    <div class="table-scroll" role="region" aria-label="Tabel data <?= esc(strtolower($type->label()), 'attr') ?>" tabindex="0">
      <table class="data-table data-table--voters secrets" id="voter-table" data-secrets>
        <thead>
          <tr>
            <th scope="col" class="num">No</th>
            <th scope="col"><?= esc($idLabel) ?></th>
            <th scope="col">Nama</th>
            <?php if ($isStudent): ?>
              <th scope="col">JK</th>
              <th scope="col">Kelas</th>
              <th scope="col" class="num">Absen</th>
            <?php endif; ?>
            <th scope="col">Kode unik</th>
            <th scope="col">Status memilih</th>
            <th scope="col"><span class="visually-hidden">Aksi</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $i => $row): ?>
            <?php $url = site_url($type->adminPath((string) $row['id'])); ?>
            <tr<?= (int) $row['status_aktif'] === 0 ? ' class="is-muted"' : '' ?>>
              <td class="num"><?= $offset + $i + 1 ?></td>
              <td class="mono"><?= esc($row[$idColumn]) ?></td>
              <th scope="row">
                <a href="<?= esc($url, 'attr') ?>"><?= esc($row['name']) ?></a>
                <?php if ((int) $row['status_aktif'] === 0): ?><span class="pill pill--muted">Nonaktif</span><?php endif; ?>
              </th>
              <?php if ($isStudent): ?>
                <td><?= esc($row['jenis_kelamin']) ?></td>
                <td><?= esc($row['kelas']) ?></td>
                <td class="num"><?= esc($row['nomor_absen'] ?? '-') ?></td>
              <?php endif; ?>
              <td class="mono"><span class="secret"><?= esc($row['kodeunik']) ?></span></td>
              <td>
                <?php if ($row['vote_id'] !== null): ?>
                  <span class="pill pill--ink"><?= icon('lock') ?> Sudah</span>
                  <span class="cell-sub"><?= esc(format_waktu($row['voted_at'], 'd MMM, HH.mm')) ?></span>
                <?php else: ?>
                  <span class="pill pill--outline">Belum</span>
                <?php endif; ?>
              </td>
              <td><a class="row-link" href="<?= esc($url, 'attr') ?>">Detail<span class="visually-hidden"> <?= esc($row['name']) ?></span> <?= icon('arrow-right') ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= $pager ?>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
