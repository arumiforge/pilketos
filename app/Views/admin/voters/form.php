<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
/**
 * Form tambah/ubah siswa / guru (Stage 11). Aturan isian sama dengan impor
 * Excel (VoterEditor + VoterImporter::validateForm).
 *
 * @var \App\Services\VoterType $type
 * @var array|null              $voter  Baris pemilih (null = baru)
 * @var array                   $values Nilai form (baris / input lama)
 * @var array<string, string>   $errors Pesan error per kolom
 * @var list<string>            $classes Rombel yang sudah ada (saran isian)
 * @var array|null              $election
 */
use App\Services\VoterType;

$isStudent = $type === VoterType::Student;
$isNew     = $voter === null;
$idColumn  = $type->identifierColumn();
$idLabel   = $type->identifierLabel();
$noun      = strtolower($type->label());
$action    = $isNew ? site_url($type->adminPath()) : site_url($type->adminPath((string) $voter['id']));
$cancel    = $isNew ? site_url($type->adminPath()) : site_url($type->adminPath((string) $voter['id']));
$value     = static fn (string $key): string => esc((string) ($values[$key] ?? ''), 'attr');
$error     = static fn (string $key): string => isset($errors[$key]) ? '<p class="field-error" id="err-' . esc($key, 'attr') . '">' . esc($errors[$key]) . '</p>' : '';
$described = static function (string $key, string $hint = '') use ($errors): string {
    $ids = array_filter([$hint, isset($errors[$key]) ? 'err-' . $key : '']);

    return ($ids === [] ? '' : ' aria-describedby="' . esc(implode(' ', $ids), 'attr') . '"')
        . (isset($errors[$key]) ? ' aria-invalid="true"' : '');
};
$gender = (string) ($values['jenis_kelamin'] ?? '');
$active = (string) ($values['status_aktif'] ?? '1') !== '0';
?>
<div class="admin-page">
  <header class="admin-head">
    <div class="admin-head__text">
      <?= view('admin/partials/crumbs', ['trail' => $isNew
          ? [[$type->label(), $type->adminPath()], ['Tambah']]
          : [[$type->label(), $type->adminPath()], ['Detail', $type->adminPath((string) $voter['id'])], ['Ubah']]]) ?>
      <div class="admin-head__heading">
        <h1 class="admin-head__title"><?= $isNew ? 'Tambah ' . esc($noun) : esc($voter['name']) ?></h1>
      </div>
    </div>
    <?php if ($isNew): ?>
      <div class="admin-head__actions">
        <a class="btn btn--outline" href="<?= site_url($type->adminPath('impor')) ?>"><?= icon('upload') ?> Banyak sekaligus? Impor Excel</a>
      </div>
    <?php endif; ?>
  </header>

  <?php if (($election['status'] ?? null) === 'ONGOING'): ?>
    <p class="notice"><?= icon('alert') ?><span>Pemilihan sedang berlangsung. Perubahan langsung berlaku: <?= $isStudent ? 'rombel menentukan rekap kelas & rombel, ' : '' ?><?= esc($idLabel) ?> dan kode unik dipakai untuk login.</span></p>
  <?php endif; ?>

  <form class="form-x" action="<?= $action ?>" method="post" novalidate>
    <?= csrf_field() ?>

    <fieldset class="form-x__part">
      <legend><span class="form-x__no">01</span> Identitas <?= esc($noun) ?></legend>
      <div class="form-grid">
        <div class="field">
          <label for="<?= esc($idColumn, 'attr') ?>"><?= esc($idLabel) ?></label>
          <input type="text" id="<?= esc($idColumn, 'attr') ?>" name="<?= esc($idColumn, 'attr') ?>" value="<?= $value($idColumn) ?>"
                 inputmode="numeric" autocomplete="off" spellcheck="false" required
                 maxlength="<?= $isStudent ? 14 : 40 ?>"<?= $described($idColumn, 'hint-' . $idColumn) ?>>
          <p class="field-hint" id="hint-<?= esc($idColumn, 'attr') ?>"><?= $isStudent
              ? 'Tepat 10 digit, angka 0 di depan dipertahankan. Dipakai siswa untuk login.'
              : 'Hanya angka (NIP PNS 18 digit; boleh nomor lain seperti NUPTK). Dipakai guru untuk login.' ?></p>
          <?= $error($idColumn) ?>
        </div>
        <div class="field">
          <label for="name">Nama lengkap</label>
          <input type="text" id="name" name="name" value="<?= $value('name') ?>" maxlength="150" autocomplete="off" required<?= $described('name') ?>>
          <?= $error('name') ?>
        </div>

        <?php if ($isStudent): ?>
          <fieldset class="choice-group"<?= $described('jenis_kelamin') ?>>
            <legend class="field__label">Jenis kelamin</legend>
            <div class="choice-group__items">
              <label class="check check--pill"><input type="radio" name="jenis_kelamin" value="L"<?= $gender === 'L' ? ' checked' : '' ?> required> <span>Laki-laki (L)</span></label>
              <label class="check check--pill"><input type="radio" name="jenis_kelamin" value="P"<?= $gender === 'P' ? ' checked' : '' ?>> <span>Perempuan (P)</span></label>
            </div>
            <?= $error('jenis_kelamin') ?>
          </fieldset>
          <div class="form-grid form-grid--pair">
            <div class="field">
              <label for="kelas">Rombel</label>
              <input type="text" id="kelas" name="kelas" value="<?= $value('kelas') ?>" maxlength="20" list="kelas-list" autocomplete="off" required<?= $described('kelas', 'hint-kelas') ?>>
              <datalist id="kelas-list">
                <?php foreach ($classes as $kelas): ?>
                  <option value="<?= esc($kelas, 'attr') ?>"></option>
                <?php endforeach; ?>
              </datalist>
              <p class="field-hint" id="hint-kelas">Diawali kelas 7, 8, atau 9, contoh 7A atau VIII-B.</p>
              <?= $error('kelas') ?>
            </div>
            <div class="field">
              <label for="nomor_absen">Nomor absen</label>
              <input type="number" id="nomor_absen" name="nomor_absen" value="<?= $value('nomor_absen') ?>" min="1" max="999" inputmode="numeric"<?= $described('nomor_absen', 'hint-absen') ?>>
              <p class="field-hint" id="hint-absen">Boleh kosong.</p>
              <?= $error('nomor_absen') ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </fieldset>

    <fieldset class="form-x__part">
      <legend><span class="form-x__no">02</span> Akun login</legend>
      <div class="form-grid">
        <div class="field">
          <label for="kodeunik">Kode unik (tanggal lahir)</label>
          <input type="text" id="kodeunik" name="kodeunik" value="<?= $value('kodeunik') ?>" inputmode="numeric" maxlength="10"
                 autocomplete="off" spellcheck="false" placeholder="DDMMYYYY" required<?= $described('kodeunik', 'hint-kodeunik') ?>>
          <p class="field-hint" id="hint-kodeunik">Format DDMMYYYY, contoh 01032013 untuk 1 Maret 2013. Dipakai bersama <?= esc($idLabel) ?> untuk login.</p>
          <?= $error('kodeunik') ?>
        </div>
        <?php if ($isNew): ?>
          <div class="field field--switch">
            <input type="hidden" name="status_aktif" value="0">
            <label class="switch">
              <input type="checkbox" name="status_aktif" value="1"<?= $active ? ' checked' : '' ?>>
              <span class="switch__track" aria-hidden="true"></span>
              <span class="switch__label">Akun aktif</span>
            </label>
            <p class="field-hint">Akun nonaktif tidak dapat login dan tidak dihitung sebagai pemilih.</p>
          </div>
        <?php else: ?>
          <p class="field-hint form-x__aside">Status akun (<?= (int) $voter['status_aktif'] === 1 ? 'aktif' : 'nonaktif' ?>) diubah lewat tombol di halaman detail, dengan konfirmasi.</p>
        <?php endif; ?>
      </div>
    </fieldset>

    <div class="form-x__submit">
      <button type="submit" class="btn btn--lg"><?= icon('check') ?> <?= $isNew ? 'Simpan ' . esc($noun) : 'Simpan perubahan' ?></button>
      <a class="btn btn--lg btn--outline" href="<?= $cancel ?>">Batal</a>
    </div>
  </form>
</div>
<?= $this->endSection() ?>
