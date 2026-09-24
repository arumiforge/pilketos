<?php
/**
 * Detail suara (admin saja). Stage 11: baris siswa dan guru dipisah dalam
 * tabel masing-masing (kolom sesuai jenisnya, pagination sendiri:
 * ?page_siswa= / ?page_guru=). Perangkat: model/kategori di baris pertama,
 * "HP · Android 14 · Chrome 140" di bawahnya (device_summary()).
 *
 * @var list<array<string, mixed>> $candidates
 * @var list<string>               $classes
 * @var array                      $filters
 * @var array{student: array, teacher: array} $groups rows/total/offset/pager per jenis
 * @var int                        $total   Jumlah baris siswa + guru
 */
use CodeIgniter\I18n\Time;

$accent   = array_column($candidates, null, 'id');
$filtered = $filters['q'] !== '' || $filters['type'] !== '' || $filters['rombel'] !== '' || $filters['gender'] !== '' || $filters['candidate'] > 0 || $filters['status'] !== 'LOCKED';
$studentOnly = $filters['rombel'] !== '' || $filters['gender'] !== '';

$blocks = [];
if ($filters['type'] !== 'teacher') {
    $blocks['student'] = ['Siswa', 'NISN', 'siswa'];
}
if ($filters['type'] !== 'student') {
    $blocks['teacher'] = ['Guru', 'NIP', 'guru'];
}

/**
 * Sel yang sama untuk tabel siswa & guru: pilihan, waktu, perangkat, status.
 */
$sharedCells = static function (array $row) use ($accent): string {
    $c      = $accent[(int) $row['candidate_id']] ?? null;
    $device = device_summary($row['device_info'] ?? null, $row['browser_info'] ?? null);
    $style  = $c ? ' style="--accent: ' . esc($c['accent'], 'attr') . '; --accent-ink: ' . esc($c['accent_ink'], 'attr') . ';"' : '';

    $html = '<td><span class="cand-tag"' . $style . '><span class="cand-chip">' . sprintf('%02d', (int) $row['nomor_urut']) . '</span> '
        . esc($row['nama_ketua']) . '</span></td>';
    $html .= '<td class="nowrap"><time datetime="' . esc($row['voted_at'], 'attr') . '">'
        . esc(Time::parse($row['voted_at'])->toLocalizedString('d MMM yyyy'))
        . '<span class="cell-sub">' . esc(format_waktu($row['voted_at'], 'HH.mm.ss')) . '</span></time></td>';
    $html .= '<td class="device-cell">' . ($device['kind'] !== null ? icon($device['kind'], 'device-cell__icon') : '')
        . '<span class="device-cell__main">' . esc($device['main']) . '</span>'
        . ($device['sub'] !== '' ? '<span class="cell-sub">' . esc($device['sub']) . '</span>' : '') . '</td>';

    if ($row['status'] === 'LOCKED' && (int) $row['voter_active'] === 1) {
        $status = '<span class="pill pill--ink">' . icon('lock') . ' Terkunci</span>';
    } elseif ($row['status'] === 'LOCKED') {
        $status = '<span class="pill pill--muted">Terkunci &middot; pemilih nonaktif, tidak dihitung</span>';
    } else {
        $status = '<span class="pill pill--outline">' . icon('unlock') . ' Dibuka ' . esc(format_waktu($row['unlocked_at'], 'd MMM, HH.mm')) . '</span>';
    }

    return $html . '<td>' . $status . '</td>';
};
?>
<form class="filters" method="get" action="<?= site_url('admin/analitik/suara') ?>" role="search" data-autosubmit data-pane-form data-live-search>
  <div class="field filters__search">
    <label for="f-q">Cari nama / NISN / NIP</label>
    <span class="search-field"><?= icon('search', 'search-field__icon') ?><input type="search" id="f-q" name="q" value="<?= esc($filters['q'], 'attr') ?>" maxlength="100" autocomplete="off"></span>
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
    <label for="f-rombel">Rombel</label>
    <select id="f-rombel" name="rombel">
      <option value="">Semua</option>
      <?php foreach ($classes as $kelas): ?>
        <option value="<?= esc($kelas, 'attr') ?>"<?= $filters['rombel'] === $kelas ? ' selected' : '' ?>><?= esc($kelas) ?></option>
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
  <div class="filters__actions" data-live-region="actions">
    <button type="submit" class="btn btn--sm"><?= icon('search') ?> Terapkan</button>
    <?php if ($filtered): ?><a class="btn btn--sm btn--outline" href="<?= site_url('admin/analitik/suara') ?>">Reset</a><?php endif; ?>
  </div>
</form>

<div data-live-region="results">
  <p class="result-count" role="status"><strong><?= angka($total) ?></strong> baris suara<?= $filtered ? ' sesuai filter' : '' ?><?php if (count($blocks) === 2 && ! $studentOnly): ?>: <?= angka($groups['student']['total']) ?> siswa &middot; <?= angka($groups['teacher']['total']) ?> guru<?php endif; ?>.</p>

  <?php foreach ($blocks as $type => [$label, $idLabel, $group]): ?>
    <?php $g = $groups[$type]; $isStudent = $type === 'student'; ?>
    <section class="vote-block" aria-labelledby="votes-<?= esc($group, 'attr') ?>">
      <header class="vote-block__head">
        <h3 class="vote-block__title" id="votes-<?= esc($group, 'attr') ?>"><?= esc($label) ?></h3>
        <span class="vote-block__count"><?= angka($g['total']) ?> baris</span>
      </header>

      <?php if (! $isStudent && $studentOnly): ?>
        <p class="empty">Filter rombel/jenis kelamin hanya berlaku untuk siswa, sehingga guru tidak ditampilkan.</p>
      <?php elseif ($g['rows'] === []): ?>
        <p class="empty"><?= $filtered ? 'Tidak ada suara ' . strtolower($label) . ' yang cocok dengan filter.' : 'Belum ada suara ' . strtolower($label) . ' masuk.' ?></p>
      <?php else: ?>
        <div class="table-scroll" role="region" aria-label="Tabel detail suara <?= esc(strtolower($label), 'attr') ?>" tabindex="0">
          <table class="data-table data-table--votes data-table--votes-<?= esc($group, 'attr') ?>">
            <thead>
              <tr>
                <th scope="col" class="num">No</th>
                <th scope="col"><?= esc($label) ?> &middot; <?= esc($idLabel) ?></th>
                <?php if ($isStudent): ?>
                  <th scope="col">Rombel</th>
                  <th scope="col" class="num">Absen</th>
                  <th scope="col">JK</th>
                <?php endif; ?>
                <th scope="col">Pilihan</th>
                <th scope="col">Waktu</th>
                <th scope="col">Perangkat &middot; browser</th>
                <th scope="col">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($g['rows'] as $i => $row): ?>
                <tr>
                  <td class="num"><?= $g['offset'] + $i + 1 ?></td>
                  <th scope="row">
                    <a href="<?= site_url(($isStudent ? 'admin/siswa/' : 'admin/guru/') . $row['voter_id']) ?>"><?= esc($row['name']) ?></a>
                    <span class="cell-sub mono"><?= esc($row['identifier']) ?></span>
                  </th>
                  <?php if ($isStudent): ?>
                    <td><?= esc($row['kelas'] ?? '-') ?></td>
                    <td class="num"><?= esc($row['nomor_absen'] ?? '-') ?></td>
                    <td><?= esc($row['jenis_kelamin'] ?? '-') ?></td>
                  <?php endif; ?>
                  <?= $sharedCells($row) ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?= $g['pager'] ?>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>
