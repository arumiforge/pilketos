<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Unlock: cari pemilih -> lihat status suara.
 *
 * @var string                     $query
 * @var list<array<string, mixed>> $results
 * @var list<array<string, mixed>> $recent
 * @var array|null                 $election
 */
use App\Services\VoterType;

$ongoing = ($election['status'] ?? null) === 'ONGOING';
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <p class="eyebrow-x">Kontrol &middot; Unlock</p>
      <h1 class="admin-head__title">Unlock hak suara</h1>
      <p class="admin-head__lede">Untuk pemilih yang benar-benar salah memilih. Unlock hanya membuka kembali hak suara: admin tidak memilihkan dan tidak mengubah pilihan. Suara lama disimpan sebagai riwayat dan tidak dihitung; pemilih harus login lalu memilih sendiri.</p>
    </div>
  </header>

  <?php if (! $ongoing): ?>
    <p class="notice"><?= icon('info') ?><span>Unlock hanya tersedia saat pemilihan <strong>sedang berlangsung</strong>. Setelah selesai, hasil akhir tidak dapat diubah lewat unlock.</span></p>
  <?php endif; ?>

  <ol class="steps" aria-label="Tahapan unlock">
    <li class="steps__item is-current"><span>01</span> Cari pemilih</li>
    <li class="steps__item"><span>02</span> Lihat status suara</li>
    <li class="steps__item"><span>03</span> Isi alasan</li>
    <li class="steps__item"><span>04</span> Konfirmasi</li>
  </ol>

  <form class="search-hero" method="get" action="<?= site_url('admin/buka-kunci') ?>" role="search">
    <label for="q" class="search-hero__label">Cari siswa atau guru</label>
    <div class="search-hero__row">
      <input type="search" id="q" name="q" value="<?= esc($query, 'attr') ?>" maxlength="100" placeholder="Nama, NISN, atau NIP" autocomplete="off" autofocus>
      <button type="submit" class="btn btn--lg"><?= icon('search') ?> Cari</button>
    </div>
  </form>

  <?php if ($query !== ''): ?>
    <p class="result-count" role="status"><strong><?= angka(count($results)) ?></strong> pemilih ditemukan untuk "<?= esc($query) ?>".</p>

    <?php if ($results === []): ?>
      <p class="empty">Tidak ada siswa atau guru yang cocok. Periksa ejaan nama atau nomor identitas.</p>
    <?php else: ?>
      <div class="table-scroll" role="region" aria-label="Hasil pencarian pemilih" tabindex="0">
        <table class="data-table">
          <thead>
            <tr>
              <th scope="col">Pemilih</th>
              <th scope="col">Jenis</th>
              <th scope="col">NISN / NIP</th>
              <th scope="col">Kelas</th>
              <th scope="col">Status suara</th>
              <th scope="col">Pilihan &amp; waktu</th>
              <th scope="col"><span class="visually-hidden">Aksi</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $r): ?>
              <?php /** @var VoterType $t */ $t = $r['type']; ?>
              <tr<?= (int) $r['status_aktif'] === 0 ? ' class="is-muted"' : '' ?>>
                <th scope="row">
                  <a href="<?= site_url($t->adminPath((string) $r['id'])) ?>"><?= esc($r['name']) ?></a>
                  <?php if ((int) $r['status_aktif'] === 0): ?><span class="pill pill--muted">Nonaktif</span><?php endif; ?>
                </th>
                <td><?= esc($t->label()) ?></td>
                <td class="mono"><?= esc($r['identifier']) ?></td>
                <td><?= esc($r['kelas'] ?? '-') ?></td>
                <td>
                  <?php if ($r['vote_id'] !== null): ?>
                    <span class="pill pill--ink"><?= icon('lock') ?> Terkunci</span>
                  <?php else: ?>
                    <span class="pill pill--outline">Belum memilih</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($r['vote_id'] !== null): ?>
                    <span class="cand-chip cand-chip--plain"><?= sprintf('%02d', (int) $r['nomor_urut']) ?></span> <?= esc($r['nama_ketua']) ?>
                    <span class="cell-sub"><?= esc(format_waktu($r['voted_at'], 'd MMM, HH.mm.ss')) ?></span>
                  <?php else: ?>
                    &ndash;
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($r['vote_id'] !== null && $ongoing): ?>
                    <a class="btn btn--sm btn--danger" href="<?= site_url($t->unlockPath($r['id'])) ?>"><?= icon('unlock') ?> Unlock Hak Suara<span class="visually-hidden"> <?= esc($r['name']) ?></span></a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <section class="panel" aria-labelledby="recent-title">
    <header class="panel__head">
      <h2 class="panel__title" id="recent-title">Unlock terakhir</h2>
      <a class="panel__link" href="<?= site_url('admin/riwayat?action=UNLOCK_VOTE') ?>">Audit log unlock <?= icon('arrow-right') ?></a>
    </header>
    <?php if ($recent === []): ?>
      <p class="empty">Belum pernah ada unlock.</p>
    <?php else: ?>
      <ol class="log-list">
        <?php foreach ($recent as $u): ?>
          <li class="log-list__item">
            <p class="log-list__meta">
              <?= esc(format_waktu($u['unlocked_at'], 'd MMM yyyy, HH.mm.ss')) ?> &middot;
              <?= $u['student_id'] !== null ? 'Siswa' : 'Guru' ?> <strong><?= esc($u['voter_name']) ?></strong> (<?= esc($u['identifier']) ?>) &middot; oleh <?= esc($u['admin_name']) ?>
            </p>
            <p class="log-list__text"><?= esc($u['reason']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>
</div>
<?= $this->endSection() ?>
