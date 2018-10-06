<?php

namespace App\Http\Controllers\RestApi;

class RestApiMember
{
  public function getMember($member_id)
  {
    $api = new \Webling\API\Client(config('app.webling_base_url'), config('app.webling_api_key')); //TODO maybe use a factory here?
    $varuable = $api->get('member/' . $member_id);
    if ($varuable && $varuable->getStatusCode() == '200') {
      $dataArray = $varuable->getData();
      $data = [
        //TODO map properties to own json
        'properties' => [
          'Vorname / prénom' => $dataArray['properties']['Vorname / prénom'],
          'Name / nom' =>  $dataArray['properties']['Name / nom'],
        ]
      ];
      return json_encode($data);

  // return //json_encode($dataArray);
  //        'properties: ' . json_encode($dataArray['properties']) . PHP_EOL
  //        . 'readonly: ' . $readonly . PHP_EOL
  //        . 'children: ' . json_encode($dataArray['children']) . PHP_EOL
  //        . 'parents: ' . json_encode($dataArray['parents']) . PHP_EOL
  //        . 'links: ' . json_encode($dataArray['links'])
  //        ;
    } else {
      //TODO how to handle errors?
      return 'Error: Server returned error';
    }
  }

//TODO implement more resources
  public function postMember($member_id)
  {
  }
}
