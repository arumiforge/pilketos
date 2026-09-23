<?php

namespace App\Controllers\Admin;

use App\Services\UnlockResult;
use App\Services\UnlockService;
use App\Services\VoterType;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Unlock hak suara (MASTER section 18, Stage 3).
 *
 * Alur: cari pemilih -> lihat status suara -> buka halaman unlock ->
 * isi alasan + centang konfirmasi -> "Unlock Hak Suara".
 *
 * Admin TIDAK memilih dan TIDAK mengubah pilihan: UnlockService hanya
 * mengubah baris LOCKED menjadi riwayat UNLOCKED, mencatat vote_unlock_logs
 * dan audit_logs. Pemilih kemudian login dan memilih sendiri.
 */
class UnlockController extends AdminController
{
    /**
     * GET admin/unlock?q=
     */
    public function index()
    {
        $query    = mb_substr(trim((string) $this->request->getGet('q')), 0, 100);
        $election = $this->election();

        return $this->render('admin/unlock/index', [
            'title'   => 'Unlock Hak Suara',
            'query'   => $query,
            'results' => $query === '' ? [] : service('voterDirectory')->search($query, $election === null ? null : (int) $election['id']),
            'recent'  => $this->recentUnlocks(),
        ], 'unlock');
    }

    /**
     * GET admin/unlock/{student|teacher}/{id}
     */
    public function form($type = null, $id = null)
    {
        [$voterType, $voter] = $this->voterOr404($type, $id);
        $election            = $this->election();

        return $this->render('admin/unlock/form', [
            'title'     => 'Unlock Hak Suara',
            'type'      => $voterType,
            'voter'     => $voter,
            'vote'      => service('voterDirectory')->activeVote($voterType, (int) $voter['id'], $election === null ? null : (int) $election['id']),
            'unlocks'   => service('voterDirectory')->unlockLogs($voterType, (int) $voter['id']),
            'reasonMin' => UnlockService::REASON_MIN,
            'reasonMax' => UnlockService::REASON_MAX,
            'errors'    => (array) (session()->getFlashdata('errors') ?? []),
        ], 'unlock');
    }

    /**
     * POST admin/unlock/{student|teacher}/{id}
     * Body: vote_id (suara yang dilihat admin), reason, confirm=1.
     */
    public function unlock($type = null, $id = null): RedirectResponse
    {
        [$voterType, $voter] = $this->voterOr404($type, $id);
        $back                = 'admin/unlock/' . $voterType->value . '/' . $voter['id'];
        $voteId              = (string) $this->request->getPost('vote_id');
        $reason              = $this->request->getPost('reason');

        $errors = [];
        if (UnlockService::normalizeReason($reason) === null) {
            $errors['reason'] = sprintf('Alasan unlock wajib diisi, %d sampai %d karakter.', UnlockService::REASON_MIN, UnlockService::REASON_MAX);
        }
        if ($this->request->getPost('confirm') !== '1') {
            $errors['confirm'] = 'Centang konfirmasi bahwa pemilih akan login dan memilih sendiri.';
        }
        if (! ctype_digit($voteId)) {
            $errors['vote_id'] = 'Data suara tidak valid. Muat ulang halaman.';
        }

        if ($errors !== []) {
            return redirect()->to($back)->withInput()->with('errors', $errors);
        }

        $result = service('unlock')->unlock(
            $voterType,
            (int) $voter['id'],
            (int) $voteId,
            $this->adminId(),
            $reason,
            null,
            $this->request->getIPAddress(),
        );

        if (! $result->isOk()) {
            $redirect = redirect()->to($back)->with('error', $result->message());

            return $result->status === UnlockResult::RETRY || $result->status === UnlockResult::INVALID_REASON
                ? $redirect->withInput()
                : $redirect;
        }

        return redirect()->to($voterType->adminPath((string) $voter['id']))->with('success', sprintf(
            'Hak suara %s berhasil dibuka. Suara lama disimpan sebagai riwayat dan tidak dihitung; pemilih perlu login lalu memilih sendiri.',
            $voter['name'],
        ));
    }

    /**
     * @return array{0: VoterType, 1: array<string, mixed>}
     */
    private function voterOr404(mixed $type, mixed $id): array
    {
        $voterType = is_string($type) ? VoterType::tryFrom($type) : null;
        $voter     = $voterType !== null && is_string($id) && ctype_digit($id)
            ? service('voterDirectory')->find($voterType, (int) $id)
            : null;

        if ($voter === null) {
            throw PageNotFoundException::forPageNotFound('Pemilih tidak ditemukan.');
        }

        return [$voterType, $voter];
    }

    /**
     * 10 unlock terakhir (siswa & guru) untuk ringkasan di halaman unlock.
     *
     * @return list<array<string, mixed>>
     */
    private function recentUnlocks(): array
    {
        return db_connect()->table('vote_unlock_logs l')
            ->select('l.id, l.reason, l.unlocked_at, l.student_id, l.teacher_id, a.name AS admin_name, '
                . 'COALESCE(s.name, t.name) AS voter_name, COALESCE(s.nisn, t.nip) AS identifier', false)
            ->join('admins a', 'a.id = l.admin_id')
            ->join('students s', 's.id = l.student_id', 'left')
            ->join('teachers t', 't.id = l.teacher_id', 'left')
            ->orderBy('l.id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();
    }
}
