<?php

namespace App\Repository\Group;


use App\Exceptions\WeblingAPIException;
use App\Repository\Member\Member;

class Group
{
    /**
     * Group constructor.
     * @param string $jsonData Group-Data as JSON-String
     *
     * @throws WeblingAPIException
     */
    public function __construct(string $jsonData)
    {
        $data = json_decode($jsonData);
        if(json_last_error() == JSON_ERROR_NONE) {
            if(isset($data->title)) {
                $this->name = $data->title;
            }

            if(isset($data->children)) {
                if(isset($data->children->membergroup)) {
                    $this->children = $data->children->membergroup;
                }
                if(isset($data->children->member)) {
                    $this->members = $data->children->member;
                }
            }

            if(isset($data->parents) && isset($data->parents[0])) {
                $this->parent = $data->parents[0];
            }

            //todo: calclulate root path already here or do it later (lazy)
            $this->rootPath = $this->calculateRootPath();
        } else {
            throw new WeblingAPIException("Invalid JSON from WeblingAPI. JSON_ERROR: ".json_last_error());
        }

    }

    /**
     * Name of the Group
     * @var string
     */
    private $name;

    /**
     * Parent Group
     * @var Group|null null if there is no parent (= group is at the root)
     */
    private $parent = null;

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
     * @var Group[]
     */
    private $rootPath = null;

    /**
     * Returns the members of this group and all subgroups
     * @return Member[]
     */
    public function getAllMembers() {
        //ToDo
    }

    /**
     * Calculates the root path
     * @return Group[]
     */
    private function calculateRootPath() {
        //ToDo
        if($this->parent == null) {
            return [];
        }

        //ToDo: is there already an instance of GroupRepository?
        $groupRepository = new GroupRepository();
        $parentObject = $groupRepository.get($this->parent);

        $this->rootPath = $parentObject->getRootPath();
        $this->rootPath[] = $this->parent;
    }

    public function getRootPath() {
        if($this->rootPath == null) {
            $this->calculateRootPath();
        }

        return $this->rootPath;
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
     * @return Group|null
     */
    public function getParent(): ?Group
    {
        return $this->parent;
    }
}