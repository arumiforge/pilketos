<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Stage 3: konteks audit log agar halaman Audit dapat menampilkan
 * pemilih, jenis pemilih, election, dan alasan tanpa menebak dari teks.
 *
 * - election_id        : election yang terkait tindakan (unlock, ubah jadwal);
 * - vote_unlock_log_id : baris vote_unlock_logs untuk tindakan UNLOCK_VOTE
 *                        (pemilih, baris suara, dan alasan ada di sana);
 * - ip_address         : alamat IP admin saat tindakan dilakukan.
 *
 * FK tetap RESTRICT (kebijakan Stage 1): log audit tidak pernah ikut
 * terhapus bersama data induknya. Semua kolom NULL sehingga baris audit
 * lama (Stage 1/2) tetap valid.
 */
class AddContextToAuditLogs extends Migration
{
    public function up()
    {
        $this->forge->addColumn('audit_logs', [
            'election_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'description',
            ],
            'vote_unlock_log_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'election_id',
                'comment'    => 'Diisi untuk UNLOCK_VOTE: pemilih, suara, dan alasan ada di vote_unlock_logs',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
                'after'      => 'vote_unlock_log_id',
            ],
        ]);

        $table     = $this->db->escapeIdentifiers($this->db->prefixTable('audit_logs'));
        $elections = $this->db->escapeIdentifiers($this->db->prefixTable('elections'));
        $unlocks   = $this->db->escapeIdentifiers($this->db->prefixTable('vote_unlock_logs'));

        $this->db->query(
            "ALTER TABLE {$table}"
            . " ADD CONSTRAINT `audit_logs_election_id_foreign` FOREIGN KEY (`election_id`) REFERENCES {$elections} (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,"
            . " ADD CONSTRAINT `audit_logs_vote_unlock_log_id_foreign` FOREIGN KEY (`vote_unlock_log_id`) REFERENCES {$unlocks} (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT",
        );
    }

    public function down()
    {
        $this->forge->dropForeignKey('audit_logs', 'audit_logs_election_id_foreign');
        $this->forge->dropForeignKey('audit_logs', 'audit_logs_vote_unlock_log_id_foreign');
        $this->forge->dropColumn('audit_logs', ['election_id', 'vote_unlock_log_id', 'ip_address']);
    }
}
