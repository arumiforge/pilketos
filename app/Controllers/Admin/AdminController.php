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
     * Render view dalam layout admin dengan data bersama (admin, election, menu aktif).
     */
    protected function render(string $view, array $data = [], string $nav = ''): string
    {
        return view($view, $data + [
            'admin'    => $this->admin(),
            'election' => $this->election(),
            'nav'      => $nav,
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
     * Nomor halaman dari query string (?page=), minimal 1.
     */
    protected function page(): int
    {
        $page = $this->request->getGet('page');

        return is_string($page) && ctype_digit($page) ? max(1, min((int) $page, 100000)) : 1;
    }

    /**
     * Link pagination dengan template admin (query string lain dipertahankan).
     */
    protected function pagerLinks(int $page, int $perPage, int $total): string
    {
        return service('pager')->makeLinks($page, $perPage, $total, 'admin');
    }
}
