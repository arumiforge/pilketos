<?php

namespace App\Filters;

use App\Models\StudentModel;

class StudentAuthFilter extends AuthFilter
{
    protected function userType(): string
    {
        return 'student';
    }

    protected function loginPath(): string
    {
        return 'siswa/masuk';
    }

    protected function deniedMessage(): string
    {
        return 'Kamu perlu masuk sebagai siswa terlebih dahulu.';
    }

    /**
     * Stage 4: sesi pemilih yang ditinggal 15 menit berakhir otomatis.
     */
    protected function idleSeconds(): int
    {
        return self::VOTER_IDLE_SECONDS;
    }

    protected function accountIsValid(int $id): bool
    {
        return model(StudentModel::class)->findActive($id) !== null;
    }
}
