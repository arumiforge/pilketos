<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateElectionsTable extends Migration
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
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'tahun' => [
                'type'       => 'SMALLINT',
                'constraint' => 4,
                'unsigned'   => true,
            ],
            'start_at' => [
                'type' => 'DATETIME',
            ],
            'end_at' => [
                'type' => 'DATETIME',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['UPCOMING', 'ONGOING', 'FINISHED'],
                'default'    => 'UPCOMING',
                'comment'    => 'Cermin dari jadwal. Sumber kebenaran: start_at/end_at vs waktu server (ElectionModel::resolveStatus)',
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
        $this->forge->createTable('elections', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);

        // Jadwal harus valid: selesai setelah mulai.
        $table = $this->db->escapeIdentifiers($this->db->prefixTable('elections'));
        $this->db->query(
            "ALTER TABLE {$table} ADD CONSTRAINT `chk_elections_schedule` CHECK (`end_at` > `start_at`)",
        );
    }

    public function down()
    {
        $this->forge->dropTable('elections', true);
    }
}
