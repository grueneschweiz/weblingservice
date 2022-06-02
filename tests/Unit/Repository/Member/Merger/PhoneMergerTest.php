<?php /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use Tests\TestCase;

class PhoneMergerTest extends TestCase
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
        
        $merger = new PhoneMerger($dst, $src);
        
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
        ?string $result
    
    ): void
    {
        $dst = FieldFactory::create($fieldKey, $dstValue);
        $src = FieldFactory::create($fieldKey, $srcValue);
        
        $merger = new PhoneMerger($dst, $src);
        
        self::assertFalse($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    public function provideSuccess(): array
    {
        $keys = ['mobilePhone', 'landlinePhone', 'workPhone'];
        $num = '+41 23 456 78 90';
        
        $cases = [];
        foreach ($keys as $key) {
            $cases["{$key}_srcEmpty"] = [$key, $num, null, $num];
            $cases["{$key}_dstEmpty"] = [$key, null, $num, $num];
            $cases["{$key}_equal"] = [$key, $num, $num, $num];
            $cases["{$key}_similar1"] = [$key, $num, '+41234567890', $num];
            $cases["{$key}_similar2"] = [$key, $num, '0041234567890', $num];
            $cases["{$key}_similar3"] = [$key, $num, '0234567890', $num];
            $cases["{$key}_similar4"] = [$key, $num, '023  4567 890', $num];
        }
        
        return [
            ...$cases,
            'phoneStatus_srcEmpty' => ['phoneStatus', 'active', null, 'active'],
            'phoneStatus_dstEmpty' => ['phoneStatus', null, 'active', 'active'],
            'phoneStatus_equal' => ['phoneStatus', 'active', 'active', 'active'],
            'phoneStatus_srcUnwanted' => ['phoneStatus', 'active', 'unwanted', 'unwanted'],
            'phoneStatus_dstUnwanted' => ['phoneStatus', 'unwanted', 'active', 'unwanted'],
        ];
    }
    
    public function provideError(): array
    {
        $keys = ['mobilePhone', 'landlinePhone', 'workPhone'];
        $num = '+41 23 456 78 90';
    
        $cases = [];
        foreach ($keys as $key) {
            $cases["{$key}_collision1"] = [$key, $num, '999', $num];
            $cases["{$key}_collision2"] = [$key, $num, "$num 12", $num];
            $cases["{$key}_collision3"] = [$key, $num, "0$num", $num];
        }
        
        return $cases;
    }
}
