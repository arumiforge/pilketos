<?php

namespace App\Controllers;

use App\Libraries\CandidateTheme;
use App\Libraries\DeviceInfo;
use App\Models\CandidateModel;
use App\Services\VoteResult;
use App\Services\VoterType;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Alur voting bersama siswa & guru (Stage 2).
 *
 * Student\VoteController dan Teacher\VoteController hanya menetapkan
 * voterType(); route masing-masing berada di grup filter studentauth /
 * teacherauth. Identitas pemilih selalu dari sesi ("<role>_id"), tidak pernah
 * dari input, sehingga tidak ada IDOR: tidak ada parameter id pemilih/suara.
 *
 * Halaman:
 * - index   : kandidat + visi-misi interaktif + surat suara (paku/coblos);
 * - confirm : konfirmasi tanpa JavaScript (fallback terakhir);
 * - submit  : simpan suara (AJAX JSON atau form POST biasa);
 * - myVote  : pilihan milik pemilih sendiri (setelah memilih / login ulang).
 */
abstract class VotingController extends BaseController
{
    /**
     * Batas request submit per pemilih (anti-spam, bukan pengaman suara
     * ganda; pengaman suara ganda ada di transaction + unique key).
     */
    private const SUBMIT_CAPACITY = 10;
    private const SUBMIT_SECONDS  = 60;

    abstract protected function voterType(): VoterType;

    protected function voterId(): int
    {
        return (int) session()->get($this->voterType()->voterKey());
    }

    /**
     * Profil pemilih aktif (filter auth sudah menjamin ada & aktif).
     */
    protected function voter(): array
    {
        return $this->voterType()->voterModel()->findActive($this->voterId());
    }

    /**
     * GET <role>/vote
     */
    public function index()
    {
        $type  = $this->voterType();
        $state = service('voting')->ballotState($type, $this->voterId());

        if ($state['vote'] !== null) {
            // Sudah memilih: tidak ada form voting lagi.
            return redirect()->to($type->path('my-vote'));
        }

        return view('voting/index', [
            'title'      => 'Kandidat & Surat Suara',
            'type'       => $type,
            'voter'      => $this->voter(),
            'election'   => $state['election'],
            'canVote'    => $state['canVote'],
            'candidates' => CandidateTheme::presentAll(model(CandidateModel::class)->getActiveCandidates()),
        ]);
    }

    /**
     * GET <role>/vote/confirm/{candidateId}
     * Konfirmasi tanpa JavaScript. Versi JS memakai modal di halaman index.
     */
    public function confirm($candidateId = null)
    {
        $type  = $this->voterType();
        $state = service('voting')->ballotState($type, $this->voterId());

        if ($state['vote'] !== null) {
            return redirect()->to($type->path('my-vote'));
        }

        if (! $state['canVote']) {
            return redirect()->to($type->path('vote'))
                ->with('error', VoteResult::rejected(VoteResult::VOTING_CLOSED, $state['status'])->message());
        }

        $candidate = ctype_digit((string) $candidateId)
            ? model(CandidateModel::class)->where('status_aktif', 1)->find((int) $candidateId)
            : null;

        if ($candidate === null) {
            return redirect()->to($type->path('vote'))
                ->with('error', VoteResult::rejected(VoteResult::INVALID_CANDIDATE)->message());
        }

        return view('voting/confirm', [
            'title'     => 'Konfirmasi Pilihan',
            'type'      => $type,
            'candidate' => CandidateTheme::present($candidate),
        ]);
    }

    /**
     * POST <role>/vote
     * Body: candidate_id (JSON dari ballot.js atau form POST dari halaman konfirmasi).
     */
    public function submit()
    {
        $type   = $this->voterType();
        $raw    = $this->candidateInput();
        $digits = is_int($raw) ? (string) $raw : (is_string($raw) ? trim($raw) : '');

        if ($digits === '' || ! ctype_digit($digits) || strlen($digits) > 10) {
            return $this->respondVote(VoteResult::rejected(VoteResult::INVALID_CANDIDATE));
        }

        $throttleKey = 'vote_submit_' . $type->value . '_' . $this->voterId();
        if (! service('throttler')->check($throttleKey, self::SUBMIT_CAPACITY, self::SUBMIT_SECONDS)) {
            return $this->respondThrottled();
        }

        $result = service('voting')->castVote(
            $type,
            $this->voterId(),
            (int) $digits,
            DeviceInfo::fromUserAgent($this->request->getUserAgent()),
        );

        return $this->respondVote($result);
    }

    /**
     * GET <role>/my-vote
     * Hanya pilihan milik pemilih sendiri: tanpa jumlah suara, peringkat,
     * atau data kandidat lain.
     */
    public function myVote()
    {
        $type  = $this->voterType();
        $state = service('voting')->ballotState($type, $this->voterId());

        if ($state['vote'] === null) {
            return redirect()->to($type->path('dashboard'))
                ->with('error', 'Belum ada suara aktif untuk akun ini.');
        }

        return view('voting/my_vote', [
            'title'     => 'Pilihan Saya',
            'type'      => $type,
            'voter'     => $this->voter(),
            'election'  => $state['election'],
            'vote'      => $state['vote'],
            'candidate' => CandidateTheme::present($state['candidate']),
        ]);
    }

    /**
     * candidate_id dari body JSON (ballot.js) atau form POST (fallback tanpa JS).
     * Field lain (mis. student_id) sengaja diabaikan.
     */
    private function candidateInput(): mixed
    {
        if (! $this->request->is('json')) {
            return $this->request->getPost('candidate_id');
        }

        try {
            return $this->request->getJsonVar('candidate_id');
        } catch (HTTPException) {
            return null;
        }
    }

    private function respondVote(VoteResult $result): RedirectResponse|ResponseInterface
    {
        $type = $this->voterType();

        if ($this->wantsJson()) {
            $payload = [
                'status'  => $result->status,
                'message' => $result->message(),
            ];

            if ($result->electionStatus !== null) {
                $payload['election_status'] = $result->electionStatus;
            }

            if ($result->isOk()) {
                $candidate           = $result->vote['candidate'];
                $payload['title']    = 'SUARA BERHASIL DISIMPAN';
                $payload['detail']   = 'Hak suara Anda telah dikunci.';
                $payload['redirect'] = site_url($type->path('my-vote'));
                $payload['vote']     = [
                    'number'   => sprintf('%02d', $candidate['nomor_urut']),
                    'ketua'    => $candidate['nama_ketua'],
                    'wakil'    => $candidate['nama_wakil'],
                    'voted_at' => format_waktu($result->vote['voted_at'], 'd MMMM yyyy, HH.mm.ss'),
                ];
            } elseif ($result->status === VoteResult::ALREADY_VOTED) {
                $payload['redirect'] = site_url($type->path('my-vote'));
            }

            return $this->response->setStatusCode($result->httpStatus())->setJSON($payload);
        }

        if ($result->isOk()) {
            return redirect()->to($type->path('my-vote'))
                ->with('success', 'SUARA BERHASIL DISIMPAN. Hak suara Anda telah dikunci.');
        }

        $target = $result->status === VoteResult::ALREADY_VOTED ? 'my-vote' : 'vote';

        return redirect()->to($type->path($target))->with('error', $result->message());
    }

    private function respondThrottled(): RedirectResponse|ResponseInterface
    {
        $message = 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.';

        if ($this->wantsJson()) {
            return $this->response
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) max(1, service('throttler')->getTokenTime()))
                ->setJSON(['status' => 'throttled', 'message' => $message]);
        }

        return redirect()->to($this->voterType()->path('vote'))->with('error', $message);
    }

    private function wantsJson(): bool
    {
        return $this->request->isAJAX()
            || $this->request->is('json')
            || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json');
    }
}
