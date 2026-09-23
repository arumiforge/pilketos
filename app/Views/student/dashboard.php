<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="dash-head">
  <div class="container">
    <p class="eyebrow">Dasbor Siswa</p>
    <h1 class="dash-head__name"><?= esc($student['name']) ?></h1>
    <dl class="dash-meta">
      <div>
        <dt>NISN</dt>
        <dd><?= esc($student['nisn']) ?></dd>
      </div>
      <div>
        <dt>Kelas</dt>
        <dd><?= esc($student['kelas']) ?></dd>
      </div>
      <div>
        <dt>Nomor Absen</dt>
        <dd><?= esc($student['nomor_absen'] ?? '-') ?></dd>
      </div>
    </dl>
  </div>
</section>

<section class="section">
  <div class="container dash-grid">
    <?= view('partials/vote_status', [
        'type'      => $type,
        'election'  => $election,
        'vote'      => $vote,
        'candidate' => $candidate,
        'canVote'   => $canVote,
    ]) ?>

    <aside class="dash-aside" aria-labelledby="schedule-title">
      <h2 class="dash-aside__title" id="schedule-title">Jadwal pemilihan</h2>
      <?= $this->include('partials/election_status') ?>
      <?php if (in_array($election['status'] ?? null, ['UPCOMING', 'ONGOING'], true)): ?>
        <?= view('partials/countdown', ['election' => $election, 'variant' => 'compact']) ?>
      <?php endif; ?>
    </aside>
  </div>
</section>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('assets/js/countdown.js') ?>" defer></script>
<?= $this->endSection() ?>
