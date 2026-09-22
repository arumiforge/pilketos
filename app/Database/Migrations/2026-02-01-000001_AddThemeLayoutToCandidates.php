<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Stage 2: art direction halaman kandidat dapat dipilih per pasangan.
 *
 * theme_layout = split | poster | column. NULL berarti otomatis dari nomor urut
 * (CandidateTheme::layout()), sehingga database lama tetap tampil berbeda
 * per pasangan tanpa perlu diisi ulang. Stage 3 mengisinya dari form admin.
 */
class AddThemeLayoutToCandidates extends Migration
{
    public function up()
    {
        $this->forge->addColumn('candidates', [
            'theme_layout' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'theme_asset',
                'comment'    => 'Art direction halaman kandidat: split | poster | column (NULL = otomatis)',
            ],
        ]);

        $table = $this->db->escapeIdentifiers($this->db->prefixTable('candidates'));
        $this->db->query(
            "ALTER TABLE {$table} ADD CONSTRAINT `chk_candidates_theme_layout` "
            . "CHECK (`theme_layout` IS NULL OR `theme_layout` IN ('split', 'poster', 'column'))",
        );
    }

    public function down()
    {
        $table = $this->db->escapeIdentifiers($this->db->prefixTable('candidates'));
        $this->db->query("ALTER TABLE {$table} DROP CONSTRAINT `chk_candidates_theme_layout`");

        $this->forge->dropColumn('candidates', 'theme_layout');
    }
}
