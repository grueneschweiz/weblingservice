<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 30.11.18
 * Time: 17:32
 */

namespace App\Repository\Debtor;


class Debtor
{
    
    public function __construct(
        private int $debtorId,
        private ?int $memberId,
    )
    {
    }
    
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->debtorId;
    }
    
    /**
     * @param int $debtorId
     */
    public function setId(int $debtorId): void
    {
        $this->debtorId = $debtorId;
    }
    
    /**
     * @return int|null
     */
    public function getMemberId(): ?int
    {
        return $this->memberId;
    }
    
    /**
     * @param int|null $memberId
     */
    public function setMemberId(?int $memberId): void
    {
        $this->memberId = $memberId;
    }
}
