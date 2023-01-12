<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace Tests\Unit\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use App\Repository\Member\Merger\BirthdayMerger;
use Tests\TestCase;

class BirthdayMergerTest extends TestCase
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
        $dst = FieldFactory::create('birthday', $dstValue);
        $src = FieldFactory::create('birthday', $srcValue);
        
        $merger = new BirthdayMerger($dst, $src);
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
        $dst = FieldFactory::create('birthday', $dstValue);
        $src = FieldFactory::create('birthday', $srcValue);
        
        $merger = new BirthdayMerger($dst, $src);
        self::assertFalse($merger->merge());
        self::assertEquals($dstValue, $dst->getValue());
    }
    
    public function provideSuccess(): array
    {
        return [
            'equal' => ['2000-01-02', '2000-01-02', '2000-01-02'],
            'srcEmpty' => ['2000-01-02', null, '2000-01-02'],
            'dstEmpty' => [null, '2000-01-02', '2000-01-02'],
            'bothEmpty' => [null, null, null],
            'srcMoreSpecific' => ['2000-01-01', '2000-01-02', '2000-01-02'],
            'dstMoreSpecific' => ['2000-01-02', '2000-01-01', '2000-01-02'],
            'srcTime0' => ['2000-01-02', '1970-01-01', '2000-01-02'],
            'dstTime0' => ['1970-01-01', '2001-01-02', '2001-01-02'],
        ];
    }
    
    public function provideError(): array
    {
        return [
            'unresolvable' => ['2000-01-02', '2000-02-01'],
            'differentYear1' => ['1999-12-31', '2000-01-01'],
            'differentYear2' => ['2000-01-01', '1999-12-31'],
        ];
    }
}
