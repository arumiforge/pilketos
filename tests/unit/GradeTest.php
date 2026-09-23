<?php

use App\Libraries\Grade;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Stage 3: jenjang 7/8/9 diekstrak dari nama kelas secara aman.
 *
 * @internal
 */
final class GradeTest extends CIUnitTestCase
{
    public static function classes(): iterable
    {
        // Angka Arab di awal
        yield ['7A', 7];
        yield ['7 B', 7];
        yield ['07-C', 7];
        yield ['8.1', 8];
        yield ['9', 9];
        yield ['Kelas 9D', 9];
        yield ['kelas8a', 8];
        // Angka Romawi di awal
        yield ['VII A', 7];
        yield ['viiia', 8];
        yield ['VIII-B', 8];
        yield ['IX-C', 9];
        yield ['kelas ix d', 9];
        // Bukan jenjang SMP / tidak jelas
        yield ['70', null];
        yield ['10A', null];
        yield ['6B', null];
        yield ['VIIII', null];
        yield ['IVA', null];
        yield ['X IPA', null];
        yield ['A7', null];
        yield ['', null];
        yield [null, null];
    }

    #[DataProvider('classes')]
    public function testFromKelas(?string $kelas, ?int $grade): void
    {
        $this->assertSame($grade, Grade::fromKelas($kelas));
    }

    public function testNormalizeKelas(): void
    {
        $this->assertSame('7A', Grade::normalizeKelas(' 7a '));
        $this->assertSame('VII B', Grade::normalizeKelas("vii \t b"));
        $this->assertSame('', Grade::normalizeKelas(null));
    }

    public function testLabel(): void
    {
        $this->assertSame('Kelas 8', Grade::label(8));
        $this->assertSame('Lainnya', Grade::label(null));
    }
}
