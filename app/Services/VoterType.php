<?php

namespace App\Services;

use App\Models\AuditLogModel;
use App\Models\StudentModel;
use App\Models\StudentVoteModel;
use App\Models\TeacherModel;
use App\Models\TeacherVoteModel;

/**
 * Jenis pemilih (MASTER section 13, desain A: tabel suara terpisah).
 *
 * Setiap jenis memetakan ke tabel identitas, tabel suara, kolom FK, dan key
 * sesi miliknya sendiri. Controller menetapkan jenis ini secara tetap
 * (Student\VoteController = Student, Teacher\VoteController = Teacher);
 * nilainya tidak pernah dibaca dari input request, sehingga siswa tidak
 * mungkin menulis ke teacher_votes dan sebaliknya.
 *
 * Stage 3 menambahkan pemetaan untuk panel admin (kolom identitas, path
 * admin, kolom vote_unlock_logs, aksi audit). Di panel admin jenis pemilih
 * dari URL selalu divalidasi lewat VoterType::tryFrom().
 */
enum VoterType: string
{
    case Student = 'student';
    case Teacher = 'teacher';

    /**
     * Tabel identitas pemilih.
     */
    public function voterTable(): string
    {
        return match ($this) {
            self::Student => 'students',
            self::Teacher => 'teachers',
        };
    }

    /**
     * Tabel suara milik jenis pemilih ini.
     */
    public function voteTable(): string
    {
        return match ($this) {
            self::Student => 'student_votes',
            self::Teacher => 'teacher_votes',
        };
    }

    /**
     * Kolom FK pemilih di tabel suara, sekaligus key id pada sesi
     * (BaseController::startAuthSession() menyimpan "<role>_id").
     */
    public function voterKey(): string
    {
        return $this->value . '_id';
    }

    /**
     * Kolom baris suara di vote_unlock_logs (student_vote_id / teacher_vote_id).
     */
    public function unlockVoteKey(): string
    {
        return $this->value . '_vote_id';
    }

    /**
     * Nama unique key "satu suara aktif" (migration Stage 1).
     */
    public function activeVoteKey(): string
    {
        return 'uq_' . $this->voteTable() . '_active';
    }

    /**
     * Kolom identitas login (NISN siswa / NIP guru).
     */
    public function identifierColumn(): string
    {
        return match ($this) {
            self::Student => 'nisn',
            self::Teacher => 'nip',
        };
    }

    public function identifierLabel(): string
    {
        return match ($this) {
            self::Student => 'NISN',
            self::Teacher => 'NIP',
        };
    }

    public function voterModel(): StudentModel|TeacherModel
    {
        return match ($this) {
            self::Student => model(StudentModel::class),
            self::Teacher => model(TeacherModel::class),
        };
    }

    public function voteModel(): StudentVoteModel|TeacherVoteModel
    {
        return match ($this) {
            self::Student => model(StudentVoteModel::class),
            self::Teacher => model(TeacherVoteModel::class),
        };
    }

    /**
     * Label untuk UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Student => 'Siswa',
            self::Teacher => 'Guru',
        };
    }

    /**
     * Path route relatif untuk jenis pemilih ini, contoh path('vote') = "student/vote".
     */
    public function path(string $suffix = ''): string
    {
        return $suffix === '' ? $this->value : $this->value . '/' . ltrim($suffix, '/');
    }

    /**
     * Path panel admin, contoh adminPath('import') = "admin/students/import".
     */
    public function adminPath(string $suffix = ''): string
    {
        $base = 'admin/' . $this->voterTable();

        return $suffix === '' ? $base : $base . '/' . ltrim($suffix, '/');
    }

    /**
     * Aksi audit untuk impor, perubahan status akun, dan penghapusan.
     */
    public function auditAction(string $event): string
    {
        return match ([$this, $event]) {
            [self::Student, 'import'] => AuditLogModel::IMPORT_STUDENT,
            [self::Teacher, 'import'] => AuditLogModel::IMPORT_TEACHER,
            [self::Student, 'status'] => AuditLogModel::STUDENT_STATUS,
            [self::Teacher, 'status'] => AuditLogModel::TEACHER_STATUS,
            [self::Student, 'delete'] => AuditLogModel::STUDENT_DELETE,
            [self::Teacher, 'delete'] => AuditLogModel::TEACHER_DELETE,
        };
    }
}
