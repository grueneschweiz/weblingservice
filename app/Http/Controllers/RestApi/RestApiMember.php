<?php

namespace App\Http\Controllers\RestApi;

use App\Exceptions\BadRequestException;
use App\Exceptions\IllegalFieldUpdateMode;
use App\Repository\Group\GroupRepository;
use App\Repository\Member\MasterDetector;
use App\Repository\Member\Member;
use App\Repository\Member\MemberMatch;
use Illuminate\Http\Request;

/**
 * Class RestApiMember
 *
 * Manages all API resources connected to the Member
 */
class RestApiMember
{
    private const MODE_REPLACE = 'replace';
    private const MODE_REPLACE_EMPTY = 'replaceEmpty';
    private const MODE_APPEND = 'append';
    private const MODE_ADD_IF_NEW = 'addIfNew';
    private const MODE_REMOVE = 'remove';
    
    /**
     * Return a json with the member fields
     *
     * @param $request  - the http Request
     * @param $member_id  - the id of the member that we should get
     * @param  bool  $is_admin  - is the call by an admin resource (i.e. should we return
     *                     all information about the member)
     *
     * @return string  the JSON
     *
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\IllegalArgumentException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \Webling\API\ClientException
     */
    public function getMember(Request $request, $member_id, $is_admin = false)
    {
        ApiHelper::checkIntegerInput($member_id);
        $memberRepo = ApiHelper::createMemberRepo($request->header($key = 'db_key'));
        
        $allowedGroups = ApiHelper::getAllowedGroups($request);
        
        $member = $memberRepo->get($member_id);
        ApiHelper::assertAllowedMember($allowedGroups, $member);
        
        $data = ApiHelper::getMemberAsArray($member, $allowedGroups, $is_admin);
        
        return json_encode($data);
    }
    
    /**
     * Return a json with the member fields of the main record (!) determined
     * by {@link MemberRepository::getMaster()}.
     *
     * @param $request  - the http Request
     * @param $member_id  - the id of the member that we should get
     * @param $group_ids  - string with commaseparated list of ids of root group
     *                     below which the main record should be searched
     *                     {@see MemberRepository::getMaster()}
     * @param  bool  $is_admin  - is the call by an admin resource (i.e. should we return
     *                     all information about the member)
     *
     * @return string  the JSON
     *
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\IllegalArgumentException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \Webling\API\ClientException
     */
    public function getMainMember(Request $request, $member_id, $group_ids = null, $is_admin = false)
    {
        $requestedGroups = $this->getAllowedRequestedGroups($request, $group_ids);
    
        ApiHelper::checkIntegerInput($member_id);
        $memberRepo = ApiHelper::createMemberRepo($request->header($key = 'db_key'));
    
        $member = $memberRepo->getMaster($member_id, $requestedGroups);
    
        $allowedGroups = ApiHelper::getAllowedGroups($request);
        $data = ApiHelper::getMemberAsArray($member, $allowedGroups, $is_admin);
        
        return json_encode($data);
    }
    
    /**
     * Return the group objects of the requested groups or all allowed if none given.
     *
     * Abort with 403 if any are not allowed.
     *
     * @param  Request  $request
     * @param  int[]|null  $group_ids
     * @return \App\Repository\Group\Group[]
     *
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\IllegalArgumentException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \Webling\API\ClientException
     */
    private function getAllowedRequestedGroups(Request $request, $group_ids)
    {
        $allowedGroups = ApiHelper::getAllowedGroups($request);
        
        if ($group_ids) {
            $group_ids = explode(',', $group_ids);
            $requestedGroups = [];
            
            /** @var GroupRepository $groupRepository */
            $groupRepository = ApiHelper::createGroupRepo();
            
            foreach ($group_ids as $groupId) {
                ApiHelper::checkIntegerInput($groupId);
                
                $group = $groupRepository->get((int) $groupId);
                $requestedGroups[(int) $groupId] = $group;
                
                ApiHelper::assertAllowedGroup($allowedGroups, $group);
            }
        } else {
            $requestedGroups = $allowedGroups;
        }
        
        return $requestedGroups;
    }
    
