<?php
namespace FedExShippingPro\Admin;
class DiagnosticsPage {
 public function register(): void {
  add_submenu_page('fedex-shipping-pro','Diagnostics','Diagnostics','manage_options','fedex-diagnostics',[$this,'render']);
 }
 public function render(): void {
  echo '<div class="wrap"><h1>Diagnostics</h1>';
  echo '<p>Account: '.esc_html(get_option('fedex_account_number')).'</p>';
  echo '<p>Token Exists: '.(get_option('fedex_access_token')?'YES':'NO').'</p>';
  echo '</div>';
 }
}
