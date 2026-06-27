<?php
namespace FedExShippingPro\Shipping;

final class Shipment {
    private ?int $id;
    private ?int $order_id;
    private string $carrier;
    private ?string $shipment_id;
    private ?string $tracking_number;
    private ?string $master_tracking_number;
    private ?string $service_type;
    private ?string $packaging_type;
    private ?float $charges;
    private ?string $currency;
    private ?Label $label;
    private string $status;
    private ?string $environment;
    private ?string $created_at;
    private ?string $updated_at;
    private array $warnings;
    private array $notes;

    public function __construct(
        string $carrier,
        string $status = ShipmentStatus::DRAFT,
        ?int $id = null,
        ?int $order_id = null,
        ?string $shipment_id = null,
        ?string $tracking_number = null,
        ?string $master_tracking_number = null,
        ?string $service_type = null,
        ?string $packaging_type = null,
        ?float $charges = null,
        ?string $currency = null,
        ?Label $label = null,
        ?string $environment = null,
        ?string $created_at = null,
        ?string $updated_at = null,
        array $warnings = [],
        array $notes = []
    ) {
        $this->id = $id;
        $this->order_id = $order_id;
        $this->carrier = trim($carrier);
        $this->shipment_id = $shipment_id !== null && trim($shipment_id) !== '' ? trim($shipment_id) : null;
        $this->tracking_number = $tracking_number !== null && trim($tracking_number) !== '' ? trim($tracking_number) : null;
        $this->master_tracking_number = $master_tracking_number !== null && trim($master_tracking_number) !== '' ? trim($master_tracking_number) : null;
        $this->service_type = $service_type !== null && trim($service_type) !== '' ? trim($service_type) : null;
        $this->packaging_type = $packaging_type !== null && trim($packaging_type) !== '' ? trim($packaging_type) : null;
        $this->charges = $charges;
        $this->currency = $currency !== null && trim($currency) !== '' ? strtoupper(trim($currency)) : null;
        $this->label = $label;
        $this->status = $status;
        $this->environment = $environment !== null && trim($environment) !== '' ? trim($environment) : null;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
        $this->warnings = array_values($warnings);
        $this->notes = array_values($notes);
    }

    public function getId(): ?int { return $this->id; }
    public function getOrderId(): ?int { return $this->order_id; }
    public function getCarrier(): string { return $this->carrier; }
    public function getShipmentId(): ?string { return $this->shipment_id; }
    public function getTrackingNumber(): ?string { return $this->tracking_number; }
    public function getMasterTrackingNumber(): ?string { return $this->master_tracking_number; }
    public function getServiceType(): ?string { return $this->service_type; }
    public function getPackagingType(): ?string { return $this->packaging_type; }
    public function getCharges(): ?float { return $this->charges; }
    public function getCurrency(): ?string { return $this->currency; }
    public function getLabel(): ?Label { return $this->label; }
    public function getStatus(): string { return $this->status; }
    public function getEnvironment(): ?string { return $this->environment; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }
    public function getWarnings(): array { return $this->warnings; }
    public function getNotes(): array { return $this->notes; }
}
