<?php
namespace FedExShippingPro\WooCommerce;

use FedExShippingPro\Admin\SettingsPage;

class PackageBuilder {

    public function build(array $package): array {
        $destination = $package['destination'] ?? [];
        $origin = $package['origin'] ?? [];
        $settings = SettingsPage::get_settings();
        $store_origin = $this->get_store_origin();
        $dimensions = $this->get_dimensions($package);

        return [
            'account_number' => SettingsPage::get_account_number(),
            'weight' => $this->get_weight($package),
            'origin_country' => $origin['country'] ?? ($package['origin_country'] ?? ($settings['origin_country'] ?? $store_origin['country'])),
            'origin_state' => $origin['state'] ?? ($package['origin_state'] ?? ($settings['origin_state'] ?? $store_origin['state'])),
            'origin_city' => $origin['city'] ?? ($package['origin_city'] ?? ($settings['origin_city'] ?? $store_origin['city'])),
            'origin_zip' => $origin['postcode'] ?? ($package['origin_zip'] ?? ($settings['origin_zip'] ?? $store_origin['postcode'])),
            'origin_address_1' => $origin['address_1'] ?? ($package['origin_address_1'] ?? ($settings['origin_address_1'] ?? $store_origin['address_1'])),
            'origin_address_2' => $origin['address_2'] ?? ($package['origin_address_2'] ?? ($settings['origin_address_2'] ?? $store_origin['address_2'])),
            'destination_country' => $destination['country'] ?? '',
            'destination_state' => $destination['state'] ?? '',
            'destination_city' => $destination['city'] ?? '',
            'destination_zip' => $destination['postcode'] ?? '',
            'destination_address_1' => $destination['address_1'] ?? '',
            'destination_address_2' => $destination['address_2'] ?? '',
            'length' => $dimensions['length'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'weight_unit' => 'LB',
            'dimension_unit' => 'IN',
            'pickup_type' => 'DROPOFF_AT_FEDEX_LOCATION',
            'packaging_type' => 'YOUR_PACKAGING',
            'rate_request_types' => ['ACCOUNT', 'LIST'],
            'carrier_codes' => ['FDXE', 'FDXG'],
            'return_transit_times' => true,
            'shipment_date' => gmdate('Y-m-d'),
            'environment' => SettingsPage::get_environment(),
        ];
    }

    private function get_weight(array $package): float {
        if (isset($package['weight']) && is_numeric($package['weight'])) {
            return (float) $package['weight'];
        }

        $weight = 0.0;

        foreach (($package['contents'] ?? []) as $item) {
            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 1;
            $product = $item['data'] ?? null;

            if (is_object($product) && method_exists($product, 'get_weight')) {
                $weight += ((float) $product->get_weight()) * max(1, $quantity);
            }
        }

        return $weight;
    }

    private function get_dimensions(array $package): array {
        $dimensions = [
            'length' => $package['length'] ?? null,
            'width' => $package['width'] ?? null,
            'height' => $package['height'] ?? null,
        ];

        foreach (($package['contents'] ?? []) as $item) {
            $product = $item['data'] ?? null;

            if (!is_object($product)) {
                continue;
            }

            foreach (['length', 'width', 'height'] as $dimension) {
                $method = 'get_' . $dimension;

                if ($dimensions[$dimension] === null && method_exists($product, $method)) {
                    $value = $product->{$method}();
                    $dimensions[$dimension] = is_numeric($value) ? (float) $value : null;
                }
            }
        }

        return $dimensions;
    }

    private function get_store_origin(): array {
        $base_country = '';
        $base_state = '';
        $default_country = (string) get_option('woocommerce_default_country', '');

        if (strpos($default_country, ':') !== false) {
            [$base_country, $base_state] = array_pad(explode(':', $default_country, 2), 2, '');
        } else {
            $base_country = $default_country;
        }

        return [
            'country' => $base_country !== '' ? $base_country : 'US',
            'state' => $base_state,
            'city' => (string) get_option('woocommerce_store_city', ''),
            'postcode' => (string) get_option('woocommerce_store_postcode', ''),
            'address_1' => (string) get_option('woocommerce_store_address', ''),
            'address_2' => (string) get_option('woocommerce_store_address_2', ''),
        ];
    }
}
