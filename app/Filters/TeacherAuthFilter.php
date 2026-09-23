<?php

namespace App\Filters;

use App\Models\TeacherModel;

class TeacherAuthFilter extends AuthFilter
{
    protected function userType(): string
    {
        return 'teacher';
    }

    protected function loginPath(): string
    {
        return 'teacher/login';
    }

    protected function deniedMessage(): string
    {
        return 'Silakan masuk sebagai guru terlebih dahulu.';
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
        return model(TeacherModel::class)->findActive($id) !== null;
    }
}
