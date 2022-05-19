<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use Tests\TestCase;

class RecordStatusMergerTest extends TestCase
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
        $dst = FieldFactory::create('recordStatus', $dstValue);
        $src = FieldFactory::create('recordStatus', $srcValue);
        
        $merger = new RecordStatusMerger($dst, $src);
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
        $dst = FieldFactory::create('recordStatus', $dstValue);
        $src = FieldFactory::create('recordStatus', $srcValue);
        
        $merger = new RecordStatusMerger($dst, $src);
        self::assertFalse($merger->merge());
        self::assertEquals($dstValue, $dst->getValue());
    }
    
    public function provideSuccess(): array
    {
        return [
            [null, 'active', 'active'],
            [null, 'blocked', 'blocked'],
            [null, 'died', 'died'],
            ['active', null, 'active'],
            ['blocked', null, 'blocked'],
            ['died', null, 'died'],
            ['active', 'active', 'active'],
            ['died', 'active', 'died'],
            ['active', 'died', 'died'],
        ];
    }
    
    public function provideError(): array
    {
        return [
            ['active', 'blocked'],
            ['blocked', 'active'],
        ];
    }
}
