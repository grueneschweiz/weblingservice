<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 12.11.18
 * Time: 14:46
 */

namespace App\Repository\Member;


use App\Exceptions\GroupNotFoundException;
use App\Exceptions\InvalidFixedValueException;
use App\Exceptions\MemberNotFoundException;
use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\MultiSelectOverwriteException;
use App\Exceptions\ValueTypeException;
use App\Exceptions\WeblingAPIException;
use App\Exceptions\WeblingFieldMappingConfigException;
use App\Repository\Group\Group;
use Illuminate\Support\Facades\Log;
use Webling\API\ClientException;

class MemberMatch
{
    /**
     * No member in webling matched the given member
     */
    const NO_MATCH = 0;
    
    /**
     * It's an unambiguous match of exactly one member
     */
    const MATCH = 1;
    
    /**
     * There was a match, but it wasn't unique enough (not enough unambiguous
     * fields matched)
     */
    const AMBIGUOUS_MATCH = 2;
    
    /**
     * There were multiple matches
     */
    const MULTIPLE_MATCHES = 3;
    
    /**
     * The matching status
     *
     * @var int
     */
    private $status;
    
    /**
     * The matches
     *
     * @var array
     */
    private $matches;
    
    /**
     * MemberMatch constructor.
     *
     * @param int $status
     * @param Member[] $matches
     */
    private function __construct(int $status, array $matches)
    {
        $this->status = $status;
        $this->matches = $matches;
    }

    /**
     * Find duplicate members in given group or its subgroups
     *
     * See chapter 6.3 of the documentation for a detailed flow chart.
     *
     * Note: The given member must at least have an email address or
     * first and last name.
     *
     * @param Member $member
     * @param Group[] $rootGroups
     * @param MemberRepository $memberRepository
     *
     * @return MemberMatch
     * @throws ClientException
     * @throws GroupNotFoundException
     * @throws WeblingAPIException
     */
    public static function match(
        Member $member,
        array $rootGroups,
        MemberRepository $memberRepository
    ): MemberMatch
    {
        // find by email and return if found
        if ($member->email1->getValue() || $member->email2->getValue()) {
            $query = self::buildEmailQuery($member);
            $matches = self::matchByQuery($query, $rootGroups, $memberRepository);
            if (!empty($matches) && $member->firstName->getValue()) {
                self::selectByFirstName($matches, $member->firstName->getValue());
            }

            if (count($matches)) {
                return self::create($matches, false);
            }
        }

        // find by phone and return if found
        if ($member->mobilePhone->getValue()) {
            $query = self::buildMobilePhoneQuery($member);
            $matches = self::matchByQuery($query, $rootGroups, $memberRepository);
            if (!empty($matches) && $member->firstName->getValue()) {
                self::selectByFirstName($matches, $member->firstName->getValue());
            }

            if (count($matches)) {
                return self::create($matches, false);
            }
        }

        // don't proceed, if we don't have first and last name
        if (!($member->firstName->getValue() && $member->lastName->getValue())) {
            return new MemberMatch(self::NO_MATCH, []);
        }

        // search webling by first and last name
        $query = self::buildNameQuery($member);
        $matches = self::matchByQuery($query, $rootGroups, $memberRepository);

        if (!empty($matches)) {
            // make sure we filter out all entries where only the beginning of the
            // name was identical, but the given name was no a short name
            self::removeWrongNameMatches($matches, $member->firstName->getValue(),
                $member->lastName->getValue());
        }

        if (!empty($matches) && $member->zip->getValue()) {
            // filter out all results, where the zip didn't match
            self::removeWrongZipMatches($matches, $member->zip->getValue());
            
            return self::create($matches, false);
        }

        // if there is more information available in the record, we create an ambiguous match
        if (!empty($matches) && self::hasAdditionalInformation($member)) {
            return self::create($matches, true);
        }

        return new MemberMatch(self::NO_MATCH, []);
    }

