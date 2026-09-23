<?php

namespace App\Controllers\Admin;

use App\Libraries\CandidateAssetException;
use App\Libraries\CandidateAssets;
use App\Libraries\CandidateTheme;
use App\Models\AuditLogModel;
use App\Models\CandidateModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Kelola pasangan calon + tema visual (Stage 3).
 *
 * Tema yang diisi di sini dipakai langsung oleh halaman kandidat Stage 2
 * lewat App\Libraries\CandidateTheme: theme_accent (#RRGGBB), theme_layout
 * (split|poster|column|otomatis), theme_background, foto_ketua, foto_wakil,
 * dan theme_asset JSON {hero, texture, artwork, poster}. Kolom hanya berisi
 * nama file acak di public/uploads/candidates (CandidateAssets).
 *
 * Kandidat yang sudah punya baris suara tidak dapat dihapus (FK RESTRICT):
 * gunakan status nonaktif.
 */
class CandidateController extends AdminController
{
    /**
     * Label field untuk pesan audit.
     */
    private const FIELD_LABELS = [
        'nomor_urut'   => 'nomor urut',
        'nama_ketua'   => 'nama ketua',
        'nama_wakil'   => 'nama wakil',
        'visi'         => 'visi',
        'misi'         => 'misi',
        'theme_name'   => 'nama tema',
        'theme_accent' => 'warna aksen',
        'theme_layout' => 'layout',
        'status_aktif' => 'status aktif',
    ];

    /**
     * GET admin/candidates
     */
    public function index()
    {
        $model    = model(CandidateModel::class);
        $snapshot = service('analytics')->snapshot($this->election());
        $counted  = array_column($snapshot['candidates'], 'votes', 'id');
        $rows     = [];

        foreach ($model->getAllOrdered() as $candidate) {
            $id     = (int) $candidate['id'];
            $rows[] = [
                'raw'       => $candidate,
                'theme'     => CandidateTheme::present($candidate),
                'votes'     => $counted[$id] ?? 0,
                'vote_rows' => $model->voteRowCount($id),
                'assets'    => $this->assetFiles($candidate),
            ];
        }

        return $this->render('admin/candidates/index', [
            'title'       => 'Pasangan Calon',
            'rows'        => $rows,
            'activeCount' => count(array_filter($rows, static fn (array $r): bool => (int) $r['raw']['status_aktif'] === 1)),
        ], 'candidates');
    }

    /**
     * GET admin/candidates/new
     */
    public function new()
    {
        $max = (int) (model(CandidateModel::class)->builder()->selectMax('nomor_urut', 'max')->get()->getRowArray()['max'] ?? 0);

        return $this->form(null, ['nomor_urut' => min(99, $max + 1), 'status_aktif' => 1]);
    }

    /**
     * POST admin/candidates
     */
    public function create()
    {
        return $this->save(null);
    }

    /**
     * GET admin/candidates/{id}/edit
     */
    public function edit($id = null)
    {
        $candidate = $this->findOr404($id);

        return $this->form($candidate, $candidate);
    }

    /**
     * POST admin/candidates/{id}
     */
    public function update($id = null)
    {
        return $this->save($this->findOr404($id));
    }

    /**
     * POST admin/candidates/{id}/delete
     */
    public function delete($id = null)
    {
        $candidate = $this->findOr404($id);
        $model     = model(CandidateModel::class);
        $voteRows  = $model->voteRowCount((int) $candidate['id']);
        $label     = sprintf('Pasangan %02d (%s & %s)', $candidate['nomor_urut'], $candidate['nama_ketua'], $candidate['nama_wakil']);

        if ($voteRows > 0) {
            return redirect()->to('admin/candidates')->with('error', sprintf(
                '%s sudah memiliki %d baris suara (termasuk riwayat) sehingga tidak dapat dihapus. Nonaktifkan pasangan ini bila perlu.',
                $label,
                $voteRows,
            ));
        }

        try {
            $model->delete((int) $candidate['id']);
        } catch (DatabaseException) {
            return redirect()->to('admin/candidates')->with('error', $label . ' tidak dapat dihapus karena sudah dipakai data suara.');
        }

        $assets = service('candidateAssets');
        foreach ($this->assetFiles($candidate) as $file) {
            $assets->delete($file);
        }

        $this->audit(AuditLogModel::CANDIDATE_DELETE, $label . ' dihapus beserta file tema.');

        return redirect()->to('admin/candidates')->with('success', $label . ' dihapus.');
    }

    /**
     * GET admin/candidates/{id}/preview
     * Pratinjau halaman kandidat persis seperti yang dilihat pemilih (tanpa surat suara).
     */
    public function preview($id = null)
    {
        $candidate = $this->findOr404($id);
        $others    = array_filter(
            model(CandidateModel::class)->getAllOrdered(),
            static fn (array $c): bool => (int) $c['status_aktif'] === 1 || (int) $c['id'] === (int) $candidate['id'],
        );

        return view('admin/candidates/preview', [
            'title'     => sprintf('Pratinjau Pasangan %02d', $candidate['nomor_urut']),
            'candidate' => CandidateTheme::present($candidate),
            'active'    => (int) $candidate['status_aktif'] === 1,
            'switcher'  => CandidateTheme::presentAll(array_values($others)),
            'bodyClass' => 'is-admin-preview',
        ]);
    }

    // ------------------------------------------------------------------

    private function form(?array $candidate, array $values)
    {
        $old = session()->getFlashdata('_ci_old_input');

        if (is_array($old['post'] ?? null)) {
            $values = array_merge($values, $old['post']);
        }

        return $this->render('admin/candidates/form', [
            'title'     => $candidate === null ? 'Tambah Pasangan' : sprintf('Ubah Pasangan %02d', $candidate['nomor_urut']),
            'candidate' => $candidate,
            'values'    => $values,
            'theme'     => $candidate === null ? null : CandidateTheme::present($candidate),
            'files'     => $candidate === null ? [] : $this->assetFiles($candidate),
            'errors'    => (array) (session()->getFlashdata('errors') ?? []),
            'postMax'   => CandidateAssets::iniBytes((string) ini_get('post_max_size')),
        ], 'candidates');
    }

    private function save(?array $existing): RedirectResponse
    {
        $model  = model(CandidateModel::class);
        $input  = $this->textInput();
        $data   = $input + ($existing === null ? [] : ['id' => (int) $existing['id']]);
        $back   = $existing === null ? 'admin/candidates/new' : 'admin/candidates/' . $existing['id'] . '/edit';

        if (! $model->validate($data)) {
            return redirect()->to($back)->withInput()->with('errors', $model->errors());
        }

        // Semua file divalidasi & diproses dulu; bila satu gagal, yang lain dibatalkan.
        $assets = service('candidateAssets');
        $stored = [];
        $errors = [];

        foreach (array_keys(CandidateAssets::SLOTS) as $slot) {
            $file = $this->request->getFile('asset_' . $slot);

            if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            try {
                $stored[$slot] = $assets->store($file, $slot, (int) $input['nomor_urut']);
            } catch (CandidateAssetException $e) {
                $errors['asset_' . $slot] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            array_map($assets->delete(...), $stored);

            return redirect()->to($back)->withInput()->with('errors', $errors);
        }

        [$files, $obsolete, $fileChanges] = $this->mergeFiles($existing, $stored, (array) $this->request->getPost('remove'));
        $data = array_merge($data, $files);

        try {
            $saved = $existing === null ? $model->insert($data) !== false : $model->update((int) $existing['id'], $data);
        } catch (DatabaseException $e) {
            log_message('error', 'Simpan kandidat gagal: {msg}', ['msg' => $e->getMessage()]);
            $saved = false;
        }

        if (! $saved) {
            array_map($assets->delete(...), $stored);

            return redirect()->to($back)->withInput()->with('errors', $model->errors() ?: ['Data pasangan gagal disimpan. Periksa nomor urut lalu coba lagi.']);
        }

        array_map($assets->delete(...), $obsolete);

        $label = sprintf('Pasangan %02d (%s & %s)', $input['nomor_urut'], $input['nama_ketua'], $input['nama_wakil']);

        if ($existing === null) {
            $this->audit(AuditLogModel::CANDIDATE_CREATE, $label . ' ditambahkan' . ($fileChanges === [] ? '.' : '; file: ' . implode(', ', $fileChanges) . '.'));

            return redirect()->to('admin/candidates')->with('success', $label . ' ditambahkan.');
        }

        $changes = array_merge($this->changedFields($existing, $input), $fileChanges);

        if ($changes !== []) {
            $this->audit(AuditLogModel::CANDIDATE_UPDATE, $label . ' diubah: ' . implode(', ', $changes) . '.');
        }

        return redirect()->to('admin/candidates')->with('success', $label . ($changes === [] ? ' tidak berubah.' : ' disimpan.'));
    }

    /**
     * Field teks dari form, dirapikan. Field lain di body diabaikan (anti mass assignment).
     *
     * @return array<string, int|string|null>
     */
    private function textInput(): array
    {
        $post = fn (string $key): string => trim(str_replace(["\r\n", "\r"], "\n", (string) $this->request->getPost($key)));

        $accent = strtoupper($post('theme_accent'));
        if (preg_match('/^[0-9A-F]{6}$/', $accent) === 1) {
            $accent = '#' . $accent;
        }

        $number = $post('nomor_urut');
        $layout = $post('theme_layout');

        return [
            'nomor_urut'   => ctype_digit($number) ? (int) $number : $number,
            'nama_ketua'   => (string) preg_replace('/\s+/u', ' ', $post('nama_ketua')),
            'nama_wakil'   => (string) preg_replace('/\s+/u', ' ', $post('nama_wakil')),
            'visi'         => $post('visi') === '' ? null : $post('visi'),
            'misi'         => $post('misi') === '' ? null : $post('misi'),
            'theme_name'   => $post('theme_name') === '' ? null : $post('theme_name'),
            'theme_accent' => $accent === '' ? null : $accent,
            'theme_layout' => $layout === '' ? null : $layout,
            'status_aktif' => $post('status_aktif') === '1' ? 1 : 0,
        ];
    }

    /**
     * Gabungkan file baru / dihapus dengan file lama.
     *
     * @param array<string, string> $stored  slot => nama file baru
     * @param list<mixed>           $remove  slot yang dicentang "hapus"
     *
     * @return array{0: array<string, mixed>, 1: list<string>, 2: list<string>} [kolom, file lama untuk dihapus, ringkasan audit]
     */
    private function mergeFiles(?array $existing, array $stored, array $remove): array
    {
        $current  = $existing === null ? [] : $this->assetFiles($existing);
        $json     = is_array($existing['theme_asset'] ?? null) ? $existing['theme_asset'] : [];
        $columns  = [];
        $obsolete = [];
        $changes  = [];

        foreach (CandidateAssets::SLOTS as $slot => $config) {
            $old = $current[$slot] ?? null;
            $new = $old;

            if (isset($stored[$slot])) {
                $new       = $stored[$slot];
                $changes[] = strtolower($config['label']) . ($old === null ? ' diunggah' : ' diganti');
            } elseif ($old !== null && in_array($slot, $remove, true)) {
                $new       = null;
                $changes[] = strtolower($config['label']) . ' dihapus';
            }

            if ($old !== null && $new !== $old) {
                $obsolete[] = $old;
            }

            if ($config['column'] !== null) {
                $columns[$config['column']] = $new;
            } elseif ($new === null) {
                unset($json[$config['asset']]);
            } else {
                $json[$config['asset']] = $new;
            }
        }

        // Hanya kunci yang dikenal CandidateTheme, dengan urutan tetap.
        $assets = [];
        foreach (CandidateTheme::ASSET_KEYS as $key) {
            if (isset($json[$key]) && is_string($json[$key]) && $json[$key] !== '') {
                $assets[$key] = $json[$key];
            }
        }
        $columns['theme_asset'] = $assets === [] ? null : $assets;

        return [$columns, $obsolete, $changes];
    }

    /**
     * File asset per slot (nama file) dari baris kandidat.
     *
     * @return array<string, string>
     */
    private function assetFiles(array $candidate): array
    {
        $json  = is_array($candidate['theme_asset'] ?? null) ? $candidate['theme_asset'] : [];
        $files = [];

        foreach (CandidateAssets::SLOTS as $slot => $config) {
            $value = $config['column'] !== null ? ($candidate[$config['column']] ?? null) : ($json[$config['asset']] ?? null);

            if (is_string($value) && $value !== '') {
                $files[$slot] = $value;
            }
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function changedFields(array $existing, array $input): array
    {
        $changes = [];

        foreach (self::FIELD_LABELS as $field => $label) {
            $old = $existing[$field] === null ? null : (string) $existing[$field];
            $new = $input[$field] === null ? null : (string) $input[$field];

            if ($old !== $new) {
                $changes[] = $label;
            }
        }

        return $changes;
    }

    private function findOr404(mixed $id): array
    {
        $candidate = is_string($id) && ctype_digit($id) ? model(CandidateModel::class)->find((int) $id) : null;

        if ($candidate === null) {
            throw PageNotFoundException::forPageNotFound('Pasangan calon tidak ditemukan.');
        }

        return $candidate;
    }
}
