<?php
namespace FedExShippingPro\Admin;
class TokenInspectorPage {
 public function register(): void {
  add_submenu_page('fedex-shipping-pro','Token Inspector','Token Inspector','manage_options','fedex-token',[$this,'render']);
 }
 public function render(): void {
  $token = SettingsPage::get_access_token();
  echo '<div class="wrap"><h1>Token Inspector</h1><pre>'.esc_html($token !== '' ? $token : 'No token').'</pre></div>';
 }
}