    /**
     * Check if the member has a phone number or address assigned
     *
     * @param Member $member
     *
     * @return bool
     */
    private static function hasAdditionalInformation(Member $member): bool {
        return  $member->mobilePhone->getValue() ||
                $member->landlinePhone->getValue() ||
                $member->workPhone->getValue() ||
                $member->address1->getValue() ||
                $member->address2->getValue();
    }

    /**
     * Return webling query to find members by email
     *
     * @param Member $member
     *
     * @return string
     */
    private static function buildEmailQuery(Member $member): string
    {
        // as webling compares casesensitive, transform everything to lower case.

        $email1 = mb_strtolower(self::escape($member->email1->getWeblingValue()));
        $email2 = mb_strtolower(self::escape($member->email2->getWeblingValue()));

        $query = [];
        if ($member->email1->getWeblingValue()) {
            $query[] = "(LOWER(`{$member->email1->getWeblingKey()}`) = '$email1' OR LOWER(`{$member->email2->getWeblingKey()}`) = '$email1')";
        }

        if ($member->email2->getWeblingValue()) {
            $query[] = "(LOWER(`{$member->email1->getWeblingKey()}`) = '$email2' OR LOWER(`{$member->email2->getWeblingKey()}`) = '$email2')";
        }

        return implode(' OR ', $query);
    }

    /**
     * Return webling query to find members by mobile phone
     *
     * @param Member $member
     *
     * @return string
     */
    private static function buildMobilePhoneQuery(Member $member): string
    {
        $mobilePhone = self::normalizePhoneNumber($member->mobilePhone->getWeblingValue());

        $query = [];
        if ($member->mobilePhone->getWeblingValue()) {
            $query[] = "`{$member->mobilePhone->getWeblingKey()}` = '$mobilePhone'";
        }

        return implode(' OR ', $query);
    }

    /**
     * Return members that matched the given query and log exceptions that
     * should not occur here.
     *
     * @param string $query
     * @param Group[] $rootGroups
     * @param MemberRepository $memberRepository
     *
     * @return Member[]
     *
     * @throws ClientException
     * @throws GroupNotFoundException
     * @throws WeblingAPIException
     */
    private static function matchByQuery(
        string $query,
        array $rootGroups,
        MemberRepository $memberRepository
    ): array
    {
        try {
            return $memberRepository->find($query, $rootGroups);
        } catch (InvalidFixedValueException
        | MemberUnknownFieldException
        | MultiSelectOverwriteException
        | ValueTypeException
        | WeblingFieldMappingConfigException $e) {
            Log::error($e->getFile() . ':' . $e->getLine() . "\n" . $e->getMessage() . $e->getTraceAsString(),
                ['Query' => $query, 'Root Groups' => $rootGroups]);
            
            return [];
        } catch (MemberNotFoundException $e) {
            Log::debug($e->getFile() . ':' . $e->getLine() . "\n" . $e->getMessage() . $e->getTraceAsString(),
                ['Query' => $query, 'Root Groups' => $rootGroups]);
            
            return [];
        }
    }
    
    /**
     * Remove entries of the matches where the first name doesn't match
     *
     * The comparison is not case sensitive. If the matches don't contain a
     * first name, they will still be part of the result.
     *
     * @param Member[] $matches
     * @param string $firstName
     */
    private static function selectByFirstName(array &$matches, string $firstName)
    {
        // remove matches with different first name
        foreach ($matches as $idx => &$match) {
            if ($match->firstName->getValue()) {
                if (!self::isShortNameOf($firstName, $match->firstName->getValue())
                    && !self::isShortNameOf($match->firstName->getValue(), $firstName)) {
                    unset($matches[$idx]);
                }
            }
        }
    }
    
