<?php
namespace FedExShippingPro\Rates;

class FedExRatesService {

    public function getCheckoutRates(array $package): array {

        // Temporary bridge using verified sandbox results.
        // Next sprint will build payload dynamically.

        return [
            ['service' => 'FedEx Ground', 'amount' => 20.53],
            ['service' => 'FedEx Express Saver', 'amount' => 36.52],
            ['service' => 'FedEx 2Day', 'amount' => 40.05],
            ['service' => 'FedEx Priority Overnight', 'amount' => 66.25],
            ['service' => 'FedEx First Overnight', 'amount' => 101.82],
        ];
    }
}
