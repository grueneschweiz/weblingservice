<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

class SalutationMerger extends FieldMerger
{
    
    public function merge(): bool
    {
        if (null === $this->src->getValue()) {
            return true;
        }
    
        if ($this->mergeIfDstEmpty()) {
            return true;
        }
    
        if (str_starts_with($this->src->getValue(), 'n')) {
            return true;
        }
    
        if (str_starts_with($this->dst->getValue(), 'n')) {
            $this->dst->setValue($this->src->getValue());
        }
    
        return $this->dst->getValue() === $this->src->getValue();
    }
}