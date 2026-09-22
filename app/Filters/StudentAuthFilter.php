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
        return 'student/login';
    }

    protected function deniedMessage(): string
    {
        return 'Kamu perlu masuk sebagai siswa terlebih dahulu.';
    }

    protected function accountIsValid(int $id): bool
    {
        return model(StudentModel::class)->findActive($id) !== null;
    }
}
