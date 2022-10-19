<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\Field;
use App\Repository\Member\Field\FreeField;
use App\Repository\Member\Field\SelectField;
use App\Repository\Member\Field\TextField;
use App\Repository\Member\Member;

class AddressMerger extends FieldMerger
{
    public function __construct(
        Field          $dst,
        Field          $src,
        private Member $dstMember,
        private Member $srcMember
    )
    {
        parent::__construct($dst, $src);
    }
    
    public function merge(): bool
    {
        $srcAddress1 = $this->srcMember->address1;
        $srcAddress2 = $this->srcMember->address2;
        $srcZip = $this->srcMember->zip;
        $srcCity = $this->srcMember->city;
        $srcCountry = $this->srcMember->country;
        $srcPostStatus = $this->srcMember->postStatus;
        
        $dstAddress1 = $this->dstMember->address1;
        $dstAddress2 = $this->dstMember->address2;
        $dstZip = $this->dstMember->zip;
        $dstCity = $this->dstMember->city;
        $dstCountry = $this->dstMember->country;
        $dstPostStatus = $this->dstMember->postStatus;
        
        // do nothing if src address is empty
        if (self::isAddressEmpty($this->srcMember)) {
            return true;
        }
        
        // do nothing if dst === src
        if ($this->wholeAddressIsSimilar()
            && ($dstCountry->getValue() === $srcCountry->getValue() || empty($srcCountry->getValue()))
        ) {
            return true;
        }
        
        // use src address if dst address is empty
        if (self::isAddressEmpty($this->dstMember)) {
            $dstAddress1->setValue($srcAddress1->getValue());
            $dstAddress2->setValue($srcAddress2->getValue());
            $dstZip->setValue($srcZip->getValue());
            $dstCity->setValue($srcCity->getValue());
            $dstCountry->setValue($srcCountry->getValue());
            if ('unwanted' !== $dstPostStatus->getValue()) {
                $dstPostStatus->setValue($srcPostStatus->getValue());
            }
            return true;
        }
        
        // use src address if present and active and dst address is invalid
        // and src !== dst
        if ('invalid' === $dstPostStatus->getValue()
            && 'active' === $srcPostStatus->getValue()
            && !empty($srcZip->getValue())
            && !empty($srcCity->getValue())
            && (!empty($srcAddress1->getValue()) || !empty($srcAddress2->getValue()))
            && !$this->wholeAddressIsSimilar()
        ) {
            $dstAddress1->setValue($srcAddress1->getValue());
            $dstAddress2->setValue($srcAddress2->getValue());
            $dstZip->setValue($srcZip->getValue());
            $dstCity->setValue($srcCity->getValue());
            $dstCountry->setValue($srcCountry->getValue());
            $dstPostStatus->setValue($srcPostStatus->getValue());
            return true;
        }
        
        // complete address if src is not invalid
        if ('invalid' !== $srcPostStatus->getValue()) {
            $updated = false;
            
            // address 2
            if (empty($dstAddress2->getValue())) {
                // add only if address 1 is similar or empty
                if (
                    empty($dstAddress1->getValue())
                    || self::addressLineIsSimilar($srcAddress1, $dstAddress1)
                ) {
                    // add only, if same value is not already in address 1
                    if (!self::addressLineIsSimilar($srcAddress2, $dstAddress1)) {
                        $dstAddress2->setValue($srcAddress2->getValue());
                        $updated = true;
                    }
                }
            }
            
            // address 1
            if (empty($dstAddress1->getValue())) {
                // add only, if same value is not already in address 2
                if (!self::addressLineIsSimilar($srcAddress1, $dstAddress2)) {
                    $dstAddress1->setValue($srcAddress1->getValue());
                    $updated = true;
                }
            }
            
            // zip
            if (empty($dstZip->getValue())) {
                // add only, if city is similar or empty
                if (
                    empty($dstCity->getValue())
                    || self::fieldsAreSimilar($srcCity, $dstCity)
                ) {
                    $dstZip->setValue($srcZip->getValue());
                    $updated = true;
                }
            }
            
            
            // city
            if (empty($dstCity->getValue())) {
                // add only, if zip is similar or empty
                if (
                    empty($dstZip->getValue())
                    || self::fieldsAreSimilar($srcZip, $dstZip)
                ) {
                    $dstCity->setValue($srcCity->getValue());
                    $updated = true;
                }
            }
            
            // post status
            if ($updated && 'unwanted' !== $dstPostStatus->getValue()) {
                $dstPostStatus->setValue($srcPostStatus->getValue());
            }
            
            // country
            if (empty($dstCountry->getValue())) {
                $dstCountry->setValue($srcCountry->getValue());
                $fieldKey = $this->src->getKey();
                if ('country' === $fieldKey || 'postStatus' === $fieldKey) {
                    $updated = true;
                }
            }
            
            if ($updated) {
                return true;
            }
        }
        
        return false;
    }
    
