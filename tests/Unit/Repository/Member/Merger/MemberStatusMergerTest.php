<?php /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use Tests\TestCase;

class MemberStatusMergerTest extends TestCase
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
        
        /** @noinspection PhpParamsInspection */
        $merger = new MemberStatusMerger($dst, $src);
        
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    public function provideSuccess(): array
    {
        $keys = ['memberStatusMunicipality', 'memberStatusRegion', 'memberStatusCanton', 'memberStatusCountry', 'memberStatusYoung'];
        
        $cases = [];
        foreach ($keys as $key) {
            $cases["{$key}_srcEmpty"] = [$key, 'member', null, 'member'];
            $cases["{$key}_dstEmpty"] = [$key, null, 'member', 'member'];
            $cases["{$key}_equal"] = [$key, 'member', 'member', 'member'];
            
            $cases["{$key}_srcWins0"] = [$key, 'notMember', 'sympathiser', 'sympathiser'];
            $cases["{$key}_srcWins1"] = [$key, 'sympathiser', 'unconfirmed', 'unconfirmed'];
            $cases["{$key}_srcWins2"] = [$key, 'unconfirmed', 'member', 'member'];
            $cases["{$key}_srcWins3"] = [$key, 'member', 'resigned', 'resigned'];
            $cases["{$key}_srcWins4"] = [$key, 'resigned', 'expelled', 'expelled'];
            
            $cases["{$key}_dstWins0"] = [$key, 'sympathiser', 'notMember', 'sympathiser'];
            $cases["{$key}_dstWins1"] = [$key, 'unconfirmed', 'sympathiser', 'unconfirmed'];
            $cases["{$key}_dstWins2"] = [$key, 'member', 'unconfirmed', 'member'];
            $cases["{$key}_dstWins3"] = [$key, 'resigned', 'member', 'resigned'];
            $cases["{$key}_dstWins4"] = [$key, 'expelled', 'resigned', 'expelled'];
        }
        
        return $cases;
    }
}
