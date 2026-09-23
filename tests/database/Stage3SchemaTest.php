<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\AuditLogModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Stage 3: migration 2026-03-01-000001_AddContextToAuditLogs.
 * (Rollback ikut teruji karena $refresh = true.)
 *
 * @internal
 */
final class Stage3SchemaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    public function testAuditLogStoresContext(): void
    {
        $id = model(AuditLogModel::class)->log(1, AuditLogModel::SCHEDULE_UPDATE, 'Uji', ['election_id' => 1, 'ip_address' => '192.168.1.20']);

        $this->seeInDatabase('audit_logs', ['id' => $id, 'election_id' => 1, 'ip_address' => '192.168.1.20', 'vote_unlock_log_id' => null]);
        $this->assertSame('Ubah jadwal pemilihan', AuditLogModel::label(AuditLogModel::SCHEDULE_UPDATE));
    }

    public function testAuditContextColumnsAreNullableForOlderRows(): void
    {
        $this->db->table('audit_logs')->insert(['admin_id' => 1, 'action' => 'LAMA', 'description' => 'baris lama', 'created_at' => Time::now()->toDateTimeString()]);

        $this->seeInDatabase('audit_logs', ['action' => 'LAMA', 'election_id' => null, 'ip_address' => null]);
    }

    public function testAuditContextForeignKeysAreEnforced(): void
    {
        $this->expectException(DatabaseException::class);

        $this->db->table('audit_logs')->insert(['admin_id' => 1, 'action' => 'X', 'election_id' => 999]);
    }

    public function testElectionReferencedByAuditCannotBeDeleted(): void
    {
        model(AuditLogModel::class)->log(1, AuditLogModel::ELECTION_CREATE, 'Uji', ['election_id' => 1]);

        $this->expectException(DatabaseException::class);
        $this->db->table('elections')->where('id', 1)->delete();
    }
}
