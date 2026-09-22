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
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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
            'admin_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'previous_candidate_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Arsip pilihan sebelumnya agar riwayat tetap dapat diaudit',
            ],
            'previous_voted_at' => [
                'type' => 'DATETIME',
                'null' => true,
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

        $this->forge->addForeignKey('election_id', 'elections', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('admin_id', 'admins', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('previous_candidate_id', 'candidates', 'id', 'CASCADE', 'SET NULL');

        $this->forge->createTable('vote_unlock_logs', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);

        // Pastikan tepat salah satu dari student_id / teacher_id yang terisi.
        $this->db->query(
            'ALTER TABLE `vote_unlock_logs` ADD CONSTRAINT `chk_vote_unlock_voter_type` CHECK (
                (`student_id` IS NOT NULL AND `teacher_id` IS NULL)
                OR (`student_id` IS NULL AND `teacher_id` IS NOT NULL)
            )'
        );
    }

    public function down()
    {
        $this->forge->dropTable('vote_unlock_logs', true);
    }
}