    /**
     * Return a json with the all member that changed since the $revisionId
     *
     * @param $request  - the http Request
     * @param $revisionId  - the id of the revision we want to get changes since
     * @param $limit  - how many records should be retrieved at most
     * @param $offset  - how many records should be skipped
     * @param  bool  $is_admin  - is the call by an admin resource (i.e. should we return
     *                     all information about the member)
     *
     * @return string the JSON
     *
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\IllegalArgumentException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\InvalidRevisionArgumentsException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\RevisionNotFoundException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \Webling\API\ClientException
     */
    public function getChanged(Request $request, $revisionId, $limit, $offset, $is_admin = false)
    {
        $allowedGroups = ApiHelper::getAllowedGroups($request);
        
        ApiHelper::checkIntegerInput($revisionId);
        ApiHelper::checkIntegerInput($limit);
        ApiHelper::checkIntegerInput($offset);
        
        $memberRepo = ApiHelper::createMemberRepo($request->header($key = 'db_key'));
        $memberRepo->setLimit((int) $limit);
        $memberRepo->setOffset((int) $offset);
    
        if (-1 === (int) $revisionId) {
            $members = $memberRepo->getAll($allowedGroups);
        } else {
            $members = $memberRepo->getUpdated($revisionId, $allowedGroups);
        }
        
        $data = [];
        foreach ($members as $id => $member) {
            if (empty($member)) {
                $data[$id] = null;
            } else {
                $data[$id] = ApiHelper::getMemberAsArray($member, $allowedGroups, $is_admin);
            }
        }
        
        return json_encode($data);
    }
    
    /**
     * Update or insert the given member.
     *
     * @param  Request  $request  - the http Request with the member data
     *
     * @return int member id
     *
     * @throws BadRequestException
     * @throws IllegalFieldUpdateMode
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\IllegalArgumentException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\NoGroupException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \Webling\API\ClientException
     */
    public function upsertMember(Request $request)
    {
        $memberData = $this->extractMemberData($request);
        
        // update the given member, if it contains an id
        if (!empty($memberData[Member::KEY_ID])) {
            $idField = $memberData[Member::KEY_ID];
            $memberId = isset($idField['value']) ? $idField['value'] : $idField;
            
            return $this->updateMember($request, $memberId);
        }
        
        $member = new Member();
        $patched = $this->patchMember($request, $member, $memberData, true);
        
        $memberRepo = ApiHelper::createMemberRepo($request->header($key = 'db_key'));
        $match = $memberRepo->findExisting($member, ApiHelper::getAllowedGroups($request));
        
        switch ($match->getStatus()) {
            case MemberMatch::MATCH:
                $matches = $match->getMatches();
                $matchedMember = reset($matches);
                $patched = $this->patchMember($request, $matchedMember, $memberData);
                
                return $memberRepo->save($patched)->id;
    
            case MemberMatch::NO_MATCH:
            case MemberMatch::MULTIPLE_MATCHES:
            case MemberMatch::AMBIGUOUS_MATCH:
                return $memberRepo->save($patched)->id;
            
            default:
                throw new IllegalFieldUpdateMode($match->getStatus().' is not defined');
        }
    }
    
    /**
     * Insert the given member (without any deduplication magic)
     *
     * @param  Request  $request  - the http Request with the member data
     *
     * @return int member id
     *
     * @throws BadRequestException
     * @throws IllegalFieldUpdateMode
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\NoGroupException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \Webling\API\ClientException
     */
    public function insertMember(Request $request): int
    {
        $memberData = $this->extractMemberData($request);
        
        $member = new Member();
        $patched = $this->patchMember($request, $member, $memberData, true);
        
        $memberRepo = ApiHelper::createMemberRepo($request->header($key = 'db_key'));
        
        return $memberRepo->save($patched)->id;
    }
    
    /**
     * Returns the member data form the given request
     *
     * @param  Request  $request
     *
     * @return array
     * @throws BadRequestException
     */
    private function extractMemberData(Request &$request): array
    {
        $memberData = json_decode($request->getContent(), true);
        if (!$memberData) {
            throw new BadRequestException('Missing request content data.');
        }
        
        if (!is_array($memberData)) {
            throw new BadRequestException('Malformed member data.');
        }
        
        return $memberData;
    }
    
    /**
     * Update the given member.
     *
     * @param  Request  $request  - the http Request with the member data
     * @param  number  $memberId
     *
     * @return int member id
     *
     * @throws IllegalFieldUpdateMode
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\IllegalArgumentException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\NoGroupException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \Webling\API\ClientException
     * @throws BadRequestException
     */
    public function updateMember(Request $request, $memberId)
    {
        ApiHelper::checkIntegerInput($memberId);
        $memberRepo = ApiHelper::createMemberRepo($request->header($key = 'db_key'));
        
        $member = $memberRepo->get($memberId);
        ApiHelper::assertAllowedMember(ApiHelper::getAllowedGroups($request), $member);
        
        $memberData = $this->extractMemberData($request);
        $patched = $this->patchMember($request, $member, $memberData);
        
        return $memberRepo->save($patched)->id;
    }
    
