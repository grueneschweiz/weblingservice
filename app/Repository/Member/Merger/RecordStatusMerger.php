<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

class RecordStatusMerger extends FieldMerger
{
    
    public function merge(): bool
    {
        if (null === $this->src->getValue()) {
            return true;
        }
        
        if ($this->mergeIfDstEmpty()) {
            return true;
        }
    
        if ('died' === $this->dst->getValue() || 'died' === $this->src->getValue()) {
            $this->dst->setValue('died');
            return true;
        }
    
        return $this->dst->getValue() === $this->src->getValue();
    }
}