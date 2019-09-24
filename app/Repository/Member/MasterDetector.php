<?php

namespace App\Repository\Member;

use App\Repository\Group\Group;

/**
 * Find the master record of a given member in Webling somewhere below the given root group.
 *
 * Uses the memberStatus fields, to determine the record where the member is bound most (most memberStatus with
 * member, unconfirmed, sympathiser (in this order)), whereas on member status wins over all status unconfirmed or
 * sympathiser.
 *
 * For equally specified memberStatus the given record is assumed to be the master.
 *
 * Only well defined matches are compared. Ambiguous matches are not considered.
 */
class MasterDetector
{
    const MEMBER_STATUS = 'member';
    const UNCONFIRMED_STATUS = 'unconfirmed';
    const SYMPATHISER_STATUS = 'sympathiser';
    
    /**
     * @var Group[]
     */
    private $rootGroups;
    
    /**
     * @var MemberRepository
     */
    private $memberRepository;
    
    /**
     * MasterDetector constructor.
     *
     * @param MemberRepository $memberRepository
     * @param Group|Group[] $rootGroups
     */
    public function __construct(MemberRepository $memberRepository, $rootGroups)
    {
        if (!is_array($rootGroups)) {
            $rootGroups = [$rootGroups];
        }
        
        $this->rootGroups = $rootGroups;
        $this->memberRepository = $memberRepository;
    }
    
    /**
     * Find similar records to the given one and return the one with the highest rated membership.
     *
     * If no similar ones are found or if there are only ambiguous matches, return the given member.
     * If one similar record has the same rating as the given member, return the given member.
     * If two similar records have an identical but higher rating than the given member, return the first
     * of the similar records.
     *
     * @param Member $member
     *
     * @return Member
     *
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \Webling\API\ClientException
     */
    public function getMaster(Member $member): Member
    {
        $matches = $this->memberRepository->findExisting($member, $this->rootGroups);
        
        // bail early if there are no possible candidates for a match
        $noCandidates = [MemberMatch::NO_MATCH, MemberMatch::MATCH, MemberMatch::AMBIGUOUS_MATCH];
        if (in_array($matches->getStatus(), $noCandidates)) {
            return $member;
        }
        
        // rate memberships and take the one with the highest rating as master.
        // for equal rates, the first one will be considered as master.
        $maxRate = $this->rateMembership($member);
        $master = $member;
        foreach ($matches->getMatches() as $candidate) {
            $rate = $this->rateMembership($candidate);
            if ($rate > $maxRate) {
                $maxRate = $rate;
                $master = $candidate;
            }
        }
        
        return $master;
    }
    
    /**
     * Look at the five member fields (memberStatusCountry, memberStatusCanton, memberStatusRegion,
     * memberStatusMunicipality, memberStatusYoung) and value the 'member' status with 11, 'unconfirmed'
     * with 6 and 'sympathizer' with 1.
     *
     * The weights of 11, 6 and 1 assure that one member status always wins over five sympathizer status
     * and one unconfirmed status wins over five sympathizer status as well. Moreover one member status will
     * dominate one unconfirmed and four sympathizer status.
     *
     * @param Member $member
     *
     * @return int
     */
    private function rateMembership(Member $member): int
    {
        $rating = 0;
        
        $values = [
            $member->memberStatusCountry->getValue(),
            $member->memberStatusCanton->getValue(),
            $member->memberStatusRegion->getValue(),
            $member->memberStatusMunicipality->getValue(),
            $member->memberStatusYoung->getValue()
        ];
        
        foreach ($values as $value) {
            switch ($value) {
                case self::MEMBER_STATUS:
                    $rating += 11;
                    continue;
                case self::UNCONFIRMED_STATUS:
                    $rating += 6;
                    continue;
                case self::SYMPATHISER_STATUS:
                    $rating += 1;
                    continue;
                default:
                    continue;
            }
        }
        
        return $rating;
    }
}