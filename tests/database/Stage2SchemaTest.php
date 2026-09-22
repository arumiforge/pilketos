<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\CandidateModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Stage 2: migration 2026-02-01-000001_AddThemeLayoutToCandidates.
 * (Rollback migration ikut teruji: DatabaseTestTrait menjalankan down()
 * seluruh migration sebelum setiap test karena $refresh = true.)
 *
 * @internal
 */
final class Stage2SchemaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    public function testSeededCandidatesUseDifferentLayouts(): void
    {
        $layouts = array_column($this->db->table('candidates')->orderBy('nomor_urut')->get()->getResultArray(), 'theme_layout');

        $this->assertSame(['split', 'poster', 'column'], $layouts);
    }

    public function testThemeLayoutAcceptsKnownValuesAndNull(): void
    {
        $model = model(CandidateModel::class);

        $this->assertTrue($model->update(1, ['theme_layout' => 'poster']));
        $this->assertTrue($model->update(2, ['theme_layout' => null]));
        $this->assertFalse($model->update(3, ['theme_layout' => 'fancy']));
        $this->assertArrayHasKey('theme_layout', $model->errors());

        $this->seeInDatabase('candidates', ['id' => 1, 'theme_layout' => 'poster']);
        $this->seeInDatabase('candidates', ['id' => 2, 'theme_layout' => null]);
    }

    public function testDatabaseRejectsUnknownLayoutEvenWithoutModelValidation(): void
    {
        $this->expectException(DatabaseException::class);

        $this->db->table('candidates')->where('id', 1)->update(['theme_layout' => 'fancy']);
    }
}
