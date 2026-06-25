<?php
namespace FedExShippingPro\Admin;

class SettingsPage {

    private string $option='fedex_shipping_pro_settings';

    public function register(): void {
        add_menu_page(
            'FedEx Shipping Pro',
            'FedEx Shipping Pro',
            'manage_options',
            'fedex-shipping-pro',
            [$this,'render'],
            'dashicons-location-alt'
        );
        add_action('admin_init',[$this,'register_settings']);
    }

    public function register_settings(): void {
        register_setting($this->option,$this->option);

        if(!get_option($this->option)){
            add_option($this->option,[
                'environment'=>'sandbox',
                'debug'=>1,
                'account_number'=>'',
                'client_id'=>'',
                'client_secret'=>''
            ]);
        }
    }

    public function render(): void {
        $o=get_option($this->option,[]);
?>
<div class="wrap">
<h1>FedEx Shipping Pro</h1>
<h2 class="nav-tab-wrapper">
<a class="nav-tab nav-tab-active" href="#">General</a>
<a class="nav-tab" href="#">API</a>
<a class="nav-tab" href="#">Origin</a>
<a class="nav-tab" href="#">Services</a>
<a class="nav-tab" href="#">Packaging</a>
<a class="nav-tab" href="#">Advanced</a>
</h2>

<form method="post" action="options.php">
<?php settings_fields($this->option); ?>

<table class="form-table">
<tr>
<th>Environment</th>
<td>
<label><input type="radio" name="fedex_shipping_pro_settings[environment]" value="sandbox" <?php checked(($o['environment']??'sandbox'),'sandbox');?>/> Sandbox</label><br>
<label><input type="radio" name="fedex_shipping_pro_settings[environment]" value="production" <?php checked(($o['environment']??''),'production');?>/> Production</label>
</td>
</tr>

<tr>
<th>Debug Logging</th>
<td><label><input type="checkbox" name="fedex_shipping_pro_settings[debug]" value="1" <?php checked(!empty($o['debug']));?>/> Enable Debug Logging</label></td>
</tr>

<tr><th>Plugin Version</th><td><strong>0.7.0 Sprint 7A</strong></td></tr>
</table>

<?php submit_button('Save Settings'); ?>
</form>
</div>
<?php
    }
}
