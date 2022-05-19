<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Member;
use Tests\TestCase;

class EmailMergerTest extends TestCase
{
    
    /**
     * @dataProvider provideSuccess
     */
    public function testMerge__success(
        string  $fieldKey,
        array   $dstMemberData,
        array   $srcMemberData,
        ?string $result
    ): void
    {
        $dstMember = new Member($dstMemberData);
        $srcMember = new Member($srcMemberData);
        
        $dst = $dstMember->$fieldKey;
        $src = $srcMember->$fieldKey;
        
        /** @noinspection PhpParamsInspection */
        $merger = new EmailMerger($dst, $src, $dstMember, $srcMember);
        
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    /**
     * @dataProvider provideError
     */
    public function testMerge__error(
        string  $fieldKey,
        array   $dstMemberData,
        array   $srcMemberData,
        ?string $result
    ): void
    {
        $dstMember = new Member($dstMemberData);
        $srcMember = new Member($srcMemberData);
        
        $dst = $dstMember->$fieldKey;
        $src = $srcMember->$fieldKey;
    
        /** @noinspection PhpParamsInspection */
        $merger = new EmailMerger($dst, $src, $dstMember, $srcMember);
        
        self::assertFalse($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    
    public function provideSuccess(): array
    {
        return [
            'email1_empty' => ['email1', ['email1' => null], ['email1' => null], null],
            'email1_dstEmpty' => ['email1', ['email1' => 'a'], ['email1' => null], 'a'],
            'email1_srcEmpty' => ['email1', ['email1' => null], ['email1' => 'b'], 'b'],
            'email1_equal' => ['email1', ['email1' => 'c'], ['email1' => 'c'], 'c'],
            'email1_addAsEmail2' => ['email1', ['email1' => 'a', 'email2' => null], ['email1' => 'b'], 'a'], // b is now in email 2
            'email1_alreadyEmail2' => ['email1', ['email1' => 'a', 'email2' => 'b'], ['email1' => 'b'], 'a'], // b is already in email 2
            
            'email2_empty' => ['email2', ['email2' => null], ['email2' => null], null],
            'email2_srcEmpty' => ['email2', ['email2' => 'a'], ['email2' => null], 'a'],
            'email2_dstEmpty' => ['email2', ['email1' => 'a', 'email2' => null], ['email2' => 'b'], 'b'],
            'email2_equal' => ['email2', ['email1' => 'a', 'email2' => 'c'], ['email2' => 'c'], 'c'],
            'email2_addAsEmail1' => ['email2', ['email1' => null, 'email2' => null], ['email2' => 'b'], null], // 'b' is now in email 1
            'email2_alreadyEmail2' => ['email2', ['email1' => 'a', 'email2' => 'b'], ['email1' => 'b', 'email2' => 'b'], 'b'], // b is already in email 2
            
            'email1_dstInvalid' => [
                'email1',
                ['email1' => 'a', 'email2' => 'b', 'emailStatus' => 'invalid'],
                ['email1' => 'c', 'email2' => 'd', 'emailStatus' => 'active'],
                'c'
            ],
            'email2_dstInvalid' => [
                'email2',
                ['email1' => 'a', 'email2' => 'b', 'emailStatus' => 'invalid'],
                ['email1' => 'c', 'email2' => 'd', 'emailStatus' => 'active'],
                'd'
            ],
            'email1_srcInvalid' => [
                'email1',
                ['email1' => 'a', 'email2' => 'b', 'emailStatus' => 'active'],
                ['email1' => 'c', 'email2' => 'd', 'emailStatus' => 'invalid'],
                'a'
            ],
            'email2_srcInvalid' => [
                'email2',
                ['email1' => 'a', 'email2' => 'b', 'emailStatus' => 'active'],
                ['email1' => 'c', 'email2' => 'd', 'emailStatus' => 'invalid'],
                'b'
            ],
            'status_srcUnwanted' => [
                'emailStatus',
                ['email1' => 'a', 'email2' => 'b', 'emailStatus' => 'active'],
                ['email1' => 'a', 'email2' => null, 'emailStatus' => 'unwanted'],
                'unwanted'
            ],
            'status_dstUnwanted' => [
                'emailStatus',
                ['email1' => 'a', 'email2' => null, 'emailStatus' => 'unwanted'],
                ['email1' => 'a', 'email2' => 'd', 'emailStatus' => 'active'],
                'unwanted'
            ],
            'status_dstUnwantedButEmpty' => [
                'emailStatus',
                ['email1' => null, 'email2' => null, 'emailStatus' => 'unwanted'],
                ['email1' => 'a', 'email2' => 'd', 'emailStatus' => 'active'],
                'unwanted'
            ],
            'status_srcUnwantedButEmpty' => [
                'emailStatus',
                ['email1' => 'a', 'email2' => 'd', 'emailStatus' => 'active'],
                ['email1' => null, 'email2' => null, 'emailStatus' => 'unwanted'],
                'active'
            ],
        ];
    }
    
    public function provideError(): array
    {
        return [
            'email1_three' => ['email1', ['email1' => 'a', 'email2' => 'b'], ['email1' => 'c'], 'a'],
            'email1_four' => ['email1', ['email1' => 'a', 'email2' => 'b'], ['email1' => 'c', 'email2' => 'd'], 'a'],
            'email2_three' => ['email2', ['email1' => 'a', 'email2' => 'b'], ['email2' => 'c'], 'b'],
            'email2_four' => ['email2', ['email1' => 'a', 'email2' => 'b'], ['email1' => 'c', 'email2' => 'd'], 'b'],
        ];
    }
}
