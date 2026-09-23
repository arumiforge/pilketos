<?php

namespace App\Controllers\Admin;

use App\Services\Import\TeacherImporter;
use App\Services\Import\VoterImporter;

/**
 * Import guru: admin/teachers/import (template teacher-import-template.xlsx).
 */
class TeacherImportController extends ImportController
{
    protected function importer(): VoterImporter
    {
        return new TeacherImporter();
    }
}
