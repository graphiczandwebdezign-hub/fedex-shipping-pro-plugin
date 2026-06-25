<?php
namespace FedExShippingPro\WooCommerce;

use FedExShippingPro\Rates\FedExRatesService;

if (!class_exists('WC_Shipping_Method')) {
    return;
}

class ShippingMethod extends \WC_Shipping_Method {

    public function __construct($instance_id = 0) {

        $this->id = 'fedex_shipping_pro';
        $this->instance_id = absint($instance_id);

        $this->method_title = 'FedEx Shipping';
        $this->method_description = 'FedEx Shipping Pro';
        $this->title = 'FedEx Shipping';

        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
        ];

        $this->init();
        $this->init_instance_settings();

        $this->enabled = $this->get_option('enabled', 'yes');
        $this->title   = $this->get_option('title', 'FedEx Shipping');
    }

    public function init() {
        $this->init_form_fields();
        $this->init_instance_form_fields();
        $this->init_settings();

        add_action(
            'woocommerce_update_options_shipping_' . $this->id,
            [$this, 'process_admin_options']
        );
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => 'Enable',
                'type'    => 'checkbox',
                'default' => 'yes'
            ],
            'title' => [
                'title'   => 'Title',
                'type'    => 'text',
                'default' => 'FedEx Shipping'
            ]
        ];
    }

    public function init_instance_form_fields() {
        $this->instance_form_fields = [

            'enabled' => [
                'title'   => __('Enable', 'fedex-shipping-pro'),
                'type'    => 'checkbox',
                'label'   => __('Enable FedEx Shipping', 'fedex-shipping-pro'),
                'default' => 'yes',
            ],

            'title' => [
                'title'       => __('Method Title', 'fedex-shipping-pro'),
                'type'        => 'text',
                'description' => __('Title shown during checkout', 'fedex-shipping-pro'),
                'default'     => __('FedEx Shipping', 'fedex-shipping-pro'),
                'desc_tip'    => true,
            ],
        ];
    }


    public function process_admin_options()
    {
        parent::process_admin_options();
    }

    public function get_instance_form_fields()
    {
        return $this->instance_form_fields;
    }

    public function generate_settings_html($form_fields = array(), $echo = false)
    {
        return parent::generate_settings_html($form_fields, $echo);
    }

    public function is_available($package = []) {
        return true;
    }

    public function calculate_shipping($package = []) {

        $service = new FedExRatesService();
        $rates = $service->getCheckoutRates($package);

        foreach ($rates as $rate) {
            $this->add_rate([
                'id'    => $this->id . '_' . sanitize_title($rate['service']),
                'label' => $rate['service'],
                'cost'  => $rate['amount']
            ]);
        }
    }
}
