<?php
namespace FedExShippingPro\Admin;
use FedExShippingPro\Rates\FedExRatesService;
class RateTestPage {
 public function register(): void {
  add_submenu_page('fedex-shipping-pro','Rate Test','Rate Test','manage_options','fedex-rate-test',[$this,'render']);
 }
 public function render(): void {
  $defaults = [
   'oc' => 'US',
   'op' => '38104',
   'oa1' => '',
   'oa2' => '',
   'ocity' => '',
   'ost' => '',
   'dc' => 'US',
   'dp' => '33324',
   'w' => '1',
   'l' => '10',
   'wi' => '10',
   'h' => '10'
  ];
  $v = array_merge($defaults, $_POST);
  $service = new FedExRatesService();
  $rates = [];
  $did_submit = isset($_POST['fedex_test_rate']);
  $message = '';
  if($did_submit){
    $package = [
      'origin' => [
        'country' => $this->value($v,'oc'),
        'postcode' => $this->value($v,'op'),
        'address_1' => $this->value($v,'oa1'),
        'address_2' => $this->value($v,'oa2'),
        'city' => $this->value($v,'ocity'),
        'state' => $this->value($v,'ost'),
      ],
      'destination' => [
        'country' => $this->value($v,'dc'),
        'postcode' => $this->value($v,'dp'),
      ],
      'weight' => $this->numeric($v,'w',1),
      'length' => $this->numeric($v,'l',10),
      'width' => $this->numeric($v,'wi',10),
      'height' => $this->numeric($v,'h',10),
    ];
    $rates = $service->getCheckoutRates($package);
    if($rates === []){
      $message = $service->getLastException() ?: 'No FedEx rates were returned.';
    }
  }
  echo '<div class="wrap"><h1>FedEx Rate Test</h1>';
  echo '<form method="post"><table class="form-table">';
  foreach([
   'Origin Country'=>'oc','Origin Postal Code'=>'op',
   'Origin Address 1'=>'oa1','Origin Address 2'=>'oa2',
   'Origin City'=>'ocity','Origin State'=>'ost',
   'Destination Country'=>'dc','Destination Postal Code'=>'dp',
   'Weight (lb)'=>'w','Length'=>'l','Width'=>'wi','Height'=>'h'
  ] as $label=>$key){
   echo '<tr><th>'.$label.'</th><td><input class="regular-text" name="'.$key.'" value="'.esc_attr($v[$key]).'"></td></tr>';
  }
  echo '</table>';
  submit_button("Get Live Rates","primary","fedex_test_rate");
  echo '</form>';
  if($did_submit){
    echo '<h2>Diagnostics</h2>';
    if($message !== ''){
      echo '<div class="notice notice-warning"><p>'.esc_html($message).'</p></div>';
    }
    echo '<table class="widefat striped"><tbody>';
    $this->row('HTTP Status', $service->getHttpStatus() === null ? 'null' : (string) $service->getHttpStatus());
    $this->row('OAuth Status', $service->getOAuthStatus() ?? 'null');
    $this->row('Execution Time', $service->getExecutionTime() === null ? 'null' : $service->getExecutionTime() . ' ms');
    $this->row('Last Exception', $service->getLastException() ?? 'null');
    echo '</tbody></table>';

    echo '<h2>Request JSON</h2><pre style="white-space:pre-wrap">'.esc_html($service->getLastRequestJson() ?? 'null').'</pre>';
    echo '<h2>Response JSON</h2><pre style="white-space:pre-wrap">'.esc_html($service->getLastResponseJson() ?? 'null').'</pre>';

    echo '<h2>Services Returned</h2>';
    echo '<table class="widefat striped"><thead><tr><th>Service</th><th>Service Code</th><th>Currency</th><th>Amount</th><th>Transit Days</th><th>Estimated Delivery</th></tr></thead><tbody>';
    foreach($rates as $r){
      echo '<tr><td>'.esc_html($r['service']).'</td><td>'.esc_html($r['service_code'] ?? '').'</td><td>'.esc_html($r['currency'] ?? '').'</td><td>'.esc_html(number_format((float) $r['amount'],2)).'</td><td>'.esc_html($r['transit_days'] ?? '').'</td><td>'.esc_html($r['estimated_delivery'] ?? $r['delivery_date'] ?? '').'</td></tr>';
    }
    echo '</tbody></table>';
    echo '<h2>FedEx Warnings</h2><pre style="white-space:pre-wrap">'.esc_html(implode("\n", $service->getLastWarnings()) ?: 'None').'</pre>';
    echo '<h2>FedEx Notes</h2><pre style="white-space:pre-wrap">'.esc_html(implode("\n", $service->getLastNotes()) ?: 'None').'</pre>';
  }
  echo '</div>';
 }

 private function value(array $values, string $key): string {
  return isset($values[$key]) ? (string) $values[$key] : '';
 }

 private function numeric(array $values, string $key, float $default): float {
  if(isset($values[$key]) && is_numeric($values[$key])){
   return (float) $values[$key];
  }
  return $default;
 }

 private function row(string $label, string $value): void {
  echo '<tr><th>'.esc_html($label).'</th><td>'.esc_html($value).'</td></tr>';
 }
}
