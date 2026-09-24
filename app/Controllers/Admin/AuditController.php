<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;

/**
 * Audit log (MASTER section 19, Stage 3): waktu, admin, tindakan, pemilih,
 * jenis pemilih, election, alasan/keterangan. Hanya-baca: tidak ada route
 * untuk mengubah atau menghapus log.
 */
class AuditController extends AdminController
{
    public const PER_PAGE = 30;

    /**
     * GET admin/riwayat?action=&q=
     */
    public function index()
    {
        $action = (string) $this->request->getGet('action');
        $action = isset(AuditLogModel::LABELS[$action]) ? $action : '';
        $query  = mb_substr(trim((string) $this->request->getGet('q')), 0, 100);
        $page   = $this->page();

        $build = static function () use ($action, $query) {
            $builder = db_connect()->table('audit_logs g')
                ->join('admins a', 'a.id = g.admin_id')
                ->join('elections e', 'e.id = g.election_id', 'left')
                ->join('vote_unlock_logs l', 'l.id = g.vote_unlock_log_id', 'left')
                ->join('students s', 's.id = l.student_id', 'left')
                ->join('teachers t', 't.id = l.teacher_id', 'left');

            if ($action !== '') {
                $builder->where('g.action', $action);
            }

            if ($query !== '') {
                $builder->groupStart()
                    ->like('g.description', $query)
                    ->orLike('l.reason', $query)
                    ->orLike('s.name', $query)
                    ->orLike('t.name', $query)
                    ->orLike('s.nisn', $query)
                    ->orLike('t.nip', $query)
                    ->orLike('a.name', $query)
                    ->groupEnd();
            }

            return $builder;
        };

        $total = $build()->countAllResults();
        $rows  = $build()
            ->select(
                'g.id, g.action, g.description, g.created_at, g.ip_address, a.name AS admin_name, a.username AS admin_username, '
                . 'e.nama AS election_nama, e.tahun AS election_tahun, l.reason, l.student_id, l.teacher_id, '
                . 's.name AS student_name, s.nisn, t.name AS teacher_name, t.nip',
                false,
            )
            ->orderBy('g.id', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get()
            ->getResultArray();

        return $this->render('admin/audit/index', [
            'title'   => 'Audit Log',
            'rows'    => $rows,
            'total'   => $total,
            'action'  => $action,
            'query'   => $query,
            'pager'   => $this->pagerLinks($page, self::PER_PAGE, $total),
            'actions' => AuditLogModel::LABELS,
        ], 'audit');
    }
}
