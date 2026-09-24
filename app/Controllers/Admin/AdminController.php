<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Models\AuditLogModel;
use App\Models\ElectionModel;

/**
 * Dasar semua halaman panel admin (Stage 3).
 *
 * Seluruh route turunan berada di grup filter "adminauth" (Routes.php):
 * AdminAuthFilter sudah menjamin sesi admin valid dan akun masih ada.
 * Identitas admin selalu dari sesi, tidak pernah dari input form.
 */
abstract class AdminController extends BaseController
{
    /**
     * Stage 4: pesan saat data penentu hasil akhir dikunci (pemilihan FINISHED).
     */
    public const RESULTS_LOCKED_MESSAGE = 'Pemilihan sudah selesai, jadi data yang menentukan hasil akhir dikunci agar hasil tidak berubah. '
        . 'Bila benar-benar perlu, buka kembali pemilihan lewat menu Jadwal (tercatat di audit log).';

    private ?array $currentAdmin = null;
    private bool $electionLoaded = false;
    private ?array $currentElection = null;

    protected function adminId(): int
    {
        return (int) session()->get('admin_id');
    }

    protected function admin(): array
    {
        return $this->currentAdmin ??= (array) model(AdminModel::class)->findForSession($this->adminId());
    }

    /**
     * Election berjalan (status sudah dihitung ulang dari jadwal & jam server).
     */
    protected function election(): ?array
    {
        if (! $this->electionLoaded) {
            $this->currentElection = model(ElectionModel::class)->getCurrentElection();
            $this->electionLoaded  = true;
        }

        return $this->currentElection;
    }

    protected function forgetElection(): void
    {
        $this->electionLoaded = false;
    }

    /**
     * Stage 4: setelah pemilihan selesai (FINISHED menurut jam server), hasil
     * akhir final. Tindakan yang dapat mengubah angka atau susunan hasil
     * (status & hapus pemilih, impor pemilih, tambah/hapus pasangan, nomor
     * urut & status aktif pasangan) ditolak. Satu-satunya jalan mengubahnya
     * adalah membuka kembali pemilihan lewat jadwal (konfirmasi + audit log).
     */
    protected function resultsLocked(): bool
    {
        return ($this->election()['status'] ?? null) === ElectionModel::STATUS_FINISHED;
    }

    /**
     * Render view dalam layout admin dengan data bersama (admin, election, menu aktif).
     */
    protected function render(string $view, array $data = [], string $nav = ''): string
    {
        return view($view, $data + [
            'admin'         => $this->admin(),
            'election'      => $this->election(),
            'nav'           => $nav,
            'resultsLocked' => $this->resultsLocked(),
        ]);
    }

    /**
     * Catat tindakan admin ke audit_logs (+ IP request).
     *
     * @param array{election_id?: int|null, vote_unlock_log_id?: int|null} $context
     */
    protected function audit(string $action, string $description, array $context = []): void
    {
        model(AuditLogModel::class)->log(
            $this->adminId(),
            $action,
            $description,
            $context + ['ip_address' => $this->request->getIPAddress()],
        );
    }

    /**
     * Nomor halaman dari query string (?page=, atau ?page_<grup>= untuk
     * pagination bergrup), minimal 1.
     */
    protected function page(string $group = 'default'): int
    {
        $page = $this->request->getGet($group === 'default' ? 'page' : 'page_' . $group);

        return is_string($page) && ctype_digit($page) ? max(1, min((int) $page, 100000)) : 1;
    }

    /**
     * Link pagination dengan template admin (query string lain dipertahankan).
     * Grup selain "default" memakai ?page_<grup>= (dua tabel di satu halaman).
     */
    protected function pagerLinks(int $page, int $perPage, int $total, string $group = 'default'): string
    {
        return service('pager')->makeLinks($page, $perPage, $total, 'admin', 0, $group);
    }
}
