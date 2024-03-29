<?php
/**
 * Created by PhpStorm.
 * User: adrian
 * Date: 25.12.18
 * Time: 18:35
 */

namespace App\Repository\Group;


use App\Exceptions\GroupNotFoundException;
use App\Exceptions\WeblingAPIException;
use RecursiveIteratorIterator;

class GroupIterator extends \RecursiveArrayIterator
{
    
    private $repository;
    private $useCache;
    
    public function __construct(array $groups, GroupRepository $groupRepository, bool $useCache = true)
    {
        parent::__construct($groups);
        
        $this->repository = $groupRepository;
        $this->useCache = $useCache;
    }
    
    /**
     * Creates a new GroupIterator wrapped by a RecursiveIteratorIterator so it can directly be used
     * @param $rootGroup
     * @param GroupRepository $repository
     * @param bool $useCache
     * @return RecursiveIteratorIterator
     */
    public static function createRecursiveGroupIterator($rootGroup, GroupRepository $repository, bool $useCache = true): RecursiveIteratorIterator
    {
        $iterator = new GroupIterator(array($rootGroup), $repository, $useCache);
        return new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
    }
    
    
    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key(): int
    {
        return $this->current()->getId();
    }
    
    
    /**
     * Returns if an iterator can be created for the current entry.
     * @link https://php.net/manual/en/recursiveiterator.haschildren.php
     * @return bool true if the current entry can be iterated over, otherwise returns false.
     * @since 5.1.0
     */
    public function hasChildren(): bool
    {
        return !empty($this->current()->getChildren());
    }
    
    /**
     * Returns an iterator for the current entry.
     * @link https://php.net/manual/en/recursiveiterator.getchildren.php
     * @return GroupIterator An iterator for the current entry.
     * @throws GroupNotFoundException
     * @throws WeblingAPIException
     * @since 5.1.0
     */
    public function getChildren(): GroupIterator
    {
        $childGroups = [];
        foreach ($this->current()->getChildren() as $childId) {
            $group = $this->repository->get($childId, $this->useCache);
            $childGroups[] = $group;
        }
        
        return new GroupIterator($childGroups, $this->repository, $this->useCache);
    }
}