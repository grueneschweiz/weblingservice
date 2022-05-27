<?php /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use Tests\TestCase;

class IgnoreConflictMergerTest extends TestCase
{
    
    /**
     * @dataProvider provideSuccess
     */
    public function testMerge__success(
        string  $fieldKey,
        ?string $dstValue,
        ?string $srcValue,
        ?string $result
    ): void
    {
        $dst = FieldFactory::create($fieldKey, $dstValue);
        $src = FieldFactory::create($fieldKey, $srcValue);
        
        $merger = new IgnoreConflictMerger($dst, $src);
        
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    public function provideSuccess(): array
    {
        return [
            'srcEmpty' => ['entryChannel', 'web', null, 'web'],
            'dstEmpty' => ['entryChannel', null, 'web', 'web'],
            'equal' => ['entryChannel', 'web', 'web', 'web'],
            'conflict' => ['entryChannel', 'web', 'analog', 'web'],
        ];
    }
}