    /**
     * Merge $data into $member
     *
     * @param Request $request
     * @param Member $member
     * @param array $data
     * @param bool $forceReplace disrespect the field's mode and use replace mode if true
     *
     * @return Member
     *
     * @throws BadRequestException
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \Webling\API\ClientException
     * @throws IllegalFieldUpdateMode
     */
    private function patchMember(Request $request, Member $member, array $data, bool $forceReplace = false): Member
    {
        foreach ($data as $fieldKey => $field) {
            if (Member::KEY_ID === $fieldKey) {
                continue;
            }
            
            $actions = $this->normalizeFieldData($field);
            if ($forceReplace) {
                $actions = $this->forceModeReplace($actions);
            }
            $this->validateFieldActions($actions, $fieldKey);
            
            if (Member::KEY_GROUPS === $fieldKey) {
                foreach ($actions as $action) {
                    $this->patchGroups($request, $member, $action);
                }
            } else {
                foreach ($actions as $action) {
                    $this->patchField($member, $action, $fieldKey);
                }
            }
        }
        
        return $member;
    }
    
    /**
     * Wraps directly given single actions into an array so it can be treated
     * like fields with multiple actions.
     *
     * Single action example: 'notesCountry' => ['value' => 'newTag', 'mode' => 'append']
     * Multi action example: 'notesCountry' => [
     *   ['value' => 'newTag', 'mode' => 'append'],
     *   ['value' => 'oldTag', 'mode' => 'remove'],
     * ]
     *
     * @param array $fieldData
     * @return array|array[]
     */
    private function normalizeFieldData(array $fieldData)
    {
        if (array_key_exists('value', $fieldData)) {
            return [$fieldData];
        }
        
        return $fieldData;
    }
    
    /**
     * Sets the mode of every action to replace
     *
     * @param array $actions
     * @return array
     */
    private function forceModeReplace(array $actions)
    {
        foreach ($actions as &$action) {
            if (is_array($action)) {
                $action['mode'] = self::MODE_REPLACE;
            }
        }
        
        return $actions;
    }
    
    /**
     * Checks if every action contains a 'mode' and a 'value'
     *
     * @throws BadRequestException
     */
    private function validateFieldActions(array $actions, string $fieldKey): void
    {
        foreach ($actions as $action) {
            if (!is_array($action) || !array_key_exists('mode', $action) || !array_key_exists('value', $action)) {
                throw new BadRequestException("Malformed data in field: $fieldKey");
            }
        }
    }
    
    /**
     * Update the groups of the given member
     *
     * @param  Request  $request
     * @param  Member  $member
     * @param  array  $data  => [ value => [ group ids ], mode => replace/append/remove ]
     *
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \Webling\API\ClientException
     * @throws IllegalFieldUpdateMode
     */
    private function patchGroups(Request &$request, Member &$member, array $data)
    {
        $groups = [];
        $groupRepo = ApiHelper::createGroupRepo();
        $allowedGroups = ApiHelper::getAllowedGroups($request);
    
        foreach ((array) $data['value'] as $group) {
            $g = $groupRepo->get($group);
            ApiHelper::assertAllowedGroup($allowedGroups, $g);
            $groups[] = $g;
        }
        
        switch ($data['mode']) {
            case self::MODE_APPEND:
                $member->addGroups($groups);
                break;
            
            case self::MODE_REPLACE:
                $member->setGroups($groups);
                break;
            
            case self::MODE_REPLACE_EMPTY:
                if (empty($member->getGroupIds())) {
                    $member->setGroups($groups);
                }
                break;
            
            case self::MODE_ADD_IF_NEW:
                if (null === $member->id) {
                    $member->setGroups($groups);
                }
                break;
                
            case self::MODE_REMOVE:
                $member->removeGroups($groups);
                break;
            
            default:
                throw new IllegalFieldUpdateMode("The update mode '{$data['mode']}' for the field '".Member::KEY_GROUPS."' is not supported.");
        }
    }
    
    /**
     * Update the given field of the given member
     *
     * @param  Member  $member
     * @param  array  $data
     * @param  string  $key
     *
     * @throws IllegalFieldUpdateMode
     */
    private function patchField(Member &$member, array $data, string $key)
    {
        $mode = $data['mode'];
        
        switch ($mode) {
            case self::MODE_APPEND:
                if (!method_exists($member->$key, 'append')) {
                    throw new IllegalFieldUpdateMode("The update mode '{$data['mode']}' for the field '$key' is not supported.");
                }
                $member->$key->append($data['value']);
                break;
            
            case self::MODE_REPLACE:
                $member->$key->setValue($data['value']);
                break;
            
            case self::MODE_REPLACE_EMPTY:
                if (empty($member->$key->getValue())) {
                    $member->$key->setValue($data['value']);
                }
                break;
            
            case self::MODE_ADD_IF_NEW:
                if (null === $member->id) {
                    $member->$key->setValue($data['value']);
                }
                break;
    
            case self::MODE_REMOVE:
                if (!method_exists($member->$key, 'remove')) {
                    throw new IllegalFieldUpdateMode("The update mode '{$data['mode']}' for the field '$key' is not supported.");
                }
                $member->$key->remove($data['value']);
                break;
            
            default:
                throw new IllegalFieldUpdateMode("The update mode '{$data['mode']}' for the field '$key' is not supported.");
        }
    }
    
