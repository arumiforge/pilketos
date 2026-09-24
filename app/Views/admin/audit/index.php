<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Audit log (hanya-baca).
 *
 * @var list<array<string, mixed>> $rows
 * @var int                        $total
 * @var string                     $action
 * @var string                     $query
 * @var string                     $pager
 * @var array<string, string>      $actions
 */
use App\Models\AuditLogModel;
use CodeIgniter\I18n\Time;
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <?= view('admin/partials/crumbs', ['trail' => [['Audit log']]]) ?>
      <div class="admin-head__heading">
        <h1 class="admin-head__title">Audit log</h1>
        <?= view('admin/partials/head_hint') ?>
      </div>
      <p class="admin-head__lede" id="admin-head-lede" data-lede>Jejak tindakan administratif penting: unlock hak suara, perubahan jadwal, perubahan pasangan calon, impor data, serta perubahan status/penghapusan pemilih. Log tidak dapat diubah atau dihapus dari panel.</p>
    </div>
  </header>

  <form class="filters" method="get" action="<?= site_url('admin/riwayat') ?>" role="search" data-autosubmit>
    <div class="field filters__search">
      <label for="a-q">Cari (keterangan, alasan, pemilih, admin)</label>
      <input type="search" id="a-q" name="q" value="<?= esc($query, 'attr') ?>" maxlength="100" autocomplete="off">
    </div>
    <div class="field">
      <label for="a-action">Tindakan</label>
      <select id="a-action" name="action">
        <option value="">Semua tindakan</option>
        <?php foreach ($actions as $key => $label): ?>
          <option value="<?= esc($key, 'attr') ?>"<?= $action === $key ? ' selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filters__actions">
      <button type="submit" class="btn btn--sm"><?= icon('search') ?> Terapkan</button>
      <?php if ($action !== '' || $query !== ''): ?><a class="btn btn--sm btn--outline" href="<?= site_url('admin/riwayat') ?>">Reset</a><?php endif; ?>
    </div>
  </form>

  <p class="result-count" role="status"><strong><?= angka($total) ?></strong> catatan.</p>

  <?php if ($rows === []): ?>
    <p class="empty">Belum ada catatan audit<?= $action !== '' || $query !== '' ? ' yang cocok' : '' ?>.</p>
  <?php else: ?>
    <div class="table-scroll" role="region" aria-label="Tabel audit log" tabindex="0">
      <table class="data-table data-table--audit">
        <thead>
          <tr>
            <th scope="col">Waktu</th>
            <th scope="col">Admin</th>
            <th scope="col">Tindakan</th>
            <th scope="col">Pemilih &middot; jenis</th>
            <th scope="col">Pemilihan &middot; alasan &middot; keterangan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
              $voterName = $row['student_name'] ?? $row['teacher_name'];
              $voterId   = $row['nisn'] ?? $row['nip'];
              $voterType = $row['student_id'] !== null ? 'Siswa' : ($row['teacher_id'] !== null ? 'Guru' : null);
            ?>
            <tr>
              <td class="nowrap">
                <time datetime="<?= esc((string) $row['created_at'], 'attr') ?>">
                  <?= esc(Time::parse((string) $row['created_at'])->toLocalizedString('d MMM yyyy')) ?>
                  <span class="cell-sub"><?= esc(format_waktu($row['created_at'], 'HH.mm.ss')) ?></span>
                </time>
              </td>
              <td><?= esc($row['admin_name']) ?><span class="cell-sub">@<?= esc($row['admin_username']) ?><?= $row['ip_address'] ? ' &middot; ' . esc($row['ip_address']) : '' ?></span></td>
              <td><span class="action-tag action-tag--<?= esc(strtolower($row['action']), 'attr') ?>"><?= esc(AuditLogModel::label($row['action'])) ?></span></td>
              <td>
                <?php if ($voterName !== null): ?>
                  <?= esc($voterName) ?>
                  <span class="cell-sub"><?= esc((string) $voterType) ?> &middot; <span class="mono"><?= esc($voterId) ?></span></span>
                <?php else: ?>
                  &ndash;
                <?php endif; ?>
              </td>
              <td class="audit-text">
                <?php if ($row['election_nama'] !== null): ?><p class="audit-text__meta"><?= esc($row['election_nama'] . ' ' . $row['election_tahun']) ?></p><?php endif; ?>
                <?php if ($row['reason'] !== null): ?><p><strong>Alasan:</strong> <?= esc($row['reason']) ?></p><?php endif; ?>
                <?php if ($row['description'] !== null && $row['description'] !== ''): ?><p class="audit-text__desc"><?= esc($row['description']) ?></p><?php endif; ?>
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
