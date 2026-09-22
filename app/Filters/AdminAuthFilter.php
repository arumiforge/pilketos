<?php

namespace App\Filters;

use App\Models\AdminModel;

class AdminAuthFilter extends AuthFilter
{
    protected function userType(): string
    {
        return 'admin';
    }

    protected function loginPath(): string
    {
        return 'admin/login';
    }

    protected function deniedMessage(): string
    {
        return 'Silakan masuk sebagai admin terlebih dahulu.';
    }

    protected function accountIsValid(int $id): bool
    {
        return model(AdminModel::class)->findForSession($id) !== null;
    }
}
