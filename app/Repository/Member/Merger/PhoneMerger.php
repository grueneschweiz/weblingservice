<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FreeField;
use App\Repository\Member\Field\SelectField;

class PhoneMerger extends FieldMerger
{
    public function __construct(
        FreeField|SelectField   $dst,
        FreeField|SelectField   $src,
    )
    {
        parent::__construct($dst, $src);
    }
    
    public function merge(): bool
    {
        if (empty($this->src->getValue())) {
            return true;
        }
        
        if ($this->phoneFieldsAreSimilar()) {
            return true;
        }
        
        if ('unwanted' === $this->src->getValue()
            || 'unwanted' === $this->dst->getValue()
        ) {
            $this->dst->setValue('unwanted');
            return true;
        }
        
        return $this->mergeIfDstEmpty();
    }
    
    private function phoneFieldsAreSimilar(): bool
    {
        /** @noinspection PhpParamsInspection */
        if (self::fieldsAreSimilar($this->dst, $this->src)) {
            return true;
        }
        
        if (empty($this->dst->getValue()) || empty($this->src->getValue())) {
            return false;
        }
        
        $dst = self::normalizePhoneNumber($this->dst->getValue());
        $src = self::normalizePhoneNumber($this->src->getValue());
        
        return $src === $dst;
    }
    
    private static function normalizePhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = self::normalizeString($phoneNumber);
        $phoneNumber = str_replace(' ', '', $phoneNumber);
        return preg_replace("/^((0041|\+41)(?=\d{9})|0(?=[1-9]\d{8}))/", '+41', (string)$phoneNumber);
    }
}