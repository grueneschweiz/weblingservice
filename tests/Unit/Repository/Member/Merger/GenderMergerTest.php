<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use Tests\TestCase;

class GenderMergerTest extends TestCase
{
    
    /**
     * @dataProvider provideSuccess
     */
    public function testMerge__success(
        ?string $dstValue,
        ?string $srcValue,
        ?string $result
    ): void
    {
        $dst = FieldFactory::create('gender', $dstValue);
        $src = FieldFactory::create('gender', $srcValue);
        
        $merger = new GenderMerger($dst, $src);
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    /**
     * @dataProvider provideError
     */
    public function testMerge__error(
        ?string $dstValue,
        ?string $srcValue,
    ): void
    {
        $dst = FieldFactory::create('gender', $dstValue);
        $src = FieldFactory::create('gender', $srcValue);
        
        $merger = new GenderMerger($dst, $src);
        self::assertFalse($merger->merge());
        self::assertEquals($dstValue, $dst->getValue());
    }
    
    public function provideSuccess(): array
    {
        return [
            [null, 'f', 'f'],
            [null, 'm', 'm'],
            [null, 'n', 'n'],
            [null, 'mf', 'mf'],
            [null, null, null],
            ['n', null, 'n'],
            ['n', 'f', 'f'],
            ['n', 'm', 'm'],
            ['n', 'mf', 'mf'],
            ['n', 'n', 'n'],
            ['f', 'n', 'f'],
            ['f', null, 'f'],
            ['m', 'n', 'm'],
            ['m', null, 'm'],
            ['mf', 'mf', 'mf'],
            ['mf', null, 'mf'],
            ['mf', 'n', 'mf'],
        ];
    }
    
    public function provideError(): array
    {
        return [
            ['f', 'mf'],
            ['f', 'm'],
            ['m', 'mf'],
            ['m', 'f'],
            ['mf', 'f'],
            ['mf', 'm'],
        ];
    }
}
