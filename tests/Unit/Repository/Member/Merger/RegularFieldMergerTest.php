<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use Tests\TestCase;

class RegularFieldMergerTest extends TestCase
{
    /**
     * @dataProvider provideSuccess_singleValue
     */
    public function testMerge__success_singleValue(
        ?string $dstValue,
        ?string $srcValue,
        ?string $result
    ): void
    {
        $dst = FieldFactory::create('firstName', $dstValue);
        $src = FieldFactory::create('firstName', $srcValue);
        
        $merger = new RegularFieldMerger($dst, $src);
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    /**
     * @dataProvider provideError_singleValue
     */
    public function testMerge__error_singleValue(
        ?string $dstValue,
        ?string $srcValue,
    ): void
    {
        $dst = FieldFactory::create('firstName', $dstValue);
        $src = FieldFactory::create('firstName', $srcValue);
        
        $merger = new RegularFieldMerger($dst, $src);
        self::assertFalse($merger->merge());
        self::assertEquals($dstValue, $dst->getValue());
    }
    
    public function provideSuccess_singleValue(): array
    {
        return [
            [null, 'hans', 'hans'],
            ['hans', 'hans', 'hans'],
            ['hans', null, 'hans'],
            ['HANS', 'hans', 'HANS'],
            ['hans', 'HANS', 'hans'],
        ];
    }
    
    public function provideError_singleValue(): array
    {
        return [
            ['maria', 'hans'],
        ];
    }
    
    /**
     * @dataProvider provideSuccess_append
     */
    public function testMerge__success_append(
        ?string $dstValue,
        ?string $srcValue,
        ?string $result
    ): void
    {
        $dst = FieldFactory::create('notesCountry', $dstValue);
        $src = FieldFactory::create('notesCountry', $srcValue);
        
        $merger = new RegularFieldMerger($dst, $src);
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    public function provideSuccess_append(): array
    {
        return [
            [null, 'new', 'new'],
            ['old', null, 'old'],
            ['old', 'new', "old\nnew"],
        ];
    }
    
    /**
     * @dataProvider provideSuccess_append_multiValue
     */
    public function testMerge__success_append_multiValue(
        null|string|array $dstValue,
        null|string|array $srcValue,
        null|string|array $result
    ): void
    {
        $dst = FieldFactory::create('request');
        $src = FieldFactory::create('request');
    
        $dst->setValue($dstValue);
        $src->setValue($srcValue);
        
        $merger = new RegularFieldMerger($dst, $src);
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    public function provideSuccess_append_multiValue(): array
    {
        return [
            [null, 'driver', ['driver']],
            ['driver', null, ['driver']],
            ['driver', 'movie', ['driver', 'movie']],
        ];
    }
}
