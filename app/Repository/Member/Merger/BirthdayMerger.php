<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

class BirthdayMerger extends FieldMerger
{
    public function merge(): bool
    {
        if (null === $this->src->getValue()) {
            return true;
        }
        
        if ($this->mergeIfDstEmpty()) {
            return true;
        }
        
        $src = date_create_immutable_from_format('Y-m-d', $this->src->getValue());
        $dst = date_create_immutable_from_format('Y-m-d', $this->dst->getValue());
        
        // don't merge, if one of the dates could not be parsed
        if (!$src || !$dst) {
            return false;
        }
        
        // if one is 1970-01-01, take the other one
        if ($dst->format('Y-m-d') === '1970-01-01') {
            $this->dst->setValue($this->src->getValue());
            return true;
        }
        if ($src->format('Y-m-d') === '1970-01-01') {
            return true;
        }
        
        // if the year is equal but one is on the first of january
        // and the other one not, take the one that is not
        $srcY = $src->format('Y');
        $dstY = $dst->format('Y');
        $srcMd = $src->format('m-d');
        $dstMd = $dst->format('m-d');
        
        if ($srcMd === '01-01' && $dstMd !== '01-01'
            && $srcY === $dstY
        ) {
            return true;
        }
        if ($dstMd === '01-01' && $srcMd !== '01-01'
            && $srcY === $dstY
        ) {
            $this->dst->setValue($this->src->getValue());
            return true;
        }
    
        return $this->dst->getValue() === $this->src->getValue();
    }
}