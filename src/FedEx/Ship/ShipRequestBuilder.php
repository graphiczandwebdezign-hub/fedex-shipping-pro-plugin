<?php
namespace FedExShippingPro\FedEx\Ship;

use FedExShippingPro\Shipping\CarrierRequest;

class ShipRequestBuilder {
    private const DEFAULT_PICKUP_TYPE = 'DROPOFF_AT_FEDEX_LOCATION';
    private const DEFAULT_PACKAGING_TYPE = 'YOUR_PACKAGING';
    private const DEFAULT_WEIGHT_UNIT = 'LB';
    private const DEFAULT_DIMENSION_UNIT = 'IN';
    private const DEFAULT_LABEL_FORMAT = 'PDF';

    public function build(CarrierRequest $request): array {
        $payload = $request->getPayload();

        $shipper = $this->requiredArray($payload, 'sender', 'Sender is required.');
        $recipient = $this->requiredArray($payload, 'recipient', 'Recipient is required.');
        $packages = $this->requiredPackages($payload);
        $service_type = $this->requiredString($payload, 'service_type', 'Service type is required.');
        $packaging_type = $this->stringValue($payload['packaging_type'] ?? null, self::DEFAULT_PACKAGING_TYPE);
        $payment = $this->requiredArray($payload, 'payment', 'Payment is required.');
        $label_spec = $this->buildLabelSpecification($payload['label_specification'] ?? []);

        $requested_packages = [];

        foreach ($packages as $package) {
            $requested_packages[] = $this->buildPackageLineItem($package);
        }

        return [
            'labelResponseOptions' => 'LABEL',
            'accountNumber' => [
                'value' => $this->requiredString($payload, 'account_number', 'Account number is required.'),
            ],
            'requestedShipment' => [
                'shipDatestamp' => $this->shipmentDate($payload['ship_date'] ?? null),
                'pickupType' => $this->stringValue($payload['pickup_type'] ?? null, self::DEFAULT_PICKUP_TYPE),
                'serviceType' => $service_type,
                'packagingType' => $packaging_type,
                'shipper' => [
                    'contact' => $this->buildContact($shipper, 'sender'),
                    'address' => $this->buildAddress($shipper, 'sender'),
                ],
                'recipients' => [[
                    'contact' => $this->buildContact($recipient, 'recipient'),
                    'address' => $this->buildAddress($recipient, 'recipient'),
                ]],
                'shippingChargesPayment' => $this->buildShippingChargesPayment($payment),
                'labelSpecification' => $label_spec,
                'requestedPackageLineItems' => $requested_packages,
            ],
        ];
    }

    private function buildPackageLineItem(array $package): array {
        $weight = $this->positiveFloat($package['weight'] ?? null, 'Package weight is required.');
        $dimensions = $this->buildDimensions($package);

        return [
            'weight' => [
                'units' => $this->stringValue($package['weight_unit'] ?? null, self::DEFAULT_WEIGHT_UNIT),
                'value' => $weight,
            ],
            'dimensions' => [
                'length' => $dimensions['length'],
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'units' => $this->stringValue($package['dimension_unit'] ?? null, self::DEFAULT_DIMENSION_UNIT),
            ],
        ];
    }

    private function buildShippingChargesPayment(array $payment): array {
        return [
            'paymentType' => $this->requiredString($payment, 'type', 'Payment type is required.'),
            'payor' => [
                'responsibleParty' => [
                    'accountNumber' => [
                        'value' => $this->requiredString($payment, 'account_number', 'Payment account number is required.'),
                    ],
                ],
            ],
        ];
    }

    private function buildLabelSpecification(array $specification): array {
        $format = $this->stringValue($specification['format'] ?? null, self::DEFAULT_LABEL_FORMAT);
        $image_type = $this->stringValue($specification['image_type'] ?? null, $format);

        return [
            'imageType' => $image_type,
            'labelStockType' => $this->stringValue($specification['stock_type'] ?? null, 'PAPER_LETTER'),
        ];
    }

    private function buildContact(array $party, string $label): array {
        return [
            'personName' => $this->requiredString($party, 'name', ucfirst($label) . ' name is required.'),
            'phoneNumber' => $this->requiredString($party, 'phone', ucfirst($label) . ' phone is required.'),
            'companyName' => $this->stringValue($party['company'] ?? null),
            'emailAddress' => $this->stringValue($party['email'] ?? null),
        ];
    }

    private function buildAddress(array $party, string $label): array {
        return [
            'streetLines' => $this->streetLines($party, $label),
            'city' => $this->requiredString($party, 'city', ucfirst($label) . ' city is required.'),
            'stateOrProvinceCode' => $this->stringValue($party['state'] ?? null),
            'postalCode' => $this->requiredString($party, 'postal_code', ucfirst($label) . ' postal code is required.'),
            'countryCode' => $this->requiredString($party, 'country_code', ucfirst($label) . ' country code is required.'),
            'residential' => $this->booleanValue($party['residential'] ?? null, false),
        ];
    }

    private function streetLines(array $party, string $label): array {
        $lines = [];

        foreach (['address_1', 'address_2'] as $key) {
            $value = $this->stringValue($party[$key] ?? null);

            if ($value !== '') {
                $lines[] = $value;
            }
        }

        if ($lines === []) {
            throw new FedExShipmentException(ucfirst($label) . ' street address is required.');
        }

        return $lines;
    }

    private function requiredPackages(array $payload): array {
        $packages = $payload['packages'] ?? [];

        if (!is_array($packages) || $packages === []) {
            throw new FedExShipmentException('At least one package is required.');
        }

        return $packages;
    }

    private function buildDimensions(array $package): array {
        $length = $this->positiveFloat($package['length'] ?? null, 'Package length is required.');
        $width = $this->positiveFloat($package['width'] ?? null, 'Package width is required.');
        $height = $this->positiveFloat($package['height'] ?? null, 'Package height is required.');

        return compact('length', 'width', 'height');
    }

    private function requiredArray(array $source, string $key, string $message): array {
        $value = $source[$key] ?? null;

        if (!is_array($value) || $value === []) {
            throw new FedExShipmentException($message);
        }

        return $value;
    }

    private function requiredString(array $source, string $key, string $message): string {
        $value = $this->stringValue($source[$key] ?? null);

        if ($value === '') {
            throw new FedExShipmentException($message);
        }

        return $value;
    }

    private function positiveFloat($value, string $message): float {
        if (!is_numeric($value) || (float) $value <= 0) {
            throw new FedExShipmentException($message);
        }

        return (float) $value;
    }

    private function shipmentDate($value): string {
        $value = $this->stringValue($value);

        if ($value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return gmdate('Y-m-d');
    }

    private function stringValue($value, string $default = ''): string {
        if (!is_scalar($value)) {
            return $default;
        }

        $value = trim((string) $value);
        return $value === '' ? $default : $value;
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
}
