<?php
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FreeField;
use App\Repository\Member\Field\SelectField;
use App\Repository\Member\Member;

class EmailMerger extends FieldMerger
{
    public function __construct(
        FreeField|SelectField $dst,
        FreeField|SelectField $src,
        private   readonly Member $dstMember,
        private   readonly Member $srcMember
    )
    {
        parent::__construct($dst, $src);
    }
    
    public function merge(): bool
    {
        /** @noinspection PhpParamsInspection */
        if (self::fieldsAreSimilar($this->dst, $this->src)) {
            return true;
        }
        
        $emailValues = [
            'dst1' => $this->dstMember->email1->getValue(),
            'dst2' => $this->dstMember->email2->getValue(),
            'src1' => $this->srcMember->email1->getValue(),
            'src2' => $this->srcMember->email2->getValue(),
        ];
        
        $emails = array_filter($emailValues, static fn($email) => !empty($email));
        $normalizedEmails = array_map([static::class, 'normalizeString'], $emails);
        $uniqueEmails = array_unique($normalizedEmails);
        
        $dstEmailStatus = $this->dstMember->emailStatus->getValue();
        $srcEmailStatus = $this->srcMember->emailStatus->getValue();
        
        if ('invalid' === $dstEmailStatus) {
            unset(
                $uniqueEmails['dst1'],
                $uniqueEmails['dst2']
            );
        }
    
        if ('invalid' === $srcEmailStatus) {
            unset(
                $uniqueEmails['src1'],
                $uniqueEmails['src2']
            );
        }
        
        $emailCount = count($uniqueEmails);
        
        if ($emailCount > 2) {
            return false;
        }
        
        if ($emailCount > 1) {
            $this->dstMember->email2->setValue(array_values($uniqueEmails)[1]);
        }
        
        if ($emailCount > 0) {
            $this->dstMember->email1->setValue(array_values($uniqueEmails)[0]);
            
            if ('invalid' === $dstEmailStatus) {
                $this->dstMember->emailStatus->setValue(
                    $this->srcMember->emailStatus->getValue()
                );
            }
        }
        
        $unwanted = [];
        if ('unwanted' === $dstEmailStatus) {
            $unwanted = ['dst1', 'dst2'];
        }
        if ('unwanted' === $srcEmailStatus) {
            $unwanted = [...$unwanted, 'src1', 'src2'];
        }
        
        $unwanted = array_intersect(array_keys($emails), $unwanted);
        
        if (!empty($unwanted)) {
            $this->dstMember->emailStatus->setValue('unwanted');
        }
        
        if (empty($this->dstMember->emailStatus->getValue())){
            $this->dstMember->emailStatus->setValue($srcEmailStatus);
        }
        
        return true;
    }
}