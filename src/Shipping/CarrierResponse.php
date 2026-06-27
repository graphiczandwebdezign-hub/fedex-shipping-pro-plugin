<?php
namespace FedExShippingPro\Shipping;

final class CarrierResponse {
    private bool $success;
    private int $http_status;
    private array $payload;
    private array $warnings;
    private array $notes;

    public function __construct(bool $success, int $http_status = 0, array $payload = [], array $warnings = [], array $notes = []) {
        $this->success = $success;
        $this->http_status = $http_status;
        $this->payload = $payload;
        $this->warnings = array_values($warnings);
        $this->notes = array_values($notes);
    }

    public function isSuccess(): bool {
        return $this->success;
    }

    public function getHttpStatus(): int {
        return $this->http_status;
    }

    public function getPayload(): array {
        return $this->payload;
    }

    public function getWarnings(): array {
        return $this->warnings;
    }

    public function getNotes(): array {
        return $this->notes;
    }
}
