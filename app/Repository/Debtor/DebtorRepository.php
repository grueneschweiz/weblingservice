<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 30.11.18
 * Time: 17:31
 */

namespace App\Repository\Debtor;


use App\Exceptions\DebtorException;
use App\Exceptions\DebtorNotWriteableException;
use App\Exceptions\WeblingAPIException;
use App\Repository\Repository;
use Webling\API\ClientException;

class DebtorRepository extends Repository
{
    /**
     * Retrieve debtor from Webling
     *
     * @param int $debtorId
     *
     * @return Debtor
     *
     * @throws DebtorException
     * @throws WeblingAPIException
     * @throws ClientException
     */
    public function get(int $debtorId): Debtor
    {
        $resp = $this->apiGet("debitor/$debtorId");
    
        if ($resp->getStatusCode() !== 200) {
            throw new WeblingAPIException("Get request to Webling failed with status code {$resp->getStatusCode()}");
        }
    
        $debtor = $resp->getData();
        $linkedMemberIds = $debtor['links']['member'] ?? [];
        
        if (count($linkedMemberIds) > 1 ){
            throw new DebtorException("Unprocessable response from Webling: the debtor is linked to multiple members.");
        }
        
        $linkedMemberId = $linkedMemberIds[0] ?? null;
        
        return new Debtor(
            $debtorId,
            $linkedMemberId
        );
    }
    
    /**
     * Update debtor in Webling
     *
     * @param Debtor $debtor
     *
     * @return void
     *
     * @throws WeblingAPIException
     * @throws ClientException
     * @throws DebtorNotWriteableException
     */
    public function put(Debtor $debtor): void {
        $payload = [
            'links' => [
                'member' => [
                    $debtor->getMemberId()
                ],
            ],
        ];
        
        $resp = $this->apiPut("debitor/{$debtor->getId()}", $payload);
    
        $respData = $resp->getData();
        if (is_array($respData)
            && array_key_exists('error', $respData)
            && $this->isDebtorNotWriteableError($respData['error'])
        ) {
            throw new DebtorNotWriteableException();
        }
        
        if ($resp->getStatusCode() !== 204) {
            throw new WeblingAPIException("Put request to Webling failed with status code {$resp->getStatusCode()}: {$resp->getRawData()}");
        }
    }

    private function isDebtorNotWriteableError(string $error): bool
    {
        return 'The object is not writable' === $error
            || preg_match('/The instance with the id \d+ is not writable/', $error);
    }
}
