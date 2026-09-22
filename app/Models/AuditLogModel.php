<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table         = 'audit_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'admin_id',
        'action',
        'description',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Helper singkat untuk mencatat aktivitas admin.
     * Digunakan oleh fitur import/candidate/schedule/unlock pada stage berikutnya.
     */
    public function log(int $adminId, string $action, ?string $description = null): void
    {
        $this->insert([
            'admin_id'    => $adminId,
            'action'      => $action,
            'description' => $description,
        ]);
    }
}
