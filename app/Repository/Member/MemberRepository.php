<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 14.10.18
 * Time: 20:30
 */


namespace App\Repository\Member;


use App\Exceptions\GroupNotFoundException;
use App\Exceptions\InvalidFixedValueException;
use App\Exceptions\InvalidRevisionArgumentsException;
use App\Exceptions\MemberMergeException;
use App\Exceptions\MemberNotFoundException;
use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\MultiSelectOverwriteException;
use App\Exceptions\NoGroupException;
use App\Exceptions\RevisionNotFoundException;
use App\Exceptions\ValueTypeException;
use App\Exceptions\WeblingAPIException;
use App\Exceptions\WeblingFieldMappingConfigException;
use App\Repository\Debtor\DebtorRepository;
use App\Repository\Group\Group;
use App\Repository\Group\GroupRepository;
use App\Repository\Member\Field\Field;
use App\Repository\Member\Merger\MemberMerger;
use App\Repository\Repository;
use App\Repository\Revision\RevisionRepository;
use Webling\API\ClientException;

class MemberRepository extends Repository
{
    /**
     * The maximum of members that should be queried in one turn.
     *
     * If more members are queried, it is split up in multiple requests.
     */
    const QUERY_MEMBER_MAX = 100;
    
    /**
     * The maximum members that should be processed at any get request
     *
     * 0 for no limit
     *
     * @var int
     */
    private $limit = 0;
    
    /**
     * The number of members that should be skipped (used in conjunction with limit)
     *
     * @var int
     */
    private $offset = 0;
    
    /**
     * Set the maximum of members that should be process at any get request
     *
     * Set to 0 for no limit
     *
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }
    
    /**
     * Set the number of records that should be skipped (use in conjunction with self::setLimit())
     *
     * @param int $offset
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }
    
    /**
     * Find the master record of a given member (or member id) in Webling somewhere below the given root group.
     *
     * @param int|Member $input id or member instance
     * @param Group[] $rootGroups
     *
     * @return Member
     *
     * @throws ClientException
     * @throws GroupNotFoundException
     * @throws InvalidFixedValueException
     * @throws MemberNotFoundException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws ValueTypeException
     * @throws WeblingAPIException
     * @throws WeblingFieldMappingConfigException
     *
     * @see MasterDetector for detailed description.
     */
    public function getMaster($input, array $rootGroups): Member
    {
        if ($input instanceof Member) {
            $member = $input;
        } else {
            $member = $this->get($input);
        }
        
        $masterDetector = new MasterDetector($this, $rootGroups);
        
        return $masterDetector->getMaster($member);
    }
    
    /**
     * Get member from webling by id
     *
     * @param int $id
     *
     * @return Member
     *
     * @throws ClientException on connection error
     * @throws InvalidFixedValueException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws ValueTypeException
     * @throws WeblingFieldMappingConfigException
     * @throws MemberNotFoundException
     * @throws WeblingAPIException
     * @throws GroupNotFoundException
     *
     * @see https://gruenesandbox.webling.ch/api#header-error-status-codes
     */
    public function get(int $id): Member
    {
        $result = $this->getMultiple([$id], 1);
        if (null === $result[$id]) {
            throw new MemberNotFoundException("Member with id '$id' not found in Webling.");
        }
        
        return $result[$id];
    }
    
    /**
     * Get multiple members by id.
     *
     * If more than $membersPerRequest ids are queried, Webling is queried
     * multiple times with at most members $membersPerRequest per request.
     *
     * @param array $memberIds the member ids to fetch
     * @param int $membersPerRequest the maximum number of members to get per
     * request.
     *
     * @return Member[] Array with the member ids as keys and the members as
     * values. If the member was not found, the value is NULL.
     *
     * @throws ClientException
     * @throws InvalidFixedValueException
     * @throws MemberNotFoundException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws ValueTypeException
     * @throws WeblingAPIException
     * @throws WeblingFieldMappingConfigException
     * @throws GroupNotFoundException
     */
    private function getMultiple(array $memberIds, int $membersPerRequest): array
    {
        $blocks = array_chunk($memberIds, $membersPerRequest);
        
        $members = [];
        foreach ($blocks as $block) {
            $ids = implode(',', $block);
            
            $resp = $this->apiGet("member/$ids");
            
            if ($resp->getStatusCode() === 200) {
                $newMembers = $this->getMembersFromWeblingPayload($resp->getData(), $block);
                $members += $newMembers;
            } else if ($resp->getStatusCode() === 404) {
                /**
                 * Since Webling returns a 404 even if only one id isn't present,
                 * we have have to narrow it down until we've got the failing
                 * ones.
                 *
                 * We do this by recursively halving the input, to minimize the
                 * amount of requests needed.
                 */
                
                // base case
                if (1 === count($block)) {
                    $members += [$block[0] => null];
                    continue;
                }
                
                // recursive cases
                $recursiveMembersPerRequest = ($membersPerRequest % 2 == 1) ? ($membersPerRequest + 1) / 2 : $membersPerRequest / 2;
                $newMembers = $this->getMultiple($block, $recursiveMembersPerRequest);
                $members += $newMembers;
            } else {
                throw new WeblingAPIException("Get request to Webling failed: {$resp->getRawData()}", $resp->getStatusCode());
            }
        }
        
        return $members;
    }
    
