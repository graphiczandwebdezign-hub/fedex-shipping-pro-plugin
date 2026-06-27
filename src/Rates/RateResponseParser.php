<?php
namespace FedExShippingPro\Rates;

class RateResponseParser {

    private array $warnings = [];

    private array $notes = [];

    public function parse($response): array {
        $this->warnings = [];
        $this->notes = [];

        if (!is_array($response)) {
            $this->warnings[] = 'Malformed rate response.';
            return [];
        }

        $this->collectMessages($response);
        $details = $response['output']['rateReplyDetails'] ?? $response['rateReplyDetails'] ?? [];

        if (!is_array($details) || $details === []) {
            $this->warnings[] = 'Rate response did not include rate reply details.';
            return [];
        }

        $rates = [];

        foreach ($details as $detail) {
            if (!is_array($detail)) {
                continue;
            }

            $rated_shipment = $this->firstArray($detail['ratedShipmentDetails'] ?? []);

            $rates[] = [
                'service_code' => $this->stringValue($detail['serviceType'] ?? ''),
                'service_name' => $this->serviceName($detail),
                'currency' => $this->currency($detail),
                'amount' => $this->amount($detail),
                'transit_days' => $this->transitDays($detail),
                'delivery_date' => $this->deliveryDate($detail, $rated_shipment),
                'estimated_delivery' => $this->deliveryDate($detail, $rated_shipment),
                'raw' => $detail,
            ];
        }

        return $rates;
    }

    public function getWarnings(): array {
        return $this->warnings;
    }

    public function getNotes(): array {
        return $this->notes;
    }

    private function collectMessages(array $response): void {
        foreach (['alerts', 'errors', 'notifications'] as $key) {
            $messages = $response[$key] ?? $response['output'][$key] ?? [];

            if (!is_array($messages)) {
                continue;
            }

            foreach ($messages as $message) {
                if (!is_array($message)) {
                    continue;
                }

                $text = $this->stringValue($message['message'] ?? $message['localizedMessage'] ?? $message['code'] ?? '');

                if ($text === '') {
                    continue;
                }

                $type = strtoupper($this->stringValue($message['alertType'] ?? $message['severity'] ?? 'NOTE'));

                if (in_array($type, ['WARNING', 'ERROR', 'FAILURE'], true)) {
                    $this->warnings[] = $text;
                } else {
                    $this->notes[] = $text;
                }
            }
        }
    }

    private function serviceName(array $detail): string {
        $name = $this->stringValue($detail['serviceName'] ?? '');

        if ($name !== '') {
            return $name;
        }

        return $this->stringValue($detail['serviceType'] ?? '');
    }

    private function currency(array $detail): string {
        $rated_shipment = $this->firstArray($detail['ratedShipmentDetails'] ?? []);
        $currency = $this->moneyCurrency($rated_shipment['totalNetCharge'] ?? null);

        if ($currency === '') {
            $currency = $this->moneyCurrency($rated_shipment['totalNetFedExCharge'] ?? null);
        }

        if ($currency === '' && isset($rated_shipment['shipmentRateDetail']) && is_array($rated_shipment['shipmentRateDetail'])) {
            $currency = $this->moneyCurrency($rated_shipment['shipmentRateDetail']['totalNetCharge'] ?? null);
        }

        if ($currency !== '') {
            return $currency;
        }

        return $this->stringValue($detail['currency'] ?? '');
    }

    private function amount(array $detail): float {
        $rated_shipment = $this->firstArray($detail['ratedShipmentDetails'] ?? []);

        foreach (['totalNetCharge', 'totalNetFedExCharge', 'totalBaseCharge'] as $key) {
            $amount = $this->moneyAmount($rated_shipment[$key] ?? null);

            if ($amount !== null) {
                return $amount;
            }
        }

        if (isset($rated_shipment['shipmentRateDetail']) && is_array($rated_shipment['shipmentRateDetail'])) {
            foreach (['totalNetCharge', 'totalNetFedExCharge', 'totalBaseCharge'] as $key) {
                $amount = $this->moneyAmount($rated_shipment['shipmentRateDetail'][$key] ?? null);

                if ($amount !== null) {
                    return $amount;
                }
            }
        }

        if (isset($detail['amount']) && is_numeric($detail['amount'])) {
            return (float) $detail['amount'];
        }

        return 0.0;
    }

    private function transitDays(array $detail): string {
        $commit = $detail['commit'] ?? [];

        if (is_array($commit)) {
            $transit = $this->stringValue($commit['transitDays'] ?? $commit['transitTime'] ?? '');

            if ($transit !== '') {
                return $transit;
            }
        }

        return $this->stringValue($detail['transitTime'] ?? '');
    }

    private function deliveryDate(array $detail, array $rated_shipment): string {
        $commit = $detail['commit'] ?? [];

        if (is_array($commit)) {
            foreach ([
                $commit['dateDetail']['dayFormat'] ?? null,
                $commit['dateDetail']['deliveryTimestamp'] ?? null,
                $commit['dateDetail']['estimatedDeliveryDate'] ?? null,
                $commit['estimatedDeliveryDate'] ?? null,
            ] as $candidate) {
                $value = $this->stringValue($candidate);

                if ($value !== '') {
                    return $value;
                }
            }
        }

        foreach ([
            $rated_shipment['deliveryTimestamp'] ?? null,
            $detail['deliveryTimestamp'] ?? null,
        ] as $candidate) {
            $value = $this->stringValue($candidate);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function firstArray($value): array {
        if (!is_array($value)) {
            return [];
        }

        if ($value === []) {
            return [];
        }

        $first = reset($value);
        return is_array($first) ? $first : $value;
    }

    private function moneyAmount($value): ?float {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_array($value) && isset($value['amount']) && is_numeric($value['amount'])) {
            return (float) $value['amount'];
        }

        return null;
    }

    private function moneyCurrency($value): string {
        if (is_array($value)) {
            return $this->stringValue($value['currency'] ?? '');
        }

        return '';
    }

    private function stringValue($value): string {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
