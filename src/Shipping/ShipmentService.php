<?php
namespace FedExShippingPro\Shipping;

use FedExShippingPro\Logging\Logger;

class ShipmentService {
    private ShipmentBuilder $shipment_builder;
    private ShipmentRepository $shipment_repository;

    public function __construct(
        ?ShipmentBuilder $shipment_builder = null,
        ?ShipmentRepository $shipment_repository = null
    ) {
        $this->shipment_builder = $shipment_builder ?? new ShipmentBuilder();
        $this->shipment_repository = $shipment_repository ?? new ShipmentRepository();
    }

    public function createFromOrder(array $order_data): Shipment {
        $shipment = $this->shipment_builder->fromOrder($order_data);

        $saved = $this->shipment_repository->save($shipment);

        if (!$saved) {
            Logger::info('Unable to persist shipment record for order ' . (string) ($shipment->getOrderId() ?? 'unknown'));
        }

        return $shipment;
    }

    public function save(Shipment $shipment): bool {
        return $this->shipment_repository->save($shipment);
    }

    public function findByOrderId(int $order_id): ?array {
        return $this->shipment_repository->findByOrderId($order_id);
    }

    public function findByShipmentId(string $shipment_id): ?array {
        return $this->shipment_repository->findByShipmentId($shipment_id);
    }
}
