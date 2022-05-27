<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use Tests\TestCase;

class SalutationMergerTest extends TestCase
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
        
        $merger = new SalutationMerger($dst, $src);
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    /**
     * @dataProvider provideError
     */
    public function testMerge__error(
        string  $fieldKey,
        ?string $dstValue,
        ?string $srcValue,
    ): void
    {
        $dst = FieldFactory::create($fieldKey, $dstValue);
        $src = FieldFactory::create($fieldKey, $srcValue);
        
        $merger = new SalutationMerger($dst, $src);
        self::assertFalse($merger->merge());
        self::assertEquals($dstValue, $dst->getValue());
    }
    
    public function provideSuccess(): array
    {
        return [
            ['salutationFormal', null, null, null],
            
            ['salutationFormal', null, 'fD', 'fD'],
            ['salutationFormal', null, 'mD', 'mD'],
            ['salutationFormal', null, 'mfD', 'mfD'],
            ['salutationFormal', null, 'fF', 'fF'],
            ['salutationFormal', null, 'mF', 'mF'],
            ['salutationFormal', null, 'mfF', 'mfF'],
            
            ['salutationFormal', 'fD', null, 'fD'],
            ['salutationFormal', 'mD', null, 'mD'],
            ['salutationFormal', 'mfD', null, 'mfD'],
            ['salutationFormal', 'fF', null, 'fF'],
            ['salutationFormal', 'mF', null, 'mF'],
            ['salutationFormal', 'mfF', null, 'mfF'],
            
            ['salutationFormal', 'fD', 'fD', 'fD'],
            ['salutationFormal', 'mD', 'mD', 'mD'],
            ['salutationFormal', 'mfD', 'mfD', 'mfD'],
            ['salutationFormal', 'fF', 'fF', 'fF'],
            ['salutationFormal', 'mF', 'mF', 'mF'],
            ['salutationFormal', 'mfF', 'mfF', 'mfF'],
            
            ['salutationInformal', null, null, null],
            
            ['salutationInformal', null, 'nD', 'nD'],
            ['salutationInformal', null, 'fD', 'fD'],
            ['salutationInformal', null, 'mD', 'mD'],
            ['salutationInformal', null, 'mfD', 'mfD'],
            ['salutationInformal', null, 'fF', 'fF'],
            ['salutationInformal', null, 'mF', 'mF'],
            ['salutationInformal', null, 'mfF', 'mfF'],
            
            ['salutationInformal', 'nD', null, 'nD'],
            ['salutationInformal', 'fD', null, 'fD'],
            ['salutationInformal', 'mD', null, 'mD'],
            ['salutationInformal', 'mfD', null, 'mfD'],
            ['salutationInformal', 'fF', null, 'fF'],
            ['salutationInformal', 'mF', null, 'mF'],
            ['salutationInformal', 'mfF', null, 'mfF'],
            
            ['salutationInformal', 'nD', 'nD', 'nD'],
            ['salutationInformal', 'fD', 'fD', 'fD'],
            ['salutationInformal', 'mD', 'mD', 'mD'],
            ['salutationInformal', 'mfD', 'mfD', 'mfD'],
            ['salutationInformal', 'fF', 'fF', 'fF'],
            ['salutationInformal', 'mF', 'mF', 'mF'],
            ['salutationInformal', 'mfF', 'mfF', 'mfF'],
            
            ['salutationInformal', 'nD', 'fD', 'fD'],
            ['salutationInformal', 'nD', 'mD', 'mD'],
            ['salutationInformal', 'nD', 'mfD', 'mfD'],
            ['salutationInformal', 'nF', 'fF', 'fF'],
            ['salutationInformal', 'nF', 'mF', 'mF'],
            ['salutationInformal', 'nF', 'mfF', 'mfF'],
            
            ['salutationInformal', 'fD', 'nD', 'fD'],
            ['salutationInformal', 'mD', 'nD', 'mD'],
            ['salutationInformal', 'mfD', 'nD', 'mfD'],
            ['salutationInformal', 'fF', 'nF', 'fF'],
            ['salutationInformal', 'mF', 'nF', 'mF'],
            ['salutationInformal', 'mfF', 'nF', 'mfF'],
        ];
    }
    
    public function provideError(): array
    {
        return [
            ['salutationFormal', 'fD', 'mD'],
            ['salutationFormal', 'fD', 'mfD'],
            ['salutationFormal', 'fD', 'fF'],
            ['salutationFormal', 'fD', 'mF'],
            ['salutationFormal', 'fD', 'mfF'],
            // ...
            
            ['salutationInformal', 'fD', 'mD'],
            ['salutationInformal', 'fD', 'mfD'],
            ['salutationInformal', 'fD', 'fF'],
            ['salutationInformal', 'fD', 'mF'],
            ['salutationInformal', 'fD', 'mfF'],
            // ...
        ];
    }
}
