<?php /** @noinspection PhpMultipleClassDeclarationsInspection */


namespace App\Repository\Member\Merger;


use App\Exceptions\DebtorException;
use App\Exceptions\DebtorNotWriteableException;
use App\Exceptions\GroupNotFoundException;
use App\Exceptions\InvalidFixedValueException;
use App\Exceptions\MemberMergeException;
use App\Exceptions\MemberNotFoundException;
use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\MultiSelectOverwriteException;
use App\Exceptions\NoGroupException;
use App\Exceptions\ValueTypeException;
use App\Exceptions\WeblingAPIException;
use App\Exceptions\WeblingFieldMappingConfigException;
use App\Repository\Debtor\DebtorRepository;
use App\Repository\Member\Field\Field;
use App\Repository\Member\Member;
use App\Repository\Member\MemberRepository;
use JsonException;
use Webling\API\ClientException;

class MemberMerger
{
    private const RECORD_STATUS_FIELDS = ['recordStatus'];
    private const GENDER_FIELDS = ['gender', 'salutationFormal', 'salutationInformal'];
    private const ADDRESS_FIELDS = ['address1', 'address2', 'zip', 'city', 'country', 'postStatus'];
    private const EMAIL_FIELDS = ['email1', 'email2', 'emailStatus'];
    private const PHONE_FIELDS = ['mobilePhone', 'landlinePhone', 'workPhone', 'phoneStatus'];
    private const BIRTHDAY_FIELDS = ['birthday'];
    private const COUPLE_FIELDS = ['coupleCategory'];
    private const MEMBER_FIELDS = ['memberStatusMunicipality', 'memberStatusRegion', 'memberStatusCanton', 'memberStatusCountry', 'memberStatusYoung'];
    private const MEMBERSHIP_DATE_FIELDS = ['membershipStart', 'membershipEnd'];
    private const IGNORE_CONFLICT_FIELDS = ['entryChannel', 'dontUse'];
    
    private const SPECIAL_FIELDS = [
        ...self::RECORD_STATUS_FIELDS,
        ...self::GENDER_FIELDS,
        ...self::ADDRESS_FIELDS,
        ...self::EMAIL_FIELDS,
        ...self::PHONE_FIELDS,
        ...self::BIRTHDAY_FIELDS,
        ...self::COUPLE_FIELDS,
        ...self::MEMBER_FIELDS,
        ...self::MEMBERSHIP_DATE_FIELDS,
        ...self::IGNORE_CONFLICT_FIELDS
    ];
    
    private Member $dst;
    private Member $src;
    
    private array $conflicts;
    
    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly DebtorRepository $debtorRepository
    )
    {
    }
    
    /**
     * @param Member $dst
     * @param Member $src
     *
     * @return Member
     *
     * @throws ClientException
     * @throws GroupNotFoundException
     * @throws InvalidFixedValueException
     * @throws JsonException
     * @throws MemberMergeException
     * @throws MemberNotFoundException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws NoGroupException
     * @throws ValueTypeException
     * @throws WeblingAPIException
     * @throws WeblingFieldMappingConfigException
     */
    public function merge(Member $dst, Member $src): Member
    {
        $this->dst = $dst;
        $this->src = $src;
        $this->conflicts = [];
        
        $this->mergeFields();
        $this->mergeDebtors();
        
        $merged = $this->memberRepository->save($this->dst);
        
        $this->memberRepository->delete($src);
        
        return $merged;
    }
    
    /**
     * @throws MemberUnknownFieldException
     * @throws MemberMergeException
     * @throws JsonException
     */
    private function mergeFields(): void
    {
        foreach ($this->src->getFields() as $key => $srcField) {
            $dstField = $this->dst->getField($key);
            
            if (empty($srcField->getValue())) {
                continue;
            }
            
            if ($dstField->getValue() === $srcField->getValue()) {
                continue;
            }
            
            if ($this->isSpecialField($key)) {
                $this->mergeSpecialField($dstField, $srcField);
            } else {
                $this->mergeRegularField($dstField, $srcField);
            }
        }
        
        if (!empty($this->conflicts)) {
            $this->throwMergeException();
        }
    }
    
    private function isSpecialField(string $key): bool
    {
        return in_array($key, self::SPECIAL_FIELDS);
    }
    
    private function mergeSpecialField(Field $dst, Field $src): void
    {
        $fieldKey = $dst->getKey();
        
        /** @noinspection PhpParamsInspection */
        $merger = match (true) {
            in_array($fieldKey, self::RECORD_STATUS_FIELDS) => new RecordStatusMerger($dst, $src),
            $fieldKey === 'gender' => new GenderMerger($dst, $src),
            in_array($fieldKey, ['salutationFormal', 'salutationInformal']) => new SalutationMerger($dst, $src),
            in_array($fieldKey, self::ADDRESS_FIELDS) => new AddressMerger($dst, $src, $this->dst, $this->src),
            in_array($fieldKey, self::EMAIL_FIELDS) => new EmailMerger($dst, $src, $this->dst, $this->src),
            in_array($fieldKey, self::PHONE_FIELDS) => new PhoneMerger($dst, $src),
            in_array($fieldKey, self::IGNORE_CONFLICT_FIELDS) => new IgnoreConflictMerger($dst, $src),
            in_array($fieldKey, self::BIRTHDAY_FIELDS) => new BirthdayMerger($dst, $src),
            in_array($fieldKey, self::COUPLE_FIELDS) => new CoupleMerger($dst, $src),
            in_array($fieldKey, self::MEMBER_FIELDS) => new MemberStatusMerger($dst, $src),
            in_array($fieldKey, self::MEMBERSHIP_DATE_FIELDS) => new MembershipDateMerger($dst, $src),
        };
        
        if (!$merger->merge()) {
            $this->conflicts[] = $fieldKey;
        }
    }
    
    private function mergeRegularField(Field $dst, Field $src): void
    {
        $merger = new RegularFieldMerger($dst, $src);
        if (!$merger->merge()) {
            $this->conflicts[] = $src->getKey();
        }
    }
    
    /**
     * @throws MemberMergeException
     * @throws JsonException
     */
    private function throwMergeException(): void
    {
        throw new MemberMergeException(
            json_encode([
                'success' => false,
                'conflicts' => $this->conflicts,
                'merged' => [],
                'message' => 'Some merge conflicts must be resolved manually. Nothing merged. See "conflicts". Do not retry.',
            ], JSON_THROW_ON_ERROR)
        );
    }
    
    /**
     * @throws MemberMergeException
     * @throws JsonException
     */
    private function mergeDebtors(): void
    {
        try {
            foreach ($this->src->getDebtorIds() as $debtorId) {
                $debtor = $this->debtorRepository->get($debtorId);
                $debtor->setMemberId($this->dst->id);
                $this->debtorRepository->put($debtor);
            }
        } catch (DebtorException|WeblingAPIException|ClientException $e) {
            throw new MemberMergeException(
                json_encode([
                    'success' => false,
                    'conflicts' => [],
                    'merged' => [],
                    'message' => 'Failed to merge some debtors. Debtors may be partially merged, member fields not merged at all. You may retry. See original message: ' . $e->getMessage(),
                ], JSON_THROW_ON_ERROR)
            );
        } catch (DebtorNotWriteableException) {
            // This is ok. We do not move debtors of a locked period.
            // They will become orphaned, but since locked periods should not be used anymore, this seems ok.
        }
    }
}