<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use Tests\TestCase;

class CoupleMergerTest extends TestCase
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
        $dst = FieldFactory::create('coupleCategory', $dstValue);
        $src = FieldFactory::create('coupleCategory', $srcValue);
        
        $merger = new CoupleMerger($dst, $src);
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    /**
     * @dataProvider provideError
     */
    public function testMerge__error(
        ?string $dstValue,
        ?string $srcValue,
        ?string $result
    ): void
    {
        $dst = FieldFactory::create('coupleCategory', $dstValue);
        $src = FieldFactory::create('coupleCategory', $srcValue);
        
        $merger = new CoupleMerger($dst, $src);
        self::assertFalse($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    public function provideSuccess(): array
    {
        return [
            [null, 'single', 'single'],
            ['single', null, 'single'],
            ['single', 'single', 'single'],
            ['partner1', 'single', 'partner1'],
            ['partner1', null, 'partner1'],
            ['partner2', 'single', 'partner2'],
            ['partner2', null, 'partner2'],
        ];
    }
    
    public function provideError(): array
    {
        return [
            ['single', 'partner1', 'single'],
            ['single', 'partner2', 'single'],
            ['partner1', 'partner2', 'partner1'],
            ['partner2', 'partner1', 'partner2'],
        ];
    }
}
