<?php
namespace FedExShippingPro\Admin;
use FedExShippingPro\Auth\FedExAuthService;
class OAuthTestPage {
 public function register(): void {
  add_submenu_page('fedex-shipping-pro','OAuth Test','OAuth Test','manage_options','fedex-oauth',[$this,'render']);
 }
 public function render(): void {
  echo '<div class="wrap"><h1>OAuth Test</h1>';
  if(isset($_POST['fedex_test_oauth'])){
    echo '<pre>'; print_r((new FedExAuthService())->requestToken()); echo '</pre>';
  }
  echo '<form method="post"><button class="button button-primary" name="fedex_test_oauth">Test OAuth Connection</button></form></div>';
 }
}