    /**
     * Convert the payload of a webling member response into an array of members
     *
     * @param array $payload the data from the Webling response
     * @param int[] $ids the webling ids that were requested
     *
     * @return Member[] with the webling ids as key and the member as value
     *
     * @throws ClientException
     * @throws InvalidFixedValueException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws ValueTypeException
     * @throws WeblingAPIException
     * @throws WeblingFieldMappingConfigException
     * @throws GroupNotFoundException
     */
    private function getMembersFromWeblingPayload(array $payload, array $ids): array
    {
        /*
         * Normalize webling payload
         *
         * Put single member response payload into array, so it has the same
         * form as a multi member response payload.
         */
        if (1 === count($ids)) {
            $payload['id'] = $ids[0];
            $payload = [$payload];
        }
        
        $members = [];
        foreach ($payload as $memberData) {
            $fieldData = $memberData['properties'];
            $groups = $this->getGroups($memberData['parents']);
            $id = $memberData['id'];
            $debtorIds = $memberData['links']['debitor'] ?? [];
            
            $members[$id] = new Member($fieldData, $id, $groups, true, $debtorIds);
        }
        
        return $members;
    }
    
    /**
     * Convert group ids in array into Groups
     *
     * @param int[] $groupIds
     *
     * @return Group[]
     *
     * @throws ClientException
     * @throws WeblingAPIException
     * @throws GroupNotFoundException
     */
    private function getGroups(array $groupIds): array
    {
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        
        $groups = [];
        foreach ($groupIds as $groupId) {
            $groups[] = $groupRepository->get($groupId);
        }
        
        return $groups;
    }
    
    /**
     * Return array of members that have changed since the given revision
     *
     * @param int $revisionId
     * @param Group[] $rootGroups
     *
     * @return Member[]? The array keys hold the member id, while the value
     * holds the member. If the member was deleted, the value is NULL.
     *
     * @throws ClientException
     * @throws InvalidFixedValueException
     * @throws RevisionNotFoundException
     * @throws MemberNotFoundException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws ValueTypeException
     * @throws WeblingAPIException
     * @throws WeblingFieldMappingConfigException
     * @throws InvalidRevisionArgumentsException
     * @throws GroupNotFoundException
     *
     * @see https://gruenesandbox.webling.ch/api#replicate
     */
    public function getUpdated(int $revisionId, array $rootGroups = []): array
    {
        $repository = new RevisionRepository($this->api_key, $this->api_url);
        $revision = $repository->get($revisionId);
        
        $members = $this->getMultiplePaged($revision->getMemberIds());
        
        if (empty($rootGroups)) {
            return $members;
        }
        
        return $this->filterByRootGroups($members, $rootGroups);
    }
    
    /**
     * Get multiple members by id, paged by $this->limit and $this->offset
     *
     * @param array $memberIds the member ids to fetch
     * @param int $membersPerRequest the maximum number of members to get per
     * request.
     *
     * @return Member[] Array with the member ids as keys and the members as
     * values. If the member was not found, the value is NULL.
     *
     * @throws ClientException
     * @throws InvalidFixedValueException
     * @throws MemberNotFoundException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws ValueTypeException
     * @throws WeblingAPIException
     * @throws WeblingFieldMappingConfigException
     * @throws GroupNotFoundException
     */
    private function getMultiplePaged(array $memberIds, int $membersPerRequest = self::QUERY_MEMBER_MAX): array
    {
        $count = count($memberIds);
        sort($memberIds, SORT_NUMERIC);
        
        if ($this->limit > 0 && $count > $this->limit) {
            $memberIds = array_slice($memberIds, $this->offset, $this->limit);
        }
        
        if ($count < $this->offset) {
            return [];
        }
        
        return $this->getMultiple($memberIds, $membersPerRequest);
    }
    
