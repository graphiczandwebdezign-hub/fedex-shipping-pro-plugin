<?php
namespace FedExShippingPro\Rates;

use FedExShippingPro\Admin\SettingsPage;

class RateRequestBuilder {

    private const DEFAULT_PICKUP_TYPE = 'DROPOFF_AT_FEDEX_LOCATION';
    private const DEFAULT_PACKAGING_TYPE = 'YOUR_PACKAGING';
    private const DEFAULT_WEIGHT_UNIT = 'LB';
    private const DEFAULT_DIMENSION_UNIT = 'IN';

    public function build(array $package): array {
        $account_number = $this->requiredString($package, 'account_number', 'FedEx account number is required to build a rate request.');
        $destination_country = $this->requiredString($package, 'destination_country', 'Destination country is required to build a rate request.');
        $destination_zip = $this->requiredString($package, 'destination_zip', 'Destination postal code is required to build a rate request.');
        $origin_country = $this->requiredString($package, 'origin_country', 'Origin country is required to build a rate request.');
        $origin_zip = $this->requiredString($package, 'origin_zip', 'Origin postal code is required to build a rate request.');
        $weight = $this->positiveFloat($package['weight'] ?? null, 'Package weight must be greater than zero to build a rate request.');

        $line_items = $this->buildPackageLineItems($package, $weight);

        $this->validateEnvironment($package['environment'] ?? SettingsPage::get_environment());

        return [
            'accountNumber' => [
                'value' => $account_number,
            ],
            'requestedShipment' => [
                'shipper' => [
                    'address' => $this->buildAddress($package, 'origin'),
                ],
                'recipient' => [
                    'address' => $this->buildAddress($package, 'destination'),
                ],
                'pickupType' => $this->stringValue($package['pickup_type'] ?? null, self::DEFAULT_PICKUP_TYPE),
                'packagingType' => $this->stringValue($package['packaging_type'] ?? null, self::DEFAULT_PACKAGING_TYPE),
                'rateRequestType' => $this->stringList($package['rate_request_types'] ?? null, ['ACCOUNT', 'LIST']),
                'shipDateStamp' => $this->shipmentDate($package['shipment_date'] ?? null),
                'requestedPackageLineItems' => $line_items,
            ],
            'carrierCodes' => $this->stringList($package['carrier_codes'] ?? null, ['FDXE', 'FDXG']),
            'returnTransitTimes' => $this->booleanValue($package['return_transit_times'] ?? null, true),
        ];
    }

    private function buildPackageLineItems(array $package, float $weight): array {
        $items = $package['package_line_items'] ?? [];

        if (!is_array($items) || $items === []) {
            $items = [[
                'weight' => $weight,
                'length' => $package['length'] ?? null,
                'width' => $package['width'] ?? null,
                'height' => $package['height'] ?? null,
            ]];
        }

        $line_items = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $item_weight = $this->positiveFloat($item['weight'] ?? $weight, 'Package line item weight must be greater than zero.');
            $line_item = [
                'weight' => [
                    'units' => $this->stringValue($item['weight_unit'] ?? $package['weight_unit'] ?? null, self::DEFAULT_WEIGHT_UNIT),
                    'value' => $item_weight,
                ],
            ];

            $line_item['dimensions'] = $this->buildDimensions($item, $package);

            $line_items[] = $line_item;
        }

        if ($line_items === []) {
            throw new RateException('At least one package line item is required to build a rate request.');
        }

        return $line_items;
    }

    private function buildDimensions(array $item, array $package): array {
        $length = $this->numericOrNull($item['length'] ?? $package['length'] ?? null);
        $width = $this->numericOrNull($item['width'] ?? $package['width'] ?? null);
        $height = $this->numericOrNull($item['height'] ?? $package['height'] ?? null);

        if ($length === null || $width === null || $height === null || $length <= 0 || $width <= 0 || $height <= 0) {
            throw new RateException('Package dimensions must include positive length, width, and height values.');
        }

        return [
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'units' => $this->stringValue($item['dimension_unit'] ?? $package['dimension_unit'] ?? null, self::DEFAULT_DIMENSION_UNIT),
        ];
    }

    private function buildAddress(array $package, string $prefix): array {
        $address = [
            'postalCode' => $this->requiredString($package, $prefix . '_zip', ucfirst($prefix) . ' postal code is required to build a rate request.'),
            'countryCode' => $this->requiredString($package, $prefix . '_country', ucfirst($prefix) . ' country is required to build a rate request.'),
        ];

        $state = $this->stringValue($package[$prefix . '_state'] ?? null);
        $city = $this->stringValue($package[$prefix . '_city'] ?? null);
        $street_lines = $this->streetLines($package, $prefix);

        if ($state !== '') {
            $address['stateOrProvinceCode'] = $state;
        }

        if ($city !== '') {
            $address['city'] = $city;
        }

        if ($street_lines !== []) {
            $address['streetLines'] = $street_lines;
        }

        return $address;
    }

    private function streetLines(array $package, string $prefix): array {
        $lines = [];

        foreach ([$prefix . '_address_1', $prefix . '_address_2'] as $key) {
            $value = $this->stringValue($package[$key] ?? null);

            if ($value !== '') {
                $lines[] = $value;
            }
        }

        return $lines;
    }

    private function requiredString(array $source, string $key, string $message): string {
        $value = $this->stringValue($source[$key] ?? null);

        if ($value === '') {
            throw new RateException($message);
        }

        return $value;
    }

    private function positiveFloat($value, string $message): float {
        $number = $this->numericOrNull($value);

        if ($number === null || $number <= 0) {
            throw new RateException($message);
        }

        return $number;
    }

    private function numericOrNull($value): ?float {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function stringValue($value, string $default = ''): string {
        if (is_scalar($value)) {
            $value = trim((string) $value);
            return $value === '' ? $default : $value;
        }

        return $default;
    }

    private function stringList($value, array $default): array {
        if (!is_array($value)) {
            return $default;
        }

        $values = [];

        foreach ($value as $item) {
            $item = $this->stringValue($item);

            if ($item !== '') {
                $values[] = $item;
            }
        }

        return $values === [] ? $default : array_values(array_unique($values));
    }

    private function booleanValue($value, bool $default): bool {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return $default;
        }

        return (bool) $value;
    }

    private function shipmentDate($value): string {
        $date = $this->stringValue($value);

        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        return gmdate('Y-m-d');
    }

    private function validateEnvironment($value): void {
        $environment = $this->stringValue($value, 'sandbox');

        if (!in_array($environment, ['sandbox', 'production'], true)) {
            throw new RateException('Rate request environment must be sandbox or production.');
        }
    }
}
