<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeacherVotesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'election_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'teacher_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'candidate_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['LOCKED', 'UNLOCKED'],
                'default'    => 'LOCKED',
                'comment'    => 'LOCKED = suara aktif. UNLOCKED = dibuka admin, baris tetap disimpan sebagai riwayat',
            ],
            // Bernilai 1 hanya untuk suara LOCKED, NULL untuk riwayat UNLOCKED.
            // Unique key di bawah memakai kolom ini sehingga database sendiri
            // menjamin maksimal 1 suara aktif per guru per election.
            'active_lock' => "`active_lock` TINYINT(1) GENERATED ALWAYS AS (CASE WHEN `status` = 'LOCKED' THEN 1 ELSE NULL END) STORED",
            'voted_at' => [
                'type' => 'DATETIME',
            ],
            'unlocked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'device_info' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'browser_info' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['election_id', 'teacher_id', 'active_lock'], 'uq_teacher_votes_active');
        $this->forge->addKey('teacher_id');
        $this->forge->addKey(['election_id', 'status', 'candidate_id'], false, false, 'idx_teacher_votes_count');

        // RESTRICT: guru/kandidat/election yang sudah memiliki suara tidak dapat dihapus.
        $this->forge->addForeignKey('election_id', 'elections', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('candidate_id', 'candidates', 'id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('teacher_votes', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('teacher_votes', true);
    }
}
