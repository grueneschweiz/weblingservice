<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

class MembershipDateMerger extends FieldMerger
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
        
        // on conflict, take the earlier one for membership start
        // and the later one for membership end. this way it works
        // well with the young greens.
        if ($src < $dst && $this->src->getKey() === 'membershipStart') {
            $this->dst->setValue($this->src->getValue());
        }
        if ($src > $dst && $this->src->getKey() === 'membershipEnd') {
            $this->dst->setValue($this->src->getValue());
        }
        
        return true;
    }
}