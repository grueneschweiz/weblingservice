<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

class GenderMerger extends FieldMerger
{
    
    public function merge(): bool
    {
        if (null === $this->src->getValue()) {
            return true;
        }
        
        if ($this->mergeIfDstEmpty()) {
            return true;
        }
        
        if ('n' === $this->src->getValue()) {
            return true;
        }
    
        if ('n' === $this->dst->getValue()) {
            $this->dst->setValue($this->src->getValue());
        }
    
        return $this->dst->getValue() === $this->src->getValue();
    }
}