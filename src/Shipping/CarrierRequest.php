<?php
namespace FedExShippingPro\Shipping;

final class CarrierRequest {
    private string $carrier_code;
    private array $payload;
    private array $meta;

    public function __construct(string $carrier_code, array $payload = [], array $meta = []) {
        $this->carrier_code = trim($carrier_code);
        $this->payload = $payload;
        $this->meta = $meta;
    }

    public function getCarrierCode(): string {
        return $this->carrier_code;
    }

    public function getPayload(): array {
        return $this->payload;
    }

    public function getMeta(): array {
        return $this->meta;
    }
}