    private static function isAddressEmpty(Member $member): bool
    {
        return self::fieldsAreEmpty(
            $member->address1,
            $member->address2,
            $member->zip,
            $member->city
        );
    }
    
    private function wholeAddressIsSimilar(): bool
    {
        $src = $this->srcMember;
        $dst = $this->dstMember;
        
        return self::addressLineIsSimilar($src->address1, $dst->address1)
            && self::addressLineIsSimilar($src->address2, $dst->address2)
            && self::fieldsAreSimilar($src->zip, $dst->zip)
            && self::fieldsAreSimilar($src->city, $dst->city)
            && self::fieldsAreSimilarOrOneIsEmpty($src->country, $dst->country);
    }
    
    private static function addressLineIsSimilar(
        TextField $addrField1,
        TextField $addrField2
    ): bool
    {
        $value1 = $addrField1->getValue();
        $value2 = $addrField2->getValue();
        
        if ($value1 === $value2) {
            return true;
        }
        
        if (!$value1 || !$value2) {
            return false;
        }
        
        $clean = static function (string $str): string {
            $str = self::normalizeString($str);
            $str = preg_replace('/[-.,;_]/', '', $str);
            return preg_replace('/[\'‘’´]/u', "'", $str);
        };
        
        $clean1 = $clean($value1);
        $clean2 = $clean($value2);
        
        if ($clean1 === $clean2) {
            return true;
        }
        
        $num1 = self::findAddressNumber($clean1);
        $num2 = self::findAddressNumber($clean2);
        
        
        // if it is a PO box address then
        // check if either one has no number (considered similar)
        // or both numbers are equal
        if (self::isPOBox($clean1) && self::isPOBox($clean2)) {
            return $num1 === $num2
                || !$num1
                || !$num2;
        }
        
        // different street numbers -> address not similar
        if ($num1 !== $num2) {
            return false;
        }
        
        $removeNumber = static function (string $str, null|string $num): string {
            if (!$num) {
                return $str;
            }
            
            $str = str_replace($num, '', $str);
            return trim($str);
        };
        
        $normal1 = self::removeWordStreet($removeNumber($clean1, $num1));
        $normal2 = self::removeWordStreet($removeNumber($clean2, $num2));
        
        return $normal1 === $normal2;
    }
    
    private static function findAddressNumber(string $str): ?string
    {
        // if starts with number (plus maybe up to three letters)
        if (preg_match('/^\d+\w{0,3}\b/u', $str, $matches)) {
            return $matches[0];
        }
        
        // if ends with number (plus maybe up to three letters)
        if (preg_match('/(?<=\s)\d+\w{0,3}$/u', $str, $matches)) {
            return $matches[0];
        }
        
        return null;
    }
    
    private static function isPOBox(string $str): bool
    {
        $patterns = [
            "^(postfach|postf\.?|pf\.?)(\s\d*)?$",
            "^(case postale|cp\.?)(\s\d*)?$",
            "^(bo(î|i)te postale|bp\.?)(\s\d*)?$",
            "^(casella postale|cp\.?)(\s\d*)?$",
            "^(post office box|p\.?\s?o\.?(\s?box)?)(\s\d*)?$",
        ];
        
        $pattern = '/' . implode('|', $patterns) . '/ui';
        
        return 1 === preg_match($pattern, $str);
    }
    
    private static function removeWordStreet(string $str): string
    {
        $frenchSuffix = '((\s(((des|du|de la)(?=\b))|((de l|l)(\'|‘|’|´)(?=\w))))|\s)';
        
        $patterns = [
            "\b(chemin|ch\.?)$frenchSuffix",
            "\b(rue|r\.?)$frenchSuffix",
            "\b(route|rte\.?)$frenchSuffix",
            "\b(avenue|av\.?)$frenchSuffix",
            "\b(boulevard|boul\.?|bd\.?)$frenchSuffix",
            "\b(place|pl\.?)$frenchSuffix",
            "\bruelle$frenchSuffix",
            "\bquai$frenchSuffix",
            "(?<=\w)(strasse|str\.?)(?=\b|\s)",
            "(?<=\w)(gasse|gässlein)\b",
            "(?<=\w)weg\b",
            "(?<=\w)platz\b",
        ];
        
        $pattern = '/' . implode('|', $patterns) . '/ui';
        
        $str = preg_replace($pattern, '', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return trim($str);
    }
    
    private static function fieldsAreSimilarOrOneIsEmpty(
        FreeField|SelectField $field1,
        FreeField|SelectField $field2
    ): bool
    {
        if (empty($field1->getValue())
            || empty($field2->getValue())
        ) {
            return true;
        }
        
        return self::fieldsAreSimilar($field1, $field2);
    }
}