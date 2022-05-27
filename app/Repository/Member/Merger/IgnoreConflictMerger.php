<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FreeField;
use App\Repository\Member\Field\SelectField;

class IgnoreConflictMerger extends FieldMerger
{
    public function __construct(
        FreeField|SelectField $dst,
        FreeField|SelectField $src,
    )
    {
        parent::__construct($dst, $src);
    }
    
    public function merge(): bool
    {
        $this->mergeIfDstEmpty();
        
        return true;
    }
}