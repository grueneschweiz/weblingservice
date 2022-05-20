<?php /** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Exceptions\MemberMergeException;
use App\Repository\Debtor\Debtor;
use App\Repository\Debtor\DebtorRepository;
use App\Repository\Group\GroupRepository;
use App\Repository\Member\Member;
use App\Repository\Member\MemberRepository;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberMergerTest extends TestCase
{
    /**
     * @dataProvider provideTestMerge_debtorSuccess
     * @dataProvider provideTestMerge_memberSuccess
     */
    public function testMerge_success(array $dstMemberData, array $srcMemberData, array $expectedMemberData): void
    {
        $memberRepositoryMock = \Mockery::mock(MemberRepository::class);
        $debtorRepositoryMock = \Mockery::mock(DebtorRepository::class);
        
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(100);
        
        $dst = new Member($dstMemberData[0], 1, [$rootGroup], true, $dstMemberData[1] ?? []);
        $src = new Member($srcMemberData[0], 2, [$rootGroup], true, $srcMemberData[1] ?? []);
        
        $memberRepositoryMock
            ->shouldReceive('save')
            ->once()
            ->with($dst)
            ->andReturnUsing(static function () use ($dst, $src) {
                $merged = clone $dst;
                $debtorIds = new \ReflectionProperty($merged, 'debtorIds');
                $debtorIds->setValue($merged, array_unique([...$dst->getDebtorIds(), ...$src->getDebtorIds()]));
                return $merged;
            });
        $memberRepositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($src);
        
        foreach ($src->getDebtorIds() as $debtorId) {
            $debtorSrc = new Debtor($debtorId, $src->id);
            
            $debtorRepositoryMock
                ->shouldReceive('get')
                ->once()
                ->with($debtorId)
                ->andReturn($debtorSrc);
            
            $debtorRepositoryMock
                ->shouldReceive('put')
                ->once()
                ->with(\Mockery::on(
                    static fn($arg) => $arg instanceof Debtor
                        && $arg->getMemberId() === $dst->id
                        && $arg->getId() === $debtorId
                ));
        }
        
        $merger = new MemberMerger($memberRepositoryMock, $debtorRepositoryMock);
        
        $merged = $merger->merge($dst, $src);
        
        foreach ($expectedMemberData[0] as $fieldKey => $expectedValue) {
            $result = $merged->$fieldKey->getValue();
            self::assertEqualsCanonicalizing($expectedValue, $result, "Error in field: $fieldKey");
        }
        if (isset($expectedMemberData[1])) {
            foreach ($expectedMemberData[1] as $debtorId) {
                self::assertContains($debtorId, $merged->getDebtorIds());
            }
        }
    }
    
    /**
     * @dataProvider provideTestMerge_conflict
     */
    public function testMerge_conflict(array $dstMemberData, array $srcMemberData, array $expectedConflicts): void
    {
        $memberRepositoryMock = \Mockery::mock(MemberRepository::class);
        $debtorRepositoryMock = \Mockery::mock(DebtorRepository::class);
        
        $merger = new MemberMerger($memberRepositoryMock, $debtorRepositoryMock);
        
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(100);
        
        $dst = new Member($dstMemberData[0], 1, [$rootGroup], true);
        $src = new Member($srcMemberData[0], 2, [$rootGroup], true);
        
        $this->expectException(MemberMergeException::class);
        $this->expectExceptionMessage(json_encode([
            'success' => false,
            'conflicts' => $expectedConflicts,
            'merged' => [],
            'message' => 'Some merge conflicts must be resolved manually. Nothing merged. See "conflicts". Do not retry.',
        ], JSON_THROW_ON_ERROR));
        
        $merger->merge($dst, $src);
    }
    
    public function provideTestMerge_conflict(): array
    {
        $memberData = self::getMemberData();
        
        return [
            [
                [$memberData],
                [[
                    'recordCategory' => 'npo',
                    'language' => 'f',
                    'gender' => 'm',
                    'address1' => 'Dorfstrasse 1',
                    'address2' => null,
                    'zip' => '8888',
                    'city' => 'wald',
                    'email1' => 'new@mail.com',
                    'memberStatusCountry' => 'resigned',
                    'mandateCountry' => ['legislativeActive'],
                ]],
                [
                    'recordCategory',
                    'language',
                    'gender',
                    'address1',
                    'zip',
                    'city',
                    'email1'
                ],
            ],
        ];
    }
    
    public static function getMemberData(): array
    {
        return [
            'recordStatus' => 'active',
            'firstName' => 'Maria',
            'lastName' => 'Muster',
            'recordCategory' => 'private',
            'language' => 'd',
            'gender' => 'f',
            'salutationFormal' => 'fD',
            'salutationInformal' => 'fD',
            'title' => 'Dr.',
            'company' => 'Company',
            'address1' => "22 rue de l'annonciade",
            'address2' => 'Case postale 123',
            'zip' => '1234',
            'city' => 'entenhausen',
            'country' => 'ch',
            'postStatus' => 'active',
            'email1' => strtolower('unittest+' . Str::random() . '@unittest.ut'),
            'email2' => 'hugo@email.com',
            'emailStatus' => 'active',
            'mobilePhone' => '+41234567890',
            'landlinePhone' => '+41098765432',
            'workPhone' => '0777777777',
            'phoneStatus' => 'unwanted',
            'entryChannel' => 'must not be merged',
            'birthday' => '1970-01-01',
            'website' => 'https://mysite.com',
            'facebook' => 'https://facebook.com/boomer',
            'twitter' => '@nerd',
            'instagram' => '@yay',
            'iban' => 'CH123',
            'profession' => 'activist',
            'professionCategory' => 'entrepreneurs',
            'networkNpo' => 'betterWorld',
            'interests' => ['energy', 'climate'],
            'request' => ['design'],
            'coupleCategory' => 'partner1',
            'partnerSalutationFormal' => 'fD',
            'partnerSalutationInformal' => 'fD',
            'partnerFirstName' => 'Vroni',
            'partnerLastName' => 'Maurer',
            'memberStatusMunicipality' => 'member',
            'memberStatusRegion' => 'member',
            'memberStatusCanton' => 'member',
            'memberStatusCountry' => 'member',
            'memberStatusYoung' => 'member',
            'membershipStart' => '1970-01-01',
            'membershipEnd' => '2034-12-31',
            'responsibility' => 'you',
            'membershipFeeMunicipality' => 'regular',
            'membershipFeeRegion' => 'reduced',
            'membershipFeeCanton' => 'couple',
            'membershipFeeCountry' => 'no',
            'membershipFeeYoung' => 'no',
            'magazineMunicipality' => 'yes',
            'newsletterMunicipality' => 'no',
            'pressReleaseMunicipality' => 'yes',
            'roleMunicipality' => 'problem solver',
            'mandateMunicipality' => ['legislativeActive'],
            'mandateMunicipalityDetail' => 'parli',
            'donorMunicipality' => 'sponsor',
            'notesMunicipality' => 'note',
            'roleRegion' => 'asdf',
            'mandateRegion' => ['governorActive'],
            'mandateRegionDetail' => 'mandateR',
            'donorRegion' => 'donor',
            'magazineCantonD' => 'yes',
            'magazineCantonF' => 'no',
            'newsletterCantonD' => 'yes',
            'newsletterCantonF' => 'no',
            'pressReleaseCantonD' => 'yes',
            'pressReleaseCantonF' => 'no',
            'roleCanton' => 'chef',
            'mandateCanton' => ['commissionActive'],
            'mandateCantonDetail' => 'canton',
            'donorCanton' => 'majorDonor',
            'notesCanton' => 'my note',
            'magazineCountryD' => 'yes',
            'magazineCountryF' => 'no',
            'newsletterCountryD' => 'yes',
            'newsletterCountryF' => 'no',
            'pressReleaseCountryD' => 'yes',
            'pressReleaseCountryF' => 'no',
            'roleCountry' => 'backer',
            'roleInternational' => 'butcher',
            'mandateCountry' => ['judikativeActive'],
            'mandateCountryDetail' => 'the judge',
            'donorCountry' => 'donor',
            'notesCountry' => 'long lives the judge',
            'notesCantonYoung' => 'party',
            'notesCountryYoung' => 'work',
            'legacy' => 'old',
            'magazineOther' => 'deprecatedM',
            'newsletterOther' => 'deprecatedNL',
            'networkOther' => 'deprecatedNW',
            'roleYoung' => 'maker',
            'donorYoung' => 'sponsor',
        ];
    }
    
    public function provideTestMerge_memberSuccess(): array
    {
        $memberData = self::getMemberData();
        
        return [
            'memberData_identical' => [
                [$memberData],
                [$memberData],
                [$memberData],
            ],
            'memberData_emptyDst' => [
                [[]],
                [$memberData],
                [$memberData],
            ],
            'memberData_emptySrc' => [
                [$memberData],
                [[]],
                [$memberData],
            ],
            'memberData_differentNoMergeConflict' => [
                [[
                    'recordStatus' => 'active',
                    'recordCategory' => 'private',
                    'address1' => "rue de l'annonciade 22",
                    'city' => 'Entenhausen',
                    'mobilePhone' => '0234567890',
                    'entryChannel' => 'dst entry',
                    'interests' => ['traffic', 'digitisation', 'energy'],
                    'memberStatusCountry' => 'sympathiser',
                    'magazineCantonD' => null,
                    'notesCountry' => 'who lives long?'
                ]],
                [$memberData],
                [[...$memberData,
                    'address1' => "rue de l'annonciade 22",
                    'city' => 'Entenhausen',
                    'mobilePhone' => '0234567890',
                    'entryChannel' => 'dst entry',
                    'interests' => ['climate', 'traffic', 'digitisation', 'energy'],
                    'notesCountry' => "who lives long?\nlong lives the judge"
                ]],
            ],
        ];
    }
    
    public function provideTestMerge_debtorSuccess(): array
    {
        $memberData = self::getMemberData();
        
        return [
            'debtor_dstNoDebtors' => [
                [$memberData, []],
                [$memberData, [1000, 1001]],
                [$memberData, [1000, 1001]],
            ],
            'debtor_srcNoDebtors' => [
                [$memberData, [1000, 1001]],
                [$memberData, []],
                [$memberData, [1000, 1001]],
            ],
            'debtor_noDebtors' => [
                [$memberData, []],
                [$memberData, []],
                [$memberData, []],
            ],
            'debtor_differentDebtors' => [
                [$memberData, [1000]],
                [$memberData, [1001]],
                [$memberData, [1000, 1001]],
            ],
        ];
    }
}
