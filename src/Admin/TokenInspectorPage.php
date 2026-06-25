<?php
namespace FedExShippingPro\Admin;
class TokenInspectorPage {
 public function register(): void {
  add_submenu_page('fedex-shipping-pro','Token Inspector','Token Inspector','manage_options','fedex-token',[$this,'render']);
 }
 public function render(): void {
  echo '<div class="wrap"><h1>Token Inspector</h1><pre>'.esc_html(get_option('fedex_access_token','No token')).'</pre></div>';
 }
}
