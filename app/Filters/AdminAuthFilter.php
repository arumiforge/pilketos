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
        return 'admin/masuk';
    }

    protected function deniedMessage(): string
    {
        return 'Silakan masuk sebagai admin terlebih dahulu.';
    }

    /**
     * Stage 13: akun harus masih ada dan, bila sesi menyimpan cap kata sandi
     * (AdminModel::SESSION_STAMP_KEY, dipasang saat login), cap itu harus
     * masih cocok. Setelah kata sandi diganti, sesi admin yang sama di
     * perangkat lain otomatis keluar.
     */
    protected function accountIsValid(int $id): bool
    {
        $stamp = model(AdminModel::class)->sessionStamp($id);

        if ($stamp === null) {
            return false;
        }

        $saved = session()->get(AdminModel::SESSION_STAMP_KEY);

        return $saved === null || hash_equals($stamp, (string) $saved);
    }
}
