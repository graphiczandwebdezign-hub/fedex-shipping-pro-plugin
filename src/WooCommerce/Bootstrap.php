<?php
namespace FedExShippingPro\WooCommerce;

class Bootstrap {

    public function register(): void {

        add_action('woocommerce_shipping_init', function () {
            require_once __DIR__ . '/ShippingMethod.php';
        });

        add_filter('woocommerce_shipping_methods', function ($methods) {
            $methods['fedex_shipping_pro'] = ShippingMethod::class;
            return $methods;
        });
    }
}
