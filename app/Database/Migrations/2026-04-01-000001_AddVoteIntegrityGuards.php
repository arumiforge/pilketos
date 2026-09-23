<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * Stage 4: penjaga integritas suara & audit di tingkat DATABASE.
 *
 * Aplikasi (VoteService, UnlockService) sudah hanya melakukan transisi yang
 * sah; migration ini memastikan database sendiri menolak transisi lain,
 * termasuk dari query manual (HeidiSQL/phpMyAdmin) atau bug di masa depan.
 *
 * State hak suara per pemilih per election:
 *
 *   NO_ACTIVE_VOTE --vote--> LOCKED --unlock admin--> NO_ACTIVE_VOTE (baris lama UNLOCKED)
 *                                                    --vote lagi--> LOCKED (baris BARU)
 *
 * 1. CHECK chk_<tabel>_state: LOCKED selalu tanpa unlocked_at, UNLOCKED selalu
 *    dengan unlocked_at (waktu unlock tercatat).
 * 2. Trigger BEFORE UPDATE pada student_votes / teacher_votes: satu-satunya
 *    perubahan yang boleh adalah LOCKED -> UNLOCKED (+ unlocked_at, updated_at).
 *    Pilihan kandidat, pemilih, election, waktu memilih, dan perangkat tidak
 *    dapat diubah; baris UNLOCKED bersifat final (tidak bisa dikunci ulang).
 * 3. Trigger BEFORE DELETE: baris suara, log unlock, dan audit log tidak dapat
 *    dihapus; log unlock dan audit log juga tidak dapat diubah (append-only).
 *
 * Satu suara aktif per pemilih tetap dijamin unique key uq_*_votes_active
 * (Stage 1). Pesan trigger (SQLSTATE 45000, kode 1644) sengaja pendek karena
 * MESSAGE_TEXT dibatasi 128 karakter.
 *
 * Hak akses: membuat trigger butuh privilege TRIGGER; pada MySQL 8 dengan
 * binary log aktif (bawaan MySQL 8), user non-SUPER juga butuh
 * log_bin_trust_function_creators = 1 (error 1419). User root Laragon sudah
 * memenuhi keduanya.
 *
 * up() aman diulang: bila sempat gagal di tengah (mis. error 1419 setelah
 * CHECK terpasang), perbaiki hak aksesnya lalu jalankan lagi php spark migrate.
 */
class AddVoteIntegrityGuards extends Migration
{
    private const VOTE_TABLES = [
        'student_votes' => 'student_id',
        'teacher_votes' => 'teacher_id',
    ];

    private const APPEND_ONLY = ['vote_unlock_logs', 'audit_logs'];

    /**
     * MySQL: "You do not have the SUPER privilege and binary logging is enabled".
     */
    private const ER_BINLOG_CREATE_ROUTINE_NEED_SUPER = 1419;

    public function up()
    {
        foreach (array_keys(self::VOTE_TABLES) as $table) {
            $this->assertConsistentStates($table);
        }

        foreach (self::VOTE_TABLES as $table => $voterKey) {
            $name = $this->table($table);

            if (! $this->constraintExists($table, "chk_{$table}_state")) {
                $this->db->query(
                    "ALTER TABLE {$name} ADD CONSTRAINT `chk_{$table}_state` CHECK ("
                    . "(`status` = 'LOCKED' AND `unlocked_at` IS NULL) OR (`status` = 'UNLOCKED' AND `unlocked_at` IS NOT NULL))",
                );
            }

            $this->createTrigger(
                "trg_{$table}_guard_update",
                "BEFORE UPDATE ON {$name} FOR EACH ROW
                BEGIN
                    IF OLD.`status` = 'UNLOCKED' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Riwayat suara UNLOCKED bersifat final dan tidak boleh diubah.';
                    END IF;
                    IF NEW.`id` <> OLD.`id`
                        OR NEW.`election_id` <> OLD.`election_id`
                        OR NEW.`{$voterKey}` <> OLD.`{$voterKey}`
                        OR NEW.`candidate_id` <> OLD.`candidate_id`
                        OR NEW.`voted_at` <> OLD.`voted_at`
                        OR NOT (NEW.`device_info` <=> OLD.`device_info`)
                        OR NOT (NEW.`browser_info` <=> OLD.`browser_info`)
                        OR NOT (NEW.`created_at` <=> OLD.`created_at`) THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Isi suara tidak boleh diubah. Satu-satunya perubahan sah: LOCKED ke UNLOCKED.';
                    END IF;
                END",
            );

            $this->createTrigger(
                "trg_{$table}_guard_delete",
                "BEFORE DELETE ON {$name} FOR EACH ROW
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Baris suara tidak boleh dihapus: riwayat wajib dapat diaudit.'",
            );
        }

        foreach (self::APPEND_ONLY as $table) {
            $name = $this->table($table);

            $this->createTrigger(
                "trg_{$table}_guard_update",
                "BEFORE UPDATE ON {$name} FOR EACH ROW
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Log bersifat append-only dan tidak boleh diubah.'",
            );

            $this->createTrigger(
                "trg_{$table}_guard_delete",
                "BEFORE DELETE ON {$name} FOR EACH ROW
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Log bersifat append-only dan tidak boleh dihapus.'",
            );
        }
    }