    /**
     * Compare names (case insensitive). If they are not equal, test if the
     * additional characters are separated either by a hyphen or a space.
     *
     * @param string $shortName
     * @param string $fullName
     *
     * @return bool
     */
    private static function isShortNameOf(string $shortName, string $fullName)
    {
        if (0 === strcasecmp($shortName, $fullName)) {
            return true;
        }
        
        $shortName = preg_quote($shortName, '/');
        
        if (preg_match("/^{$shortName}[- ]/i", $fullName)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Transform matches into a member match object
     *
     * @param Member[] $matches
     * @param bool $ambiguous
     *
     * @return MemberMatch
     */
    private static function create(array $matches, bool $ambiguous): MemberMatch
    {
        switch (count($matches)) {
            case 0:
                $status = self::NO_MATCH;
                break;
            
            case 1:
                $status = $ambiguous ? self::AMBIGUOUS_MATCH : self::MATCH;
                break;
            
            default:
                $status = self::MULTIPLE_MATCHES;
        }
        
        // make sure the matches are strictly ordered (no holes from unset)
        $matches = array_values($matches);
        
        return new MemberMatch($status, $matches);
    }
    
    /**
     * Return webling query to find members by first and last name
     *
     * @param Member $member
     *
     * @return string
     */
    private static function buildNameQuery(Member $member): string
    {
        // webling's FILTER seems to be caseINsensitive, so dont bother
        
        $firstName = self::escape($member->firstName->getWeblingValue());
        $lastName = self::escape($member->lastName->getWeblingValue());
        
        $query = [];
        if ($member->firstName->getWeblingValue()) {
            $query[] = "`{$member->firstName->getWeblingKey()}` FILTER '$firstName'";
        }
        
        if ($member->lastName->getWeblingValue()) {
            $query[] = "`{$member->lastName->getWeblingKey()}` FILTER '$lastName'";
        }
        
        return implode(' AND ', $query);
    }
    
    /**
     * Remove members where trailing characters of name are not separated by
     * either a space or a hyphen.
     *
     * There are two reasons for this function:
     * - In online forms, people tend to not write there full names (if they have multiple)
     * - Weblings FILTER is a 'starts with' function, that doesn't allow any further checks
     *
     * @param Member[] $matches
     * @param string $firstName
     * @param string $lastName
     */
    private static function removeWrongNameMatches(array &$matches, string $firstName, string $lastName)
    {
        foreach ($matches as $idx => $match) {
            if (!self::isShortNameOf($firstName, $match->firstName->getValue())
                && !self::isShortNameOf($match->firstName->getValue(), $firstName)) {
                unset($matches[$idx]);
            }
            if (!self::isShortNameOf($lastName, $match->lastName->getValue())
                && !self::isShortNameOf($match->lastName->getValue(), $lastName)) {
                unset($matches[$idx]);
            }
        }
    }
    
    /**
     * Compare zip code and remove the entries where the zip doesn't match
     *
     * @param Member[] $matches
     * @param string $zip
     */
    private static function removeWrongZipMatches(array &$matches, string $zip)
    {
        $zip = abs((int)filter_var($zip, FILTER_SANITIZE_NUMBER_INT));
        foreach ($matches as $idx => $match) {
            if ($match->zip->getValue()) {
                $matchZip = abs((int)filter_var($match->zip->getValue(), FILTER_SANITIZE_NUMBER_INT));
                if ($matchZip != $zip) {
                    unset($matches[$idx]);
                }
            }
        }
    }

    /**
     * Normalize a phone number by removing non-digits and standardizing format.
     *
     * @param string $phoneNumber
     * @return string
     */
    private static function normalizePhoneNumber(string $phoneNumber): string
    {
        return preg_replace('/\D/', '', str_replace('+41', '0', $phoneNumber));
    }

    /**
     * Escape single quotes
     *
     * @param string|null $string
     * @return string|null
     */
    private static function escape(?string $string): ?string
    {
        if (null === $string) {
            return null;
        }
        
        return str_replace("'", "\'", $string);
    }
    
    /**
     * Return the matches status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }
    
    /**
     * Return the matches
     *
     * @return Member[] empty on no match
     */
    public function getMatches(): array
    {
        return $this->matches;
    }
    
    /**
     * Return the number of matches
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->matches);
    }
}
