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

    protected function accountIsValid(int $id): bool
    {
        return model(TeacherModel::class)->findActive($id) !== null;
    }
}
