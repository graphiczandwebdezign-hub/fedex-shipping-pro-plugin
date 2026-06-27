<?php
namespace FedExShippingPro\FedEx\Ship;

use FedExShippingPro\Shipping\CarrierResponse;
use FedExShippingPro\Shipping\Label;
use FedExShippingPro\Shipping\Shipment;
use FedExShippingPro\Shipping\ShipmentStatus;

class ShipResponseParser {
    public function parse(array $response, array $context = []): CarrierResponse {
        $http_status = isset($response['status']) ? (int) $response['status'] : 0;
        $body = $response['body'] ?? '';
        $decoded = is_string($body) ? json_decode($body, true) : null;

        if (!is_array($decoded)) {
            return new CarrierResponse(false, $http_status, [], ['Unable to decode FedEx shipment response.'], []);
        }

        $warnings = $this->warnings($decoded);
        $notes = $this->notes($decoded);
        $shipment_data = $this->buildShipment($decoded, $context);

        if ($http_status >= 400) {
            return new CarrierResponse(false, $http_status, [], $warnings, $notes);
        }

        if ($shipment_data === null) {
            return new CarrierResponse(false, $http_status, [], array_merge($warnings, ['FedEx shipment response did not contain shipment data.']), $notes);
        }

        $payload = [
            'shipment' => $shipment_data,
            'raw' => $decoded,
        ];

        return new CarrierResponse(true, $http_status, $payload, $warnings, $notes);
    }

    private function buildShipment(array $decoded, array $context): ?Shipment {
        $output = $decoded['output'] ?? $decoded;
        $transaction_shipments = $output['transactionShipments'] ?? [];
        $first_shipment = is_array($transaction_shipments) ? ($transaction_shipments[0] ?? []) : [];
        $master_shipment = $this->firstArray($first_shipment['masterTrackingId'] ?? []);
        $piece = $this->firstArray($first_shipment['pieceResponses'] ?? []);
        $tracking_number = $this->stringValue($piece['trackingNumber'] ?? $master_shipment['trackingNumber'] ?? '');
        $shipment_id = $this->stringValue($first_shipment['shipmentId'] ?? $output['shipmentId'] ?? '');
        $service_type = $this->stringValue($context['service_type'] ?? $first_shipment['serviceType'] ?? '');
        $packaging_type = $this->stringValue($context['packaging_type'] ?? $first_shipment['packagingType'] ?? '');
        $currency = $this->shipmentCurrency($first_shipment, $piece);
        $charges = $this->shipmentCharges($first_shipment, $piece);
        $label = $this->buildLabel($first_shipment, $piece);

        if ($shipment_id === '' && $tracking_number === '' && $label === null) {
            return null;
        }

        return new Shipment(
            $this->stringValue($context['carrier'] ?? 'fedex'),
            ShipmentStatus::CREATED,
            null,
            isset($context['order_id']) ? (int) $context['order_id'] : null,
            $shipment_id !== '' ? $shipment_id : null,
            $tracking_number !== '' ? $tracking_number : null,
            $this->stringValue($master_shipment['trackingNumber'] ?? '') !== '' ? $this->stringValue($master_shipment['trackingNumber'] ?? '') : null,
            $service_type !== '' ? $service_type : null,
            $packaging_type !== '' ? $packaging_type : null,
            $charges,
            $currency !== '' ? $currency : null,
            $label,
            $this->stringValue($context['environment'] ?? '') !== '' ? $this->stringValue($context['environment'] ?? '') : null,
            gmdate('Y-m-d H:i:s'),
            gmdate('Y-m-d H:i:s'),
            $this->warnings($decoded),
            $this->notes($decoded)
        );
    }

    private function buildLabel(array $shipment, array $piece): ?Label {
        $label = $this->firstArray($piece['label'] ?? $shipment['label'] ?? []);

        if ($label === []) {
            return null;
        }

        $format = $this->stringValue($label['imageType'] ?? $label['format'] ?? '');
        $reference = $this->stringValue($label['labelId'] ?? $label['reference'] ?? '');
        $url = $this->stringValue($label['url'] ?? '');
        $base64 = $this->stringValue($label['encodedLabel'] ?? $label['base64'] ?? '');

        return new Label($format !== '' ? $format : 'PDF', $reference, $url !== '' ? $url : null, $base64 !== '' ? $base64 : null);
    }

    private function shipmentCharges(array $shipment, array $piece): ?float {
        foreach ([$piece, $shipment] as $source) {
            foreach (['netCharge', 'totalNetCharge', 'totalNetFedExCharge', 'shipmentAmount'] as $key) {
                if (isset($source[$key]) && is_numeric($source[$key])) {
                    return (float) $source[$key];
                }
            }
        }

        return null;
    }

    private function shipmentCurrency(array $shipment, array $piece): string {
        foreach ([$piece, $shipment] as $source) {
            foreach (['currency', 'currencyCode'] as $key) {
                if (isset($source[$key]) && is_scalar($source[$key])) {
                    return strtoupper(trim((string) $source[$key]));
                }
            }
        }

        return '';
    }

    private function warnings(array $decoded): array {
        return $this->messages($decoded, ['warnings', 'alerts', 'notifications']);
    }

    private function notes(array $decoded): array {
        return $this->messages($decoded, ['notes', 'messages', 'notifications']);
    }

    private function messages(array $decoded, array $keys): array {
        $messages = [];

        foreach ($keys as $key) {
            $value = $decoded[$key] ?? $decoded['output'][$key] ?? [];

            if (!is_array($value)) {
                continue;
            }

            foreach ($value as $item) {
                if (is_array($item)) {
                    $message = $this->stringValue($item['message'] ?? $item['description'] ?? $item['code'] ?? '');
                } else {
                    $message = $this->stringValue($item);
                }

                if ($message !== '') {
                    $messages[] = $message;
                }
            }
        }

        return array_values(array_unique($messages));
    }

    private function firstArray($value): array {
        if (!is_array($value) || $value === []) {
            return [];
        }

        $first = reset($value);
        return is_array($first) ? $first : $value;
    }

    private function stringValue($value): string {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