    /**
     * Filter the given members so only members below the given root groups are returned.
     *
     * Note: Member ids of deleted members will always stay in the array.
     *
     * @param Member[] $unfilteredMembers
     * @param Group[] $rootGroups
     *
     * @return Member[]
     *
     * @throws ClientException
     * @throws GroupNotFoundException
     * @throws WeblingAPIException
     */
    private function filterByRootGroups(array $unfilteredMembers, array $rootGroups): array
    {
        $filtered = [];
        foreach ($unfilteredMembers as $key => &$member) {
            if (null === $member) {
                $filtered[$key] = null;
                continue;
            }
            
            foreach ($rootGroups as &$rootGroup) {
                if ($member->isDescendantOf($rootGroup)) {
                    $filtered[$key] = &$member;
                    continue 2;
                }
            }
        }
        
        return $filtered;
    }
    
    /**
     * @param Group[] $rootGroups
     *
     * @return array
     * @throws ClientException
     * @throws GroupNotFoundException
     * @throws InvalidFixedValueException
     * @throws MemberNotFoundException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws ValueTypeException
     * @throws WeblingAPIException
     * @throws WeblingFieldMappingConfigException
     */
    public function getAll(array $rootGroups = []): array
    {
        return $this->find('', $rootGroups);
    }
    
    /**
     * Find members using a webling query string.
     *
     * Use the query syntax as documented by webling (without the '?filter=').
     * If no query string is provided, all members are returned.
     *
     * Note: The query string must not be encoded. Use the Webling field names
     * and values.
     *
     * @param string $query the query string in the webling syntax
     * @param Group[] $rootGroups the groups to search below, all members if left empty
     *
     * @return Member[]
     *
     * @throws ClientException
     * @throws InvalidFixedValueException
     * @throws MemberNotFoundException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws ValueTypeException
     * @throws WeblingAPIException
     * @throws WeblingFieldMappingConfigException
     * @throws GroupNotFoundException
     *
     * @see https://gruenesandbox.webling.ch/api#header-query-language
     */
    public function find(string $query, array $rootGroups = []): array
    {
        if (!empty($query)) {
            $resp = $this->apiGet("member/?filter=$query");
        } else {
            $resp = $this->apiGet('member');
        }
        
        if ($resp->getStatusCode() !== 200) {
            throw new WeblingAPIException("Get request to Webling failed: {$resp->getRawData()}", $resp->getStatusCode());
        }
    
        if (!is_array($resp->getData()) || !array_key_exists('objects', $resp->getData())) {
            throw new WeblingAPIException("Malformed answer from Webling. Array key 'objects' not present. Raw response: {$resp->getRawData()}", 502);
        }
        
        $ids = $resp->getData()['objects'];
        if (empty($ids)) {
            return [];
        }
        
        $members = $this->getMultiplePaged($ids);
        
        if (empty($rootGroups)) {
            return $members;
        }
        
        /** @var Member $member */
        foreach ($members as $idx => &$member) {
            /**
             * remove not found members from search results
             * @see getMultiple()
             */
            if (empty($member)) {
                unset($members[$idx]);
            }
        }
        
        return $this->filterByRootGroups($members, $rootGroups);
    }
    
