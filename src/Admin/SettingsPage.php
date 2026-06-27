<?php
namespace FedExShippingPro\Admin;

class SettingsPage {

    public const OPTION = 'fedex_shipping_pro_settings';

    public const ACCESS_TOKEN_OPTION = 'fedex_access_token';
    public const ACCESS_TOKEN_EXPIRES_OPTION = 'fedex_access_token_expires_at';

    private string $option = self::OPTION;

    public static function defaults(): array {
        return [
            'environment' => 'sandbox',
            'debug' => 1,
            'account_number' => '',
            'client_id' => '',
            'client_secret' => '',
        ];
    }

    public static function get_settings(): array {
        $settings = get_option(self::OPTION, []);

        if (!is_array($settings)) {
            $settings = [];
        }

        $settings = array_merge(self::defaults(), $settings);

        if ($settings['client_id'] === '') {
            $settings['client_id'] = (string) get_option('fedex_api_key', '');
        }

        if ($settings['client_secret'] === '') {
            $settings['client_secret'] = (string) get_option('fedex_secret_key', '');
        }

        if ($settings['account_number'] === '') {
            $settings['account_number'] = (string) get_option('fedex_account_number', '');
        }

        return $settings;
    }

    public static function get_environment(): string {
        $settings = self::get_settings();
        return $settings['environment'] === 'production' ? 'production' : 'sandbox';
    }

    public static function get_client_id(): string {
        $settings = self::get_settings();
        return (string) $settings['client_id'];
    }

    public static function get_client_secret(): string {
        $settings = self::get_settings();
        return (string) $settings['client_secret'];
    }

    public static function get_account_number(): string {
        $settings = self::get_settings();
        return (string) $settings['account_number'];
    }

    public static function get_access_token(): string {
        $environment = self::get_environment();
        $scoped_token = (string) get_option(self::ACCESS_TOKEN_OPTION . '_' . $environment, '');

        if ($scoped_token !== '') {
            return $scoped_token;
        }

        return (string) get_option(self::ACCESS_TOKEN_OPTION, '');
    }

    public static function update_access_token(string $token): void {
        update_option(self::ACCESS_TOKEN_OPTION . '_' . self::get_environment(), $token);
    }

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
            add_option($this->option,self::get_settings());
        }
    }

    public function render(): void {
        $o=self::get_settings();
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
