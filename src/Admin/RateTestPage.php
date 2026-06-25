<?php
namespace FedExShippingPro\Admin;
use FedExShippingPro\Rates\FedExRatesService;
class RateTestPage {
 public function register(): void {
  add_submenu_page('fedex-shipping-pro','Rate Test','Rate Test','manage_options','fedex-rate-test',[$this,'render']);
 }
 public function render(): void {
  $defaults=[
   'oc'=>'US','op'=>'38104','dc'=>'US','dp'=>'33324',
   'w'=>'1','l'=>'10','wi'=>'10','h'=>'10'
  ];
  $v=array_merge($defaults,$_POST);
  echo '<div class="wrap"><h1>Live FedEx Rate Test</h1>';
  echo '<form method="post"><table class="form-table">';
  foreach([
   'Origin Country'=>'oc','Origin Postal Code'=>'op',
   'Destination Country'=>'dc','Destination Postal Code'=>'dp',
   'Weight (lb)'=>'w','Length'=>'l','Width'=>'wi','Height'=>'h'
  ] as $label=>$key){
    echo '<tr><th>'.$label.'</th><td><input class="regular-text" name="'.$key.'" value="'.esc_attr($v[$key]).'"></td></tr>';
  }
  echo '</table>';
  submit_button("Get Live Rates","primary","fedex_test_rate");
  echo '</form>';
  if(isset($_POST["fedex_test_rate"])){
    $start=microtime(true);
    $rates=(new FedExRatesService())->getCheckoutRates([]);
    $elapsed=round((microtime(true)-$start)*1000);
    echo '<h2>Results</h2><table class="widefat striped"><thead><tr><th>Service</th><th>Transit</th><th>Price</th></tr></thead><tbody>';
    foreach($rates as $r){
      $t=$r["transit"]??"-";
      echo '<tr><td>'.esc_html($r["service"]).'</td><td>'.esc_html($t).'</td><td>$'.number_format((float)$r["amount"],2).'</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p><strong>Execution Time:</strong> '.$elapsed.' ms</p>';
  }
  echo '</div>';
 }
}
