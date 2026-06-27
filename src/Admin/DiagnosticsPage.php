<?php
namespace FedExShippingPro\Admin;
class DiagnosticsPage {
 public function register(): void {
  add_submenu_page('fedex-shipping-pro','Diagnostics','Diagnostics','manage_options','fedex-diagnostics',[$this,'render']);
 }
 public function render(): void {
  echo '<div class="wrap"><h1>Diagnostics</h1><table class="widefat striped"><tbody>';
  echo '<tr><th>Account</th><td>'.esc_html(SettingsPage::get_account_number()).'</td></tr>';
  echo '<tr><th>Token Exists</th><td>'.(SettingsPage::get_access_token()?'YES':'NO').'</td></tr>';
  echo '</tbody></table></div>';
 }
}
