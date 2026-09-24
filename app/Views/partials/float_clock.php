<?php
/**
 * Jam pemilihan melayang (Stage 8, dasbor siswa & guru): "Ditutup dalam
 * 03:12:45" / "Dibuka dalam ..." menempel di bawah layar (position: sticky),
 * lalu berhenti di atas footer saat halaman habis digulir, jadi tidak pernah
 * menutupi footer. Hanya tampil saat pemilihan belum dibuka / berlangsung.
 *
 * @var array|null $election Hasil ElectionModel::getCurrentElection()
 */
$floatStatus = $election['status'] ?? null;

if (! $election || ! in_array($floatStatus, ['UPCOMING', 'ONGOING'], true)) {
    return;
}
?>
<aside class="float-clock float-clock--<?= esc(strtolower($floatStatus), 'attr') ?>" aria-label="Sisa waktu pemilihan">
  <span class="float-clock__led" aria-hidden="true"></span>
  <?= view('partials/countdown', ['election' => $election, 'variant' => 'terminal']) ?>
</aside>
