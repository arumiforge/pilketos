<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Detail suara (admin saja).
 *
 * @var list<array<string, mixed>> $candidates
 * @var list<string>               $classes
 * @var array                      $filters
 * @var list<array<string, mixed>> $rows
 * @var int                        $total
 * @var int                        $offset
 * @var int                        $counted Total suara sah (dasbor)
 * @var string                     $pager
 */
use CodeIgniter\I18n\Time;

$accent = array_column($candidates, null, 'id');
$filtered = $filters['q'] !== '' || $filters['type'] !== '' || $filters['kelas'] !== '' || $filters['gender'] !== '' || $filters['candidate'] > 0 || $filters['status'] !== 'LOCKED';
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <?= view('admin/partials/crumbs', ['trail' => [['Analitik', 'admin/analitik'], ['Detail suara']]]) ?>
      <div class="admin-head__heading">
        <h1 class="admin-head__title">Detail suara</h1>
        <?= view('admin/partials/head_hint') ?>
      </div>
      <p class="admin-head__lede" id="admin-head-lede" data-lede>
        Bawaan: suara sah (terkunci) dari pemilih aktif &mdash; jumlahnya sama dengan total suara di dasbor
        (<strong><?= angka($counted) ?></strong>). Perangkat dan browser dibaca server dari User-Agent saat mencoblos.
      </p>
    </div>
  </header>

  <form class="filters" method="get" action="<?= site_url('admin/analitik/suara') ?>" role="search" data-autosubmit>
    <div class="field filters__search">
      <label for="f-q">Cari nama / NISN / NIP</label>
      <input type="search" id="f-q" name="q" value="<?= esc($filters['q'], 'attr') ?>" maxlength="100" autocomplete="off">
    </div>
    <div class="field">
      <label for="f-type">Jenis pemilih</label>
      <select id="f-type" name="type">
        <option value="">Semua</option>
        <option value="student"<?= $filters['type'] === 'student' ? ' selected' : '' ?>>Siswa</option>
        <option value="teacher"<?= $filters['type'] === 'teacher' ? ' selected' : '' ?>>Guru</option>
      </select>
    </div>
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
      <label for="f-gender">Jenis kelamin</label>
      <select id="f-gender" name="gender">
        <option value="">Semua</option>
        <option value="L"<?= $filters['gender'] === 'L' ? ' selected' : '' ?>>Laki-laki</option>
        <option value="P"<?= $filters['gender'] === 'P' ? ' selected' : '' ?>>Perempuan</option>
      </select>
    </div>
    <div class="field">
      <label for="f-candidate">Pasangan</label>
      <select id="f-candidate" name="candidate">
        <option value="">Semua</option>
        <?php foreach ($candidates as $c): ?>
          <option value="<?= (int) $c['id'] ?>"<?= $filters['candidate'] === (int) $c['id'] ? ' selected' : '' ?>><?= esc($c['label'] . ' - ' . $c['ketua']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="f-status">Status suara</label>
      <select id="f-status" name="status">
        <option value="LOCKED"<?= $filters['status'] === 'LOCKED' ? ' selected' : '' ?>>Sah (terkunci)</option>
        <option value="UNLOCKED"<?= $filters['status'] === 'UNLOCKED' ? ' selected' : '' ?>>Riwayat dibuka admin</option>
        <option value="all"<?= $filters['status'] === 'all' ? ' selected' : '' ?>>Semua baris</option>
      </select>
    </div>
    <div class="filters__actions">
      <button type="submit" class="btn btn--sm"><?= icon('search') ?> Terapkan</button>
      <?php if ($filtered): ?><a class="btn btn--sm btn--outline" href="<?= site_url('admin/analitik/suara') ?>">Reset</a><?php endif; ?>
    </div>
    <?php if ($filters['kelas'] !== '' || $filters['gender'] !== ''): ?>
      <p class="filters__note">Filter kelas/jenis kelamin hanya berlaku untuk siswa, sehingga guru tidak ditampilkan.</p>
    <?php endif; ?>
  </form>

  <p class="result-count" role="status"><strong><?= angka($total) ?></strong> baris suara<?= $filtered ? ' sesuai filter' : '' ?>.</p>

  <?php if ($rows === []): ?>
    <p class="empty"><?= $filtered ? 'Tidak ada suara yang cocok dengan filter.' : 'Belum ada suara masuk.' ?></p>
  <?php else: ?>
    <div class="table-scroll" role="region" aria-label="Tabel detail suara" tabindex="0">
      <table class="data-table data-table--votes">
        <thead>
          <tr>
            <th scope="col" class="num">No</th>
            <th scope="col">Pemilih &middot; NISN / NIP</th>
            <th scope="col">Kelas</th>
            <th scope="col" class="num">Absen</th>
            <th scope="col">JK</th>
            <th scope="col">Pilihan</th>
            <th scope="col">Waktu</th>
            <th scope="col">Perangkat &middot; browser</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $i => $row): ?>
            <?php
              $isStudent = $row['voter_type'] === 'student';
              $c         = $accent[(int) $row['candidate_id']] ?? null;
              $detailUrl = site_url(($isStudent ? 'admin/siswa/' : 'admin/guru/') . $row['voter_id']);
            ?>
            <tr>
              <td class="num"><?= $offset + $i + 1 ?></td>
              <th scope="row">
                <a href="<?= esc($detailUrl, 'attr') ?>"><?= esc($row['name']) ?></a>
                <span class="cell-sub"><?= $isStudent ? 'Siswa' : 'Guru' ?> &middot; <span class="mono"><?= esc($row['identifier']) ?></span></span>
              </th>
              <td><?= esc($row['kelas'] ?? '-') ?></td>
              <td class="num"><?= esc($row['nomor_absen'] ?? '-') ?></td>
              <td><?= esc($row['jenis_kelamin'] ?? '-') ?></td>
              <td>
                <span class="cand-tag"<?= $c ? ' style="--accent: ' . esc($c['accent'], 'attr') . '; --accent-ink: ' . esc($c['accent_ink'], 'attr') . ';"' : '' ?>>
                  <span class="cand-chip"><?= sprintf('%02d', (int) $row['nomor_urut']) ?></span>
                  <?= esc($row['nama_ketua']) ?>
                </span>
              </td>
              <td class="nowrap">
                <time datetime="<?= esc($row['voted_at'], 'attr') ?>">
                  <?= esc(Time::parse($row['voted_at'])->toLocalizedString('d MMM yyyy')) ?>
                  <span class="cell-sub"><?= esc(format_waktu($row['voted_at'], 'HH.mm.ss')) ?></span>
                </time>
              </td>
              <td>
                <?= esc($row['device_info'] ?? '-') ?>
                <span class="cell-sub"><?= esc($row['browser_info'] ?? '-') ?></span>
              </td>
              <td>
                <?php if ($row['status'] === 'LOCKED' && (int) $row['voter_active'] === 1): ?>
                  <span class="pill pill--ink"><?= icon('lock') ?> Terkunci</span>
                <?php elseif ($row['status'] === 'LOCKED'): ?>
                  <span class="pill pill--muted">Terkunci &middot; pemilih nonaktif, tidak dihitung</span>
                <?php else: ?>
                  <span class="pill pill--outline"><?= icon('unlock') ?> Dibuka <?= esc(format_waktu($row['unlocked_at'], 'd MMM, HH.mm')) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= $pager ?>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
