<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 30.11.18
 * Time: 17:32
 */

namespace App\Repository\Revision;


use App\Exceptions\InvalidRevisionArgumentsException;

class Revision
{
    /**
     * The webling ids of the members, where some member properties have
     * changed between the queried revision and the current revision.
     *
     * @var int[]
     */
    private $memberIds;
    
    /**
     * The most recent revision id
     *
     * @var int
     */
    private $currentRevisionId;
    
    /**
     * The revision id that was used to get the member ids
     *
     * @var int
     */
    private $queriedRevisionId;
    
    /**
     * Revision constructor.
     *
     * @param int $queriedRevisionId
     * @param int $currentRevisionId
     * @param int[] $memberIds
     *
     * @throws InvalidRevisionArgumentsException
     */
    public function __construct(
        int $queriedRevisionId,
        int $currentRevisionId,
        array $memberIds
    )
    {
        if ($queriedRevisionId > $currentRevisionId) {
            throw new InvalidRevisionArgumentsException('The queried revision id must not exceed the current revision id.');
        }
        
        $this->memberIds = $memberIds;
        $this->currentRevisionId = $currentRevisionId;
        $this->queriedRevisionId = $queriedRevisionId;
    }
    
    /**
     * @return int[]
     */
    public function getMemberIds(): array
    {
        return $this->memberIds;
    }
    
    /**
     * @return int
     */
    public function getCurrentRevisionId(): int
    {
        return $this->currentRevisionId;
    }
    
    /**
     * @return int
     */
    public function getQueriedRevisionId(): int
    {
        return $this->queriedRevisionId;
    }
}
