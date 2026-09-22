<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVoteUnlockLogsTable extends Migration
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
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'teacher_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'student_vote_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Baris student_votes yang di-UNLOCK (riwayat pilihan tetap di sana)',
            ],
            'teacher_vote_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Baris teacher_votes yang di-UNLOCK (riwayat pilihan tetap di sana)',
            ],
            'admin_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'reason' => [
                'type' => 'TEXT',
            ],
            'unlocked_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('election_id');
        $this->forge->addKey('student_id');
        $this->forge->addKey('teacher_id');
        $this->forge->addKey('student_vote_id');
        $this->forge->addKey('teacher_vote_id');
        $this->forge->addKey('admin_id');

        // Semua FK RESTRICT (tanpa CASCADE/SET NULL):
        // - log audit tidak boleh hilang karena data induk dihapus;
        // - MySQL 8 menolak CHECK constraint pada kolom yang FK-nya memakai
        //   referential action (error 3823), jadi student_id/teacher_id wajib RESTRICT.
        $this->forge->addForeignKey('election_id', 'elections', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('student_vote_id', 'student_votes', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('teacher_vote_id', 'teacher_votes', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('admin_id', 'admins', 'id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('vote_unlock_logs', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);

        // Tepat satu jenis pemilih per log: siswa ATAU guru, beserta baris vote-nya.
        $table = $this->db->escapeIdentifiers($this->db->prefixTable('vote_unlock_logs'));
        $this->db->query(
            "ALTER TABLE {$table} ADD CONSTRAINT `chk_vote_unlock_voter_type` CHECK (
                (`student_id` IS NOT NULL AND `student_vote_id` IS NOT NULL AND `teacher_id` IS NULL AND `teacher_vote_id` IS NULL)
                OR (`teacher_id` IS NOT NULL AND `teacher_vote_id` IS NOT NULL AND `student_id` IS NULL AND `student_vote_id` IS NULL)
            )",
        );
    }

    public function down()
    {
        $this->forge->dropTable('vote_unlock_logs', true);
    }
}
