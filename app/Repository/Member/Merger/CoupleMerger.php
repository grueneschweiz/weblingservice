<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

class CoupleMerger extends FieldMerger
{
    
    public function merge(): bool
    {
        if (null === $this->src->getValue()) {
            return true;
        }
        
        if ($this->mergeIfDstEmpty()) {
            return true;
        }
        
        if ('single' === $this->src->getValue()) {
            return true;
        }
    
        return $this->dst->getValue() === $this->src->getValue();
    }
}