<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\Field;
use App\Repository\Member\Field\FreeField;
use App\Repository\Member\Field\SelectField;

abstract class FieldMerger
{
    public function __construct(
        protected Field $dst,
        protected Field $src,
    )
    {
    }
    
    protected static function fieldsAreSimilar(
        FreeField|SelectField $field1,
        FreeField|SelectField $field2
    ): bool
    {
        // both empty -> similar
        if (self::fieldsAreEmpty($field1, $field2)) {
            return true;
        }
        
        // one empty -> not similar
        if (empty($field1->getValue())
            || empty($field2->getValue())
        ) {
            return false;
        }
        
        $normal1 = self::normalizeString($field1->getValue());
        $normal2 = self::normalizeString($field2->getValue());
        
        return $normal1 === $normal2;
        
    }
    
    protected static function fieldsAreEmpty(Field ...$fields): bool
    {
        $notEmptyFields = array_filter(
            $fields,
            static fn($field) => !empty($field->getValue())
        );
        return empty($notEmptyFields);
    }
    
    protected static function normalizeString(string $str): string
    {
        $str = trim($str);
        $str = mb_strtolower($str);
        return preg_replace('/\s+/', ' ', $str);
    }
    
    abstract public function merge(): bool;
    
    protected function mergeIfDstEmpty(): bool
    {
        if (empty($this->dst->getValue())) {
            $this->dst->setValue($this->src->getValue());
            return true;
        }
        
        return false;
    }
}