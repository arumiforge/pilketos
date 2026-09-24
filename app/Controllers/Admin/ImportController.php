<?php

namespace App\Controllers\Admin;

use App\Libraries\CandidateAssets;
use App\Services\Import\ImportConflictException;
use App\Services\Import\ImportStore;
use App\Services\Import\VoterImporter;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * Import Excel pemilih (Stage 3):
 * Download template -> Upload -> Preview (validasi) -> Import -> Result.
 *
 * File .xlsx dibaca dari lokasi sementara unggahan PHP dan TIDAK disimpan;
 * yang disimpan hanya hasil baca tervalidasi (ImportStore, 1 jam, terikat
 * admin). Commit melakukan upsert dalam satu transaction dan dicatat di audit.
 */
abstract class ImportController extends AdminController
{
    public const PREVIEW_PER_PAGE = 100;

    /**
     * Filter tabel pratinjau: key => label.
     */
    public const PREVIEW_FILTERS = [
        'issues'                       => 'Perlu diperiksa',
        VoterImporter::ACTION_INVALID  => 'Bermasalah',
        VoterImporter::ACTION_CREATE   => 'Baru',
        VoterImporter::ACTION_UPDATE   => 'Diperbarui',
        VoterImporter::ACTION_SAME     => 'Tidak berubah',
        'all'                          => 'Semua baris',
    ];

    abstract protected function importer(): VoterImporter;

    /**
     * GET admin/{siswa|guru}/impor
     */
    public function index()
    {
        $importer = $this->importer();

        return $this->render('admin/import/index', [
            'title'    => 'Impor Data ' . $importer->type()->label(),
            'type'     => $importer->type(),
            'importer' => $importer,
            'maxBytes' => $this->maxBytes(),
        ], $importer->type()->voterTable());
    }

    /**
     * GET admin/{siswa|guru}/impor/templat
     */
    public function template()
    {
        $importer = $this->importer();

        return $this->response
            ->download($importer->templateFilename(), $importer->templateBinary())
            ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '')
            ->setHeader('Cache-Control', 'no-store, max-age=0');
    }

    /**
     * POST admin/{siswa|guru}/impor
     */
    public function upload(): RedirectResponse
    {
        $importer = $this->importer();
        $type     = $importer->type();
        $back     = redirect()->to($type->adminPath('impor'));
        $file     = $this->request->getFile('file');

        // Stage 4: impor dapat mengubah jumlah pemilih & rekap kelas/jenjang hasil akhir.
        if ($this->resultsLocked()) {
            return $back->with('error', self::RESULTS_LOCKED_MESSAGE);
        }

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $back->with('error', 'Pilih file Excel (.xlsx) terlebih dahulu.');
        }

        if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return $back->with('error', 'Ukuran file melebihi batas unggah server (' . CandidateAssets::formatBytes($this->maxBytes()) . ').');
        }

        if ($file->getError() !== UPLOAD_ERR_OK || ! $file->isValid()) {
            return $back->with('error', 'File gagal diunggah. Coba ulangi.');
        }

        if ((int) $file->getSize() > $this->maxBytes()) {
            return $back->with('error', 'Ukuran file maksimal ' . CandidateAssets::formatBytes($this->maxBytes()) . '.');
        }

        if (! in_array(strtolower($file->getClientExtension()), VoterImporter::EXTENSIONS, true)
            || ! in_array($file->getMimeType(), VoterImporter::MIME_TYPES, true)) {
            return $back->with('error', 'Format file harus Excel .xlsx (gunakan template yang disediakan).');
        }

        $name   = self::safeName($file->getClientName());
        $result = $importer->parse($file->getTempName());

        if ($result['errors'] !== []) {
            return $back->with('errors', array_merge(['File "' . $name . '" belum dapat diimpor:'], $result['errors']));
        }

        $token = service('importStore')->save($this->adminId(), $type, [
            'file_name' => $name,
            'summary'   => $result['summary'],
            'notices'   => $result['notices'],
            'rows'      => $result['rows'],
        ]);

        return redirect()->to($type->adminPath('impor/cek/' . $token));
    }

    /**
     * GET admin/{siswa|guru}/impor/cek/{token}
     */
    public function preview($token = null)
    {
        $importer = $this->importer();
        $type     = $importer->type();
        $payload  = $this->loadPayload($token);

        if ($payload === null) {
            return redirect()->to($type->adminPath('impor'))->with('error', 'Pratinjau tidak ditemukan atau sudah kedaluwarsa (1 jam). Unggah ulang file.');
        }

        $summary = $payload['summary'];
        $show    = (string) $this->request->getGet('show');

        if (! isset(self::PREVIEW_FILTERS[$show])) {
            $show = $summary['issues'] > 0 ? 'issues' : 'all';
        }

        $rows = array_values(array_filter($payload['rows'], static fn (array $row): bool => match ($show) {
            'all'    => true,
            'issues' => $row['errors'] !== [] || $row['warnings'] !== [],
            default  => $row['action'] === $show,
        }));

        $page  = $this->page();
        $total = count($rows);

        return $this->render('admin/import/preview', [
            'title'    => 'Pratinjau Impor ' . $type->label(),
            'type'     => $type,
            'importer' => $importer,
            'token'    => (string) $token,
            'fileName' => $payload['file_name'],
            'summary'  => $summary,
            'notices'  => $payload['notices'],
            'show'     => $show,
            'rows'     => array_slice($rows, ($page - 1) * self::PREVIEW_PER_PAGE, self::PREVIEW_PER_PAGE),
            'total'    => $total,
            'pager'    => $this->pagerLinks($page, self::PREVIEW_PER_PAGE, $total),
            'expires'  => (int) $payload['created_at'] + ImportStore::TTL,
        ], $type->voterTable());
    }

    /**
     * POST admin/{siswa|guru}/impor/simpan
     */
    public function commit(): RedirectResponse
    {
        $importer = $this->importer();
        $type     = $importer->type();
        $token    = (string) $this->request->getPost('token');

        if ($this->resultsLocked()) {
            return redirect()->to($type->adminPath('impor'))->with('error', self::RESULTS_LOCKED_MESSAGE);
        }

        // Stage 4: pratinjau diklaim atomik; POST kedua (klik ganda / diulang)
        // tidak dapat memproses impor yang sama dua kali.
        $store   = service('importStore');
        $payload = ImportStore::validToken($token) ? $store->claim($token, $this->adminId(), $type) : null;

        if ($payload === null) {
            return redirect()->to($type->adminPath('impor'))->with('error', 'Pratinjau tidak ditemukan, sudah kedaluwarsa, atau sudah diimpor. Periksa data, lalu unggah ulang file bila perlu.');
        }

        $summary = $payload['summary'];
        $preview = redirect()->to($type->adminPath('impor/cek/' . $token));

        if ($summary['importable'] === 0) {
            $store->release($token);

            return $preview->with('error', 'Tidak ada baris baru atau berubah untuk diimpor.');
        }

        if ($summary[VoterImporter::ACTION_INVALID] > 0 && $this->request->getPost('confirm_skip') !== '1') {
            $store->release($token);

            return $preview->with('error', 'Centang konfirmasi bahwa baris bermasalah akan dilewati, atau perbaiki file lalu unggah ulang.');
        }

        try {
            $counts = $importer->commit($payload['rows']);
        } catch (ImportConflictException) {
            $store->release($token);

            return $preview->with('error', 'Data berubah bersamaan saat impor (mis. admin lain sedang mengimpor). Tidak ada data yang disimpan; ulangi impor.');
        } catch (Throwable $e) {
            $store->release($token);

            throw $e;
        }

        $store->delete($token);

        $this->audit($type->auditAction('import'), sprintf(
            'Impor %s dari file "%s": %d baris dibaca, %d baru, %d diperbarui, %d tidak berubah, %d dilewati (bermasalah).',
            strtolower($type->label()),
            $payload['file_name'],
            $summary['rows'],
            $counts['created'],
            $counts['updated'],
            $counts['unchanged'],
            $counts['skipped'],
        ));

        return redirect()->to($type->adminPath('impor/selesai'))
            ->with('import_result', $counts + ['file_name' => $payload['file_name'], 'rows' => $summary['rows']]);
    }

    /**
     * GET admin/{siswa|guru}/impor/selesai
     */
    public function result()
    {
        $type   = $this->importer()->type();
        $result = session()->getFlashdata('import_result');

        if (! is_array($result)) {
            return redirect()->to($type->adminPath('impor'));
        }

        return $this->render('admin/import/result', [
            'title'  => 'Hasil Impor ' . $type->label(),
            'type'   => $type,
            'result' => $result,
        ], $type->voterTable());
    }

    private function loadPayload(mixed $token): ?array
    {
        return is_string($token) && ImportStore::validToken($token)
            ? service('importStore')->load($token, $this->adminId(), $this->importer()->type())
            : null;
    }

    private function maxBytes(): int
    {
        $server = CandidateAssets::iniBytes((string) ini_get('upload_max_filesize'));

        return $server > 0 ? min(VoterImporter::MAX_BYTES, $server) : VoterImporter::MAX_BYTES;
    }

    /**
     * Nama file asli hanya untuk ditampilkan/diaudit (selalu di-escape di view).
     */
    private static function safeName(string $name): string
    {
        $name = (string) preg_replace('/[\x00-\x1F\x7F]+/u', '', basename(str_replace('\\', '/', $name)));

        return mb_substr($name === '' ? 'tanpa-nama.xlsx' : $name, 0, 120);
    }
}
