<?php
/**
 * Created by PhpStorm.
 * User: cyrill.bolliger
 * Date: 2019-03-20
 * Time: 18:17
 */

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

abstract class ClientCommand extends Command
{
    /**
     * @param int $id
     *
     * @return int|mixed
     */
    protected function getClientById(int $id)
    {
        if (0 >= $id) {
            $this->error('<comment>Invalid id:</comment> ' . $id);
            
            return false;
        }
        
        $client = DB::table('oauth_clients')->find($id);
        
        if (empty($client)) {
            $this->error('<comment>No client with id:</comment> ' . $id);
            
            return false;
        }
        
        return $client;
    }
    
    protected function validateGroups(array $groups)
    {
        if (empty($groups)) {
            $this->error('Missing option: You must specify at least one root group. Use --root-group={group_id}');
            
            return false;
        }
        
        foreach ($groups as $group) {
            if (!is_numeric($group) || 0 >= (int)$group) {
                $this->error('<comment>Invalid group id:</comment> ' . $group);
                
                return false;
            }
        }
        
        return true;
    }
}