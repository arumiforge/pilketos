<?php

namespace App\Services;

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
     * Nama unique key "satu suara aktif" (migration Stage 1).
     */
    public function activeVoteKey(): string
    {
        return 'uq_' . $this->voteTable() . '_active';
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
}
