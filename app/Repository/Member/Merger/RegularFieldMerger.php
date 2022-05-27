<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FreeField;
use App\Repository\Member\Field\LongTextField;
use App\Repository\Member\Field\MultiSelectField;
use App\Repository\Member\Field\SelectField;

class RegularFieldMerger extends FieldMerger
{
    public function merge(): bool
    {
        if (empty($this->src->getValue())) {
            return true;
        }
        
        if ($this->mergeIfDstEmpty()) {
            return true;
        }
        
        if (
            ($this->dst instanceof FreeField || $this->dst instanceof SelectField)
            && ($this->src instanceof FreeField || $this->src instanceof SelectField)
        ) {
            if ($this->dst instanceof LongTextField) {
                $this->dst->append($this->src->getValue());
                return true;
            }
            
            return self::fieldsAreSimilar($this->dst, $this->src);
        }
        
        if (
            $this->dst instanceof MultiSelectField
            && $this->src instanceof MultiSelectField
        ) {
            $this->dst->append($this->src->getValue());
            return true;
        }
        
        return false;
    }
}