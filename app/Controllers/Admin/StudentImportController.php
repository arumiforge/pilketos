<?php

namespace App\Controllers\Admin;

use App\Services\Import\StudentImporter;
use App\Services\Import\VoterImporter;

/**
 * Import siswa: admin/students/import (template student-import-template.xlsx).
 */
class StudentImportController extends ImportController
{
    protected function importer(): VoterImporter
    {
        return new StudentImporter();
    }
}