    /**
     * Save member in Webling.
     *
     * @param Member $member
     *
     * @return Member On insert, it contains the id after saving.
     *
     * @throws ClientException on connection error
     * @throws InvalidFixedValueException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws ValueTypeException
     * @throws WeblingFieldMappingConfigException
     * @throws MemberNotFoundException
     * @throws WeblingAPIException
     * @throws GroupNotFoundException
     * @throws NoGroupException
     *
     * @see https://gruenesandbox.webling.ch/api#member-member-list-post
     * @see https://gruenesandbox.webling.ch/api#member-member-put
     */
    public function save(Member $member): Member
    {
        // make sure we do have any groups, else webling isn't happy
        if (!$member->groups) {
            throw new NoGroupException('To save a member, it must have at least one group.');
        }
        
        // only save dirty fields
        $dirtyFields = $member->getDirtyFields();
        
        // get array of fields formed for the webling api
        $fields = $this->makeWeblingFieldArray($dirtyFields);
        
        // get array of groups formed for the webling api
        $groups = array_map(function (Group $group) {
            return $group->getId();
        }, $member->groups);
        
        // bring data into the form, webling wants
        $data = [
            'properties' => $fields,
            'parents' => $groups
        ];
        
        $id = $member->id;
        
        if ($id) {
            // update
            if ($data) { // only send request, if data has changed
                $resp = $this->apiPut("member/$id", $data);
                if ($resp->getStatusCode() !== 204) {
                    throw new WeblingAPIException("Put request to Webling failed: {$resp->getRawData()}", $resp->getStatusCode());
                }
            }
            
        } else {
            // create
            $resp = $this->apiPost('member', $data);
            if ($resp->getStatusCode() !== 201) {
                throw new WeblingAPIException("Post request to Webling failed: {$resp->getRawData()}", $resp->getStatusCode());
            }
            $id = $resp->getData();
        }
        
        return $this->get($id);
    }
    
    /**
     * Transform fields into an array the webling api understands
     *
     * @param Field[] $fields
     *
     * @return array
     */
    private function makeWeblingFieldArray(array $fields): array
    {
        $apiData = [];
        
        foreach ($fields as $field) {
            $apiData[$field->getWeblingKey()] = $field->getWeblingValue();
        }
        
        return $apiData;
    }
    
    /**
     * Check if the given member does already exists in Webling somewhere below
     * the given root group and return a MemberMatch object.
     *
     * @param Member $member
     * @param Group[] $rootGroups
     *
     * @return MemberMatch
     *
     * @throws ClientException
     * @throws GroupNotFoundException
     * @throws WeblingAPIException
     */
    public function findExisting(Member $member, array $rootGroups): MemberMatch
    {
        return MemberMatch::match($member, $rootGroups, $this);
    }
    
    /**
     * Delete member in Webling.
     *
     * Note: Think twice, if you want to delete this member. There might be some
     * accounting data left over without a linked member.
     *
     * @param int|Member $input id or member instance
     *
     * @throws WeblingAPIException
     * @throws ClientException
     */
    public function delete($input)
    {
        $id = $input instanceof Member ? $input->id : $input;
        
        $data = $this->apiDelete("member/$id");
        
        if ($data->getStatusCode() !== 204) {
            throw new WeblingAPIException("Delete request to Webling failed {$data->getRawData()}", $data->getStatusCode());
        }
    }
    
    /**
     * Merge src member data into dst member
     *
     * The member fields of the member with $srcId are merged
     * into the member with $dstId. The debtors of src are reassociated
     * with the dst member. The dst member is deleted on success, but
     * retained on error.
     *
     * @see MemberMerger::merge()        The merge process in detail.
     * @see MemberMerger::mergeFields()  How the individual fields are merged.
     * @see MemberMerger::mergeDebtors() How the debtors are merged.
     *
     * @param int $dstId  the id of the dst member
     * @param int $srcId  the id of the src member
     *
     * @return Member  the dst member with the merged data from src
     *
     * @throws ClientException
     * @throws GroupNotFoundException
     * @throws InvalidFixedValueException
     * @throws MemberNotFoundException
     * @throws MemberUnknownFieldException
     * @throws MultiSelectOverwriteException
     * @throws NoGroupException
     * @throws ValueTypeException
     * @throws WeblingAPIException
     * @throws WeblingFieldMappingConfigException
     * @throws MemberMergeException  If the member fields could not be merged automatically.
     * @throws \JsonException
     */
    public function merge(int $dstId, int $srcId): Member
    {
        $members = $this->getMultiple([$dstId, $srcId], 2);
        $dst = $members[$dstId] ?? null;
        $src = $members[$srcId] ?? null;
        
        if ($src === null) {
            throw new MemberNotFoundException("Source member not found in Webling.");
        }
        if ($dst === null) {
            throw new MemberNotFoundException("Destination member not found in Webling.");
        }
        
        $merger = new MemberMerger($this, new DebtorRepository(config('app.webling_finance_admin_api_key')));
        return $merger->merge($dst, $src);
    }
}
