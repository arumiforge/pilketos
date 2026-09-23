<?php

namespace App\Controllers\Admin;

use App\Services\VoterDirectory;
use App\Services\VoterType;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;

/**
 * Data pemilih di panel admin (Stage 3): daftar + pencarian + filter,
 * detail (status & riwayat suara, log unlock), ubah status akun, hapus.
 *
 * Student\... dan Teacher\... hanya menetapkan voterType(); tabel siswa
 * dan guru tetap terpisah. Pilihan kandidat hanya tampil di halaman detail
 * (untuk keperluan unlock), tidak di daftar.
 */
abstract class VoterController extends AdminController
{
    abstract protected function voterType(): VoterType;

    /**
     * GET admin/students | admin/teachers
     */
    public function index()
    {
        $type      = $this->voterType();
        $directory = service('voterDirectory');
        $classes   = $type === VoterType::Student ? service('analytics')->classes() : [];
        $filters   = VoterDirectory::filters($type, (array) $this->request->getGet(), $classes);
        $page      = $this->page();
        $election  = $this->election();
        $result    = $directory->paginate($type, $election === null ? null : (int) $election['id'], $filters, $page);

        return $this->render('admin/voters/index', [
            'title'   => 'Data ' . $type->label(),
            'type'    => $type,
            'filters' => $filters,
            'classes' => $classes,
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'offset'  => ($page - 1) * VoterDirectory::PER_PAGE,
            'pager'   => $this->pagerLinks($page, VoterDirectory::PER_PAGE, $result['total']),
            'summary' => service('analytics')->snapshot($election)['summary'][$type === VoterType::Student ? 'students' : 'teachers'],
        ], $type->voterTable());
    }

    /**
     * GET admin/students/{id} | admin/teachers/{id}
     */
    public function show($id = null)
    {
        $type      = $this->voterType();
        $voter     = $this->findOr404($id);
        $directory = service('voterDirectory');
        $election  = $this->election();

        return $this->render('admin/voters/show', [
            'title'     => $voter['name'],
            'type'      => $type,
            'voter'     => $voter,
            'vote'      => $directory->activeVote($type, (int) $voter['id'], $election === null ? null : (int) $election['id']),
            'history'   => $directory->history($type, (int) $voter['id']),
            'unlocks'   => $directory->unlockLogs($type, (int) $voter['id']),
            'deletable' => $directory->historyCount($type, (int) $voter['id']) === 0,
        ], $type->voterTable());
    }

    /**
     * POST admin/students/{id}/status  (status_aktif = 0|1)
     * Pemilih nonaktif tidak dapat login dan tidak dihitung dalam analitik.
     */
    public function status($id = null)
    {
        $type   = $this->voterType();
        $voter  = $this->findOr404($id);
        $target = $this->request->getPost('status_aktif');

        if (! in_array($target, ['0', '1'], true)) {
            return redirect()->to($type->adminPath((string) $voter['id']))->with('error', 'Status akun tidak valid.');
        }

        if ((int) $voter['status_aktif'] === (int) $target) {
            return redirect()->to($type->adminPath((string) $voter['id']));
        }

        $type->voterModel()->builder()->where('id', (int) $voter['id'])->update([
            'status_aktif' => (int) $target,
            'updated_at'   => Time::now()->toDateTimeString(),
        ]);

        $verb = $target === '1' ? 'diaktifkan' : 'dinonaktifkan';
        $this->audit($type->auditAction('status'), sprintf(
            '%s %s (%s %s) %s.',
            $type->label(),
            $voter['name'],
            $type->identifierLabel(),
            $voter[$type->identifierColumn()],
            $verb,
        ));

        return redirect()->to($type->adminPath((string) $voter['id']))->with('success', sprintf(
            'Akun %s %s. %s',
            $voter['name'],
            $verb,
            $target === '1' ? 'Pemilih dapat login kembali.' : 'Pemilih tidak dapat login dan tidak dihitung di analitik.',
        ));
    }

    /**
     * POST admin/students/{id}/delete
     * Hanya untuk data tanpa riwayat suara/unlock (mis. salah impor).
     */
    public function delete($id = null)
    {
        $type      = $this->voterType();
        $voter     = $this->findOr404($id);
        $directory = service('voterDirectory');
        $label     = sprintf('%s %s (%s %s)', $type->label(), $voter['name'], $type->identifierLabel(), $voter[$type->identifierColumn()]);

        if ($directory->historyCount($type, (int) $voter['id']) > 0) {
            return redirect()->to($type->adminPath((string) $voter['id']))
                ->with('error', $label . ' memiliki riwayat suara sehingga tidak dapat dihapus. Nonaktifkan akun bila perlu.');
        }

        try {
            $type->voterModel()->delete((int) $voter['id']);
        } catch (DatabaseException) {
            return redirect()->to($type->adminPath((string) $voter['id']))
                ->with('error', $label . ' tidak dapat dihapus karena sudah dipakai data suara.');
        }

        $this->audit($type->auditAction('delete'), $label . ' dihapus.');

        return redirect()->to($type->adminPath())->with('success', $label . ' dihapus.');
    }

    private function findOr404(mixed $id): array
    {
        $voter = is_string($id) && ctype_digit($id)
            ? service('voterDirectory')->find($this->voterType(), (int) $id)
            : null;

        if ($voter === null) {
            throw PageNotFoundException::forPageNotFound($this->voterType()->label() . ' tidak ditemukan.');
        }

        return $voter;
    }
}
