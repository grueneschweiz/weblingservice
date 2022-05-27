<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\SelectField;

class MemberStatusMerger extends FieldMerger
{
    public function __construct(
        SelectField $dst,
        SelectField $src,
    )
    {
        parent::__construct($dst, $src);
    }
    
    public function merge(): bool
    {
        // src empty -> do nothing
        if (empty($this->src->getValue())) {
            return true;
        }
        
        // dst empty -> merge
        if ($this->mergeIfDstEmpty()) {
            return true;
        }
        
        // src === dst -> do nothing
        if ($this->dst->getValue() === $this->src->getValue()) {
            return true;
        }
        
        // take the value with the higher rating
        // @see self::rate
        $dstRating = self::rate($this->dst->getValue());
        $srcRating = self::rate($this->src->getValue());
        
        if ($srcRating > $dstRating) {
            $this->dst->setValue($this->src->getValue());
        }
        
        return true;
    }
    
    private static function rate(string $status): int
    {
        return match ($status) {
            'notMember' => 0,
            'sympathiser' => 1,
            'unconfirmed' => 2,
            'member' => 3,
            'resigned' => 4,
            'expelled' => 5,
        };
    }
}