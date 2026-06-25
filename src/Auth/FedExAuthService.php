<?php
namespace FedExShippingPro\Auth;
class FedExAuthService {
 public function requestToken(): array {
  $response = wp_remote_post('https://apis-sandbox.fedex.com/oauth/token',[
   'headers'=>['Content-Type'=>'application/x-www-form-urlencoded'],
   'body'=>[
    'grant_type'=>'client_credentials',
    'client_id'=>get_option('fedex_api_key'),
    'client_secret'=>get_option('fedex_secret_key')
   ]
  ]);
  $body=json_decode(wp_remote_retrieve_body($response),true);
  if(!empty($body['access_token'])){
   update_option('fedex_access_token',$body['access_token']);
   return ['success'=>true,'expires_in'=>$body['expires_in']??0];
  }
  return ['success'=>false,'response'=>$body];
 }
}
