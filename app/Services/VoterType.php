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
 * dari route selalu divalidasi lewat VoterType::tryFrom(). Stage 7: segmen
 * URL memakai slug() (siswa/guru), bukan nilai enum.
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
     * Segmen URL untuk jenis pemilih ini (Stage 7: bahasa Indonesia).
     * Nilai enum (student/teacher) tetap dipakai untuk sesi & database.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Student => 'siswa',
            self::Teacher => 'guru',
        };
    }

    /**
     * Path route relatif untuk jenis pemilih ini, contoh path('coblos') = "siswa/coblos".
     */
    public function path(string $suffix = ''): string
    {
        return $suffix === '' ? $this->slug() : $this->slug() . '/' . ltrim($suffix, '/');
    }

    /**
     * Path panel admin, contoh adminPath('impor') = "admin/siswa/impor".
     */
    public function adminPath(string $suffix = ''): string
    {
        $base = 'admin/' . $this->slug();

        return $suffix === '' ? $base : $base . '/' . ltrim($suffix, '/');
    }

    /**
     * Path halaman buka kunci hak suara satu pemilih, contoh "admin/buka-kunci/siswa/12".
     */
    public function unlockPath(int|string $id): string
    {
        return 'admin/buka-kunci/' . $this->slug() . '/' . $id;
    }

    /**
     * Aksi audit untuk impor, tambah/ubah (Stage 11), perubahan status akun,
     * dan penghapusan.
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
            [self::Student, 'create'] => AuditLogModel::STUDENT_CREATE,
            [self::Teacher, 'create'] => AuditLogModel::TEACHER_CREATE,
            [self::Student, 'update'] => AuditLogModel::STUDENT_UPDATE,
            [self::Teacher, 'update'] => AuditLogModel::TEACHER_UPDATE,
        };
    }
}
