<?php
namespace FedExShippingPro\Shipping;

final class TrackingInfo {
    private string $tracking_number;
    private ?string $master_tracking_number;
    private ?string $status;
    private ?string $estimated_delivery;
    private array $events;

    public function __construct(
        string $tracking_number,
        ?string $master_tracking_number = null,
        ?string $status = null,
        ?string $estimated_delivery = null,
        array $events = []
    ) {
        $this->tracking_number = trim($tracking_number);
        $this->master_tracking_number = $master_tracking_number !== null && trim($master_tracking_number) !== '' ? trim($master_tracking_number) : null;
        $this->status = $status !== null && trim($status) !== '' ? trim($status) : null;
        $this->estimated_delivery = $estimated_delivery !== null && trim($estimated_delivery) !== '' ? trim($estimated_delivery) : null;
        $this->events = array_values($events);
    }

    public function getTrackingNumber(): string {
        return $this->tracking_number;
    }

    public function getMasterTrackingNumber(): ?string {
        return $this->master_tracking_number;
    }

    public function getStatus(): ?string {
        return $this->status;
    }

    public function getEstimatedDelivery(): ?string {
        return $this->estimated_delivery;
    }

    public function getEvents(): array {
        return $this->events;
    }
}