    /**
     * Search, if the given member is already in the database.
     *
     * See chapter 6.3 of the documentation for a detailed flow chart.
     *
     * The response contains a json containing:
     * {
     *   'status': 'match'|'no_match'|'ambiguous'|'multiple'|'error',
     *   'matches': [ members ],
     *   'ratings': { '1234': 11, '1111': 7 }
     * }
     *
     * The ratings use the member id as key and the rating as value.
     *
     * Note: The given member must at least have an email address or
     * first and last name.
     *
     * @param  Request  $request
     * @param  null|array  $group_ids  the groups to look in
     * @return false|string JSON
     * @throws BadRequestException
     * @throws IllegalFieldUpdateMode
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\IllegalArgumentException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \Webling\API\ClientException
     */
    public function matchMember(Request $request, $group_ids = null)
    {
        $memberData = $this->extractMemberData($request);
        $requestedGroups = $this->getAllowedRequestedGroups($request, $group_ids);
        $allowedGroups = ApiHelper::getAllowedGroups($request);
        
        $memberRepo = ApiHelper::createMemberRepo($request->header($key = 'db_key'));
        
        // return the corresponding member, if it contains an id
        if (!empty($memberData[Member::KEY_ID])) {
            $member = $memberRepo->get($memberData[Member::KEY_ID]);
            $data = ApiHelper::getMemberAsArray($member, $allowedGroups);
            
            return json_encode([
                'status' => 'match',
                'matches' => $data,
                'ratings' => [$member->id => MasterDetector::rateMembership($member)],
            ]);
        }
        
        $member = new Member();
        $this->patchMember($request, $member, $memberData, true);
        $match = $memberRepo->findExisting($member, $requestedGroups);
        
        $data = [];
        $ratings = [];
        foreach ($match->getMatches() as $member) {
            $data[] = ApiHelper::getMemberAsArray($member, $allowedGroups);
            $ratings[$member->id] = MasterDetector::rateMembership($member);
        }
        
        switch ($match->getStatus()) {
            case MemberMatch::NO_MATCH:
                $status = 'no_match';
                break;
            
            case MemberMatch::MATCH:
                $status = 'match';
                break;
            
            case MemberMatch::AMBIGUOUS_MATCH:
                $status = 'ambiguous';
                break;
            
            case MemberMatch::MULTIPLE_MATCHES:
                $status = 'multiple';
                break;
            
            default:
                $status = 'error';
        }
        
        return json_encode([
            'status' => $status,
            'matches' => $data,
            'ratings' => $ratings,
        ]);
    }
    
    /**
     * {@see MemberRepository::merge()} for documentation
     *
     * @param Request $request
     * @param string|int|null $dstMemberId
     * @param string|int|null $srcMemberId
     *
     * @return string
     *
     * @throws BadRequestException
     * @throws MemberMergeException
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \App\Exceptions\InvalidFixedValueException
     * @throws \App\Exceptions\MemberNotFoundException
     * @throws \App\Exceptions\MemberUnknownFieldException
     * @throws \App\Exceptions\MultiSelectOverwriteException
     * @throws \App\Exceptions\NoGroupException
     * @throws \App\Exceptions\ValueTypeException
     * @throws \App\Exceptions\WeblingAPIException
     * @throws \App\Exceptions\WeblingFieldMappingConfigException
     * @throws \JsonException
     * @throws \Webling\API\ClientException
     */
    public function mergeMember(Request $request, $dstMemberId, $srcMemberId): string
    {
        $srcMemberId = (int)$srcMemberId;
        if (empty($srcMemberId)) {
            throw new BadRequestException('Missing ID of source member.');
        }
        
        $dstMemberId = (int)$dstMemberId;
        if (empty($dstMemberId)) {
            throw new BadRequestException('Missing ID of destination member.');
        }
        
        $memberRepo = ApiHelper::createMemberRepo($request->header($key = 'db_key'));
        $allowedGroups = ApiHelper::getAllowedGroups($request);
        
        $merged = $memberRepo->merge($dstMemberId, $srcMemberId);
        
        return json_encode([
            'success' => true,
            'conflicts' => [],
            'merged' => ApiHelper::getMemberAsArray($merged, $allowedGroups, true),
            'message' => 'OK'
        ], JSON_THROW_ON_ERROR);
    }
}
