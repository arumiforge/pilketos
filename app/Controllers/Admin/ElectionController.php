<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Models\ElectionModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use DateTimeZone;

/**
 * Kontrol pemilihan (MASTER section 16, Stage 3): nama, tahun, start_at, end_at.
 *
 * Status TIDAK diubah manual: selalu dihitung ElectionModel::resolveStatus()
 * dari jadwal terhadap jam SERVER (zona Asia/Jakarta). Menutup lebih awal =
 * end_at diganti waktu server sekarang; membuka sekarang = start_at diganti
 * waktu server sekarang. Jam browser tidak pernah dipakai. Setiap perubahan
 * dicatat di audit log.
 */
class ElectionController extends AdminController
{
    private const INPUT_FORMATS = ['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s'];

    /**
     * GET admin/election
     */
    public function index()
    {
        return $this->render('admin/election/index', [
            'title'  => 'Jadwal Pemilihan',
            'now'    => Time::now(),
            'errors' => (array) (session()->getFlashdata('errors') ?? []),
        ], 'election');
    }

    /**
     * POST admin/election
     * Buat election (bila belum ada) atau ubah jadwal election berjalan.
     */
    public function save(): RedirectResponse
    {
        $election = $this->election();
        $input    = [
            'nama'     => trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost('nama'))),
            'tahun'    => trim((string) $this->request->getPost('tahun')),
            'start_at' => self::parseInput($this->request->getPost('start_at')),
            'end_at'   => self::parseInput($this->request->getPost('end_at')),
        ];

        $rules = [
            'nama'     => ['label' => 'Nama pemilihan', 'rules' => 'required|max_length[150]'],
            'tahun'    => ['label' => 'Tahun', 'rules' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]'],
            'start_at' => ['label' => 'Waktu mulai', 'rules' => 'required|valid_date[Y-m-d H:i:s]'],
            'end_at'   => ['label' => 'Waktu selesai', 'rules' => 'required|valid_date[Y-m-d H:i:s]'],
        ];
        $messages = [
            'nama'     => ['required' => 'Nama pemilihan wajib diisi.', 'max_length' => 'Nama pemilihan maksimal 150 karakter.'],
            'tahun'    => [
                'required'              => 'Tahun wajib diisi.',
                'integer'               => 'Tahun berupa angka.',
                'greater_than_equal_to' => 'Tahun tidak valid.',
                'less_than_equal_to'    => 'Tahun tidak valid.',
            ],
            'start_at' => ['required' => 'Waktu mulai wajib diisi dengan format tanggal & jam yang valid.', 'valid_date' => 'Waktu mulai tidak valid.'],
            'end_at'   => ['required' => 'Waktu selesai wajib diisi dengan format tanggal & jam yang valid.', 'valid_date' => 'Waktu selesai tidak valid.'],
        ];

        if (! $this->validateData($input, $rules, $messages)) {
            return redirect()->to('admin/election')->withInput()->with('errors', $this->validator->getErrors());
        }

        if (strtotime($input['end_at']) <= strtotime($input['start_at'])) {
            return redirect()->to('admin/election')->withInput()->with('errors', ['end_at' => 'Waktu selesai harus setelah waktu mulai.']);
        }

        $data = ['tahun' => (int) $input['tahun']] + $input;

        return $election === null ? $this->create($data) : $this->update($election, $data, 'Jadwal pemilihan disimpan.');
    }

    /**
     * POST admin/election/close
     * Tutup sekarang: end_at = waktu server saat ini (hanya saat ONGOING).
     */
    public function close(): RedirectResponse
    {
        $election = $this->election();

        if (($election['status'] ?? null) !== ElectionModel::STATUS_ONGOING) {
            return redirect()->to('admin/election')->with('error', 'Pemilihan hanya dapat ditutup saat sedang berlangsung.');
        }

        $now   = Time::now();
        $start = Time::parse($election['start_at']);
        // CHECK end_at > start_at: bila dibuka pada detik yang sama, tutup 1 detik setelahnya.
        $end = $now->getTimestamp() > $start->getTimestamp() ? $now : $start->addSeconds(1);

        return $this->update($election, ['end_at' => $end->toDateTimeString()], 'Pemilihan ditutup. Pencoblosan tidak dapat dilakukan lagi.');
    }

    /**
     * POST admin/election/open
     * Buka sekarang: start_at = waktu server saat ini (hanya saat UPCOMING).
     */
    public function open(): RedirectResponse
    {
        $election = $this->election();

        if (($election['status'] ?? null) !== ElectionModel::STATUS_UPCOMING) {
            return redirect()->to('admin/election')->with('error', 'Pemilihan hanya dapat dibuka lebih awal bila statusnya belum dibuka.');
        }

        return $this->update($election, ['start_at' => Time::now()->toDateTimeString()], 'Pemilihan dibuka. Siswa dan guru dapat mencoblos sekarang.');
    }

    // ------------------------------------------------------------------

    private function create(array $data): RedirectResponse
    {
        $model          = model(ElectionModel::class);
        $data['status'] = $model->resolveStatus($data);

        $id = $model->insert($data);

        if ($id === false) {
            return redirect()->to('admin/election')->withInput()->with('errors', $model->errors());
        }

        $this->audit(AuditLogModel::ELECTION_CREATE, sprintf(
            'Pemilihan "%s" %d dibuat: mulai %s, selesai %s.',
            $data['nama'],
            $data['tahun'],
            $data['start_at'],
            $data['end_at'],
        ), ['election_id' => (int) $id]);

        return redirect()->to('admin/election')->with('success', 'Pemilihan dibuat. Status: ' . election_status_label($data['status']) . '.');
    }

    /**
     * @param array<string, int|string> $changes kolom yang diubah
     */
    private function update(array $election, array $changes, string $message): RedirectResponse
    {
        $model  = model(ElectionModel::class);
        $next   = array_merge($election, $changes);
        $status = $model->resolveStatus($next);
        $diff   = [];

        foreach (['nama' => 'nama', 'tahun' => 'tahun', 'start_at' => 'mulai', 'end_at' => 'selesai'] as $field => $label) {
            if (array_key_exists($field, $changes) && (string) $election[$field] !== (string) $changes[$field]) {
                $diff[] = sprintf('%s %s -> %s', $label, $election[$field], $changes[$field]);
            }
        }

        if ($diff === []) {
            return redirect()->to('admin/election')->with('success', 'Tidak ada perubahan jadwal.');
        }

        try {
            // UPDATE baris election menunggu suara yang sedang diproses
            // (VoteService memegang shared lock pada baris ini).
            $model->builder()->where('id', (int) $election['id'])->update(array_intersect_key($changes, array_flip(['nama', 'tahun', 'start_at', 'end_at'])) + [
                'status'     => $status,
                'updated_at' => Time::now()->toDateTimeString(),
            ]);
        } catch (DatabaseException $e) {
            log_message('error', 'Ubah jadwal gagal: {msg}', ['msg' => $e->getMessage()]);

            return redirect()->to('admin/election')->withInput()->with('errors', ['end_at' => 'Jadwal tidak dapat disimpan. Pastikan waktu selesai setelah waktu mulai.']);
        }

        $this->forgetElection();

        $statusBefore = $model->resolveStatus($election);
        $this->audit(AuditLogModel::SCHEDULE_UPDATE, sprintf(
            '%s. %s.',
            ucfirst(implode('; ', $diff)),
            $statusBefore === $status
                ? 'Status tetap: ' . election_status_label($status)
                : 'Status: ' . election_status_label($statusBefore) . ' -> ' . election_status_label($status),
        ), ['election_id' => (int) $election['id']]);

        return redirect()->to('admin/election')->with('success', $message . ' Status sekarang: ' . election_status_label($status) . '.');
    }

    /**
     * Input datetime-local ("2026-10-01T07:00") dibaca sebagai WIB (zona aplikasi).
     */
    private static function parseInput(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        $zone = new DateTimeZone(config('App')->appTimezone);

        foreach (self::INPUT_FORMATS as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, trim($value), $zone);

            if ($date !== false && $date->format($format) === trim($value)) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return '';
    }
}
