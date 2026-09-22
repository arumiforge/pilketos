<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCandidatesTable extends Migration
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
            'nomor_urut' => [
                'type'       => 'TINYINT',
                'constraint' => 2,
                'unsigned'   => true,
            ],
            'nama_ketua' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'nama_wakil' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'foto_ketua' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'foto_wakil' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'visi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'misi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'theme_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'theme_background' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Path asset background/artwork pasangan',
            ],
            'theme_accent' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'Warna solid non-gradient, format hex',
            ],
            'theme_asset' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'JSON berisi asset tambahan: texture, campaign artwork, poster, dll',
            ],
            'status_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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
        $this->forge->addUniqueKey('nomor_urut');
        $this->forge->createTable('candidates', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('candidates', true);
    }
}