    public function down()
    {
        foreach (array_merge(array_keys(self::VOTE_TABLES), self::APPEND_ONLY) as $table) {
            $this->db->query("DROP TRIGGER IF EXISTS `trg_{$table}_guard_update`");
            $this->db->query("DROP TRIGGER IF EXISTS `trg_{$table}_guard_delete`");
        }

        foreach (array_keys(self::VOTE_TABLES) as $table) {
            if ($this->constraintExists($table, "chk_{$table}_state")) {
                $this->db->query("ALTER TABLE {$this->table($table)} DROP CONSTRAINT `chk_{$table}_state`");
            }
        }
    }

    private function table(string $table): string
    {
        return $this->db->escapeIdentifiers($this->db->prefixTable($table));
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS n FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$this->db->prefixTable($table), $constraint],
        )->getRowArray();

        return (int) ($row['n'] ?? 0) > 0;
    }

    /**
     * Trigger dibuat ulang (DROP IF EXISTS lalu CREATE) sehingga up() aman
     * diulang. Error 1419 MySQL diterjemahkan menjadi langkah perbaikan.
     */
    private function createTrigger(string $trigger, string $definition): void
    {
        $this->db->query("DROP TRIGGER IF EXISTS `{$trigger}`");

        try {
            $this->db->query("CREATE TRIGGER `{$trigger}` {$definition}");
        } catch (DatabaseException $e) {
            if ((int) $e->getCode() !== self::ER_BINLOG_CREATE_ROUTINE_NEED_SUPER) {
                throw $e;
            }

            throw new RuntimeException(
                'MySQL menolak membuat trigger ' . $trigger . ' (error 1419: binary log aktif dan user database bukan SUPER). '
                . 'Jalankan migration dengan user root, atau minta pengelola database menjalankan '
                . '"SET GLOBAL log_bin_trust_function_creators = 1" (atau set di my.ini), lalu ulangi: php spark migrate',
                self::ER_BINLOG_CREATE_ROUTINE_NEED_SUPER,
                $e,
            );
        }
    }

    /**
     * Baris lama yang melanggar aturan state tidak diperbaiki diam-diam:
     * migration berhenti dengan pesan jelas agar panitia memeriksanya.
     */
    private function assertConsistentStates(string $table): void
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS n, GROUP_CONCAT(`id` ORDER BY `id` SEPARATOR ', ') AS ids FROM {$this->table($table)}
             WHERE (`status` = 'LOCKED' AND `unlocked_at` IS NOT NULL) OR (`status` = 'UNLOCKED' AND `unlocked_at` IS NULL)",
        )->getRowArray();

        if ((int) ($row['n'] ?? 0) > 0) {
            throw new RuntimeException(sprintf(
                'Tabel %s memiliki %d baris dengan status dan unlocked_at yang tidak konsisten (id: %s). Periksa baris tersebut sebelum menjalankan migration ini.',
                $table,
                (int) $row['n'],
                (string) $row['ids'],
            ));
        }
    }
}
