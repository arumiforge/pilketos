<?php

namespace App\Services\Import;

use RuntimeException;

/**
 * Commit import bertabrakan dengan perubahan lain (unique key / kunci baris).
 * Seluruh transaction sudah dibatalkan; admin cukup mengulang impor.
 */
final class ImportConflictException extends RuntimeException
{
}
