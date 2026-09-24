<?php

namespace App\Controllers\Admin;

use App\Services\AnalyticsService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Analitik lengkap & detail suara (Stage 3). Hanya admin.
 *
 * Stage 11: satu halaman dengan "pill section header". Setiap pill adalah
 * URL biasa (admin/analitik/<bagian>) yang dirender penuh oleh server, jadi
 * tetap berfungsi tanpa JavaScript dan bisa dibuka/ditandai langsung.
 * admin-analytics.js memuat bagian yang dipilih lewat fetch dengan header
 * X-Analytics-Pane: server lalu hanya mengirim isi bagian itu (tanpa layout)
 * dan skrip menggantinya di tempat + history.pushState.
 */
class AnalyticsController extends AdminController
{
    public const VOTES_PER_PAGE = 25;

    /**
     * Header request fetch dari admin-analytics.js.
     */
    public const PANE_HEADER = 'X-Analytics-Pane';

    /**
     * Bagian analitik: slug URL => label pill. "suara" = detail suara.
     * Stage 13: label "Total", "Pemilih", "Jenis Kelamin", "Detail Suara"
     * (slug URL tetap).
     */
    public const PANES = [
        'keseluruhan'   => 'Total',
        'jenis-pemilih' => 'Pemilih',
        'jenis-kelamin' => 'Jenis Kelamin',
        'kelas'         => 'Kelas',
        'rombel'        => 'Rombel',
        'suara'         => 'Detail Suara',
    ];

    /**
     * GET admin/analitik | admin/analitik/<bagian>
     * Keseluruhan, jenis pemilih, jenis kelamin siswa, kelas (7/8/9), rombel.
     */
    public function index(string $pane = 'keseluruhan')
    {
        if (! isset(self::PANES[$pane]) || $pane === 'suara') {
            throw PageNotFoundException::forPageNotFound('Bagian analitik tidak ditemukan.');
        }

        return $this->pane($pane, [
            'snapshot' => service('analytics')->snapshot($this->election()),
        ]);
    }

    /**
     * GET admin/analitik/suara
     * Detail suara: pencarian, filter jenis pemilih/kelas/jenis kelamin/
     * pasangan/status. Stage 11: siswa dan guru di tabel terpisah, masing-
     * masing dengan pagination sendiri (?page_siswa= / ?page_guru=).
     */
    public function votes()
    {
        $analytics  = service('analytics');
        $election   = $this->election();
        $snapshot   = $analytics->snapshot($election);
        $candidates = $snapshot['candidates'];
        $classes    = $analytics->classes();
        $filters    = AnalyticsService::detailFilters(
            (array) $this->request->getGet(),
            array_column($candidates, 'id'),
            $classes,
        );

        $groups = [];
        foreach (['student' => 'siswa', 'teacher' => 'guru'] as $type => $group) {
            $page   = $this->page($group);
            $result = $filters['type'] === '' || $filters['type'] === $type
                ? $analytics->detailVotes($election, ['type' => $type] + $filters, $page, self::VOTES_PER_PAGE)
                : ['rows' => [], 'total' => 0];

            $groups[$type] = [
                'rows'   => $result['rows'],
                'total'  => $result['total'],
                'offset' => ($page - 1) * self::VOTES_PER_PAGE,
                'pager'  => $this->pagerLinks($page, self::VOTES_PER_PAGE, $result['total'], $group),
            ];
        }

        return $this->pane('suara', [
            'candidates' => $candidates,
            'classes'    => $classes,
            'filters'    => $filters,
            'groups'     => $groups,
            'total'      => $groups['student']['total'] + $groups['teacher']['total'],
            'counted'    => $snapshot['summary']['all']['voted'],
        ]);
    }

    /**
     * Halaman penuh, atau isi bagian saja untuk fetch admin-analytics.js.
     */
    private function pane(string $pane, array $data): ResponseInterface|string
    {
        $data += [
            'title' => $pane === 'keseluruhan' ? 'Analitik' : self::PANES[$pane] . ' · Analitik',
            'pane'  => $pane,
            'panes' => self::PANES,
        ];

        if ($this->request->getHeaderLine(self::PANE_HEADER) !== '') {
            return $this->response
                ->setHeader('Vary', self::PANE_HEADER)
                ->setBody(view('admin/analytics/pane', $data + [
                    'election' => $this->election(),
                ]));
        }

        return $this->render('admin/analytics/index', $data, $pane === 'suara' ? 'votes' : 'analytics');
    }
}
