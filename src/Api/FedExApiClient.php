<?php
namespace FedExShippingPro\Api;
class FedExApiClient {
 public function post($endpoint,$payload){
  $response=wp_remote_post($endpoint,[
   'headers'=>[
    'Authorization'=>'Bearer '.get_option('fedex_access_token'),
    'Content-Type'=>'application/json',
    'X-locale'=>'en_US'
   ],
   'body'=>wp_json_encode($payload),
   'timeout'=>60
  ]);
  return [
   'status'=>wp_remote_retrieve_response_code($response),
   'body'=>wp_remote_retrieve_body($response)
  ];
 }
}
