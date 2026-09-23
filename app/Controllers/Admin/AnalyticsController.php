<?php

namespace App\Controllers\Admin;

use App\Services\AnalyticsService;

/**
 * Analitik lengkap & detail suara (Stage 3). Hanya admin.
 */
class AnalyticsController extends AdminController
{
    public const VOTES_PER_PAGE = 25;

    /**
     * GET admin/analytics
     * Keseluruhan, jenis pemilih, jenis kelamin siswa, jenjang, dan kelas.
     */
    public function index()
    {
        return $this->render('admin/analytics/index', [
            'title'    => 'Analitik',
            'snapshot' => service('analytics')->snapshot($this->election()),
        ], 'analytics');
    }

    /**
     * GET admin/analytics/votes
     * Detail suara: pencarian, filter jenis pemilih/kelas/jenis kelamin/
     * pasangan/status, dan pagination.
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
        $page   = $this->page();
        $result = $analytics->detailVotes($election, $filters, $page, self::VOTES_PER_PAGE);

        return $this->render('admin/analytics/votes', [
            'title'      => 'Detail Suara',
            'candidates' => $candidates,
            'classes'    => $classes,
            'filters'    => $filters,
            'rows'       => $result['rows'],
            'total'      => $result['total'],
            'offset'     => ($page - 1) * self::VOTES_PER_PAGE,
            'counted'    => $snapshot['summary']['all']['voted'],
            'pager'      => $this->pagerLinks($page, self::VOTES_PER_PAGE, $result['total']),
        ], 'votes');
    }
}
