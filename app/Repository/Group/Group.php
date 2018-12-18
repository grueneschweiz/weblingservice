<?php

namespace App\Repository\Group;


use App\Exceptions\WeblingAPIException;
use App\Repository\Member\Member;

class Group implements \JsonSerializable
{
    /**
     * Group id
     * @var int
     */
    private $id;

    /**
     * Name of the Group
     * @var string
     */
    private $name;

    /**
     * Parent Group
     * @var int|null null if there is no parent (= group is at the root)
     */
    private $parent;

    /**
     * Subgroups/Children
     * @var Group[]
     */
    private $children;

    /**
     * Direct group members, without members of subgroups
     * @var Member[]
     */
    private $members;

    /**
     * @var int[]
     */
    private $rootPath = null;

    /**
     * Returns the members of this group and all subgroups
     * @return Member[]
     */
    public function getAllMembers() {
        //ToDo
        return [];
    }

    /**
     * Calculates the root path
     * @param $groupRepository GroupRepository
     * @return int[]
     * @throws WeblingAPIException
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \Webling\API\ClientException
     */
    public function calculateRootPath($groupRepository): array
    {
        //ToDo
        if($this->parent === null) {
            $this->rootPath = [];
        } else {
            $parentObject = $groupRepository->get($this->parent);

            $this->rootPath = $parentObject->getRootPath($groupRepository);
            $this->rootPath[] = $this->parent;
        }

        return $this->rootPath;
    }

    /**
     * @param GroupRepository $groupRepository
     * @return int[]
     * @throws WeblingAPIException
     * @throws \App\Exceptions\GroupNotFoundException
     * @throws \Webling\API\ClientException
     */
    public function getRootPath(GroupRepository $groupRepository): array
    {
        if($this->rootPath == null) {
            $this->calculateRootPath($groupRepository);
        }

        return $this->rootPath;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param int|null $parent
     */
    public function setParent(?int $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @param Group[] $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    /**
     * @param Member[] $members
     */
    public function setMembers(array $members): void
    {
        $this->members = $members;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Group[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return Member[]
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    /**
     * @return int|null
     */
    public function getParent(): ?int
    {
        return $this->parent;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $array =  get_object_vars($this);
        foreach ($array as $key => $value) {
            if($value == null) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}