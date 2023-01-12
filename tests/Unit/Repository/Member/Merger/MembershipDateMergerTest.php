<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace Tests\Unit\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use App\Repository\Member\Merger\MembershipDateMerger;
use Tests\TestCase;

class MembershipDateMergerTest extends TestCase
{
    
    /**
     * @dataProvider provideSuccess
     */
    public function testMerge__success(
        string  $field,
        ?string $dstValue,
        ?string $srcValue,
        ?string $result
    ): void
    {
        $dst = FieldFactory::create($field, $dstValue);
        $src = FieldFactory::create($field, $srcValue);
        
        $merger = new MembershipDateMerger($dst, $src);
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    public function provideSuccess(): array
    {
        return [
            'equal' => ['membershipStart', '2000-01-02', '2000-01-02', '2000-01-02'],
            'srcEmpty' => ['membershipStart', '2000-01-02', null, '2000-01-02'],
            'dstEmpty' => ['membershipStart', null, '2000-01-02', '2000-01-02'],
            'bothEmpty' => ['membershipStart', null, null, null],
            'srcStartEarlier' => ['membershipStart', '2000-11-11', '2000-01-01', '2000-01-01'],
            'dstStartEarlier' => ['membershipStart', '2000-01-01', '2000-11-11', '2000-01-01'],
            'srcEndLater' => ['membershipEnd', '2000-01-01', '2000-11-11', '2000-11-11'],
            'dstEndLater' => ['membershipEnd', '2000-11-11', '2000-01-01', '2000-11-11'],
            'srcTime0' => ['membershipStart', '2000-01-02', '1970-01-01', '2000-01-02'],
            'dstTime0' => ['membershipStart', '1970-01-01', '2001-01-02', '2001-01-02'],
        ];
    }
}
