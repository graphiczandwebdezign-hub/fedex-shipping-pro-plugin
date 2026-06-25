<?php
namespace FedExShippingPro\WooCommerce;

class PackageBuilder {

    public function build(array $package): array {

        return [
            'weight' => 2,
            'origin_zip' => '38116',
            'destination_zip' => $package['destination']['postcode'] ?? ''
        ];
    }
}
