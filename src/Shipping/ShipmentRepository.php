<?php
namespace FedExShippingPro\Shipping;

class ShipmentRepository {
    private string $table_name;

    public function __construct(?string $table_name = null) {
        global $wpdb;

        $this->table_name = $table_name ?: $wpdb->prefix . 'fedex_shipments';
    }

    public function getTableName(): string {
        return $this->table_name;
    }

    public function save(Shipment $shipment): bool {
        global $wpdb;

        $data = $this->toRow($shipment);
        $format = $this->rowFormat($data);

        if ($shipment->getId() === null) {
            $inserted = $wpdb->insert($this->table_name, $data, $format);

            return $inserted !== false;
        }

        $data['updated_at'] = current_time('mysql');
        $result = $wpdb->update($this->table_name, $data, ['id' => $shipment->getId()], $format, ['%d']);

        return $result !== false;
    }

    public function findByOrderId(int $order_id): ?array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE order_id = %d ORDER BY id DESC LIMIT 1", $order_id),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public function findByShipmentId(string $shipment_id): ?array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE shipment_id = %s ORDER BY id DESC LIMIT 1", $shipment_id),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    private function toRow(Shipment $shipment): array {
        return [
            'order_id' => $shipment->getOrderId(),
            'carrier' => $shipment->getCarrier(),
            'shipment_id' => $shipment->getShipmentId(),
            'tracking_number' => $shipment->getTrackingNumber(),
            'master_tracking_number' => $shipment->getMasterTrackingNumber(),
            'service_type' => $shipment->getServiceType(),
            'shipment_status' => $shipment->getStatus(),
            'charges' => $shipment->getCharges(),
            'currency' => $shipment->getCurrency(),
            'label_reference' => $shipment->getLabel() ? $shipment->getLabel()->getReference() : null,
            'environment' => $shipment->getEnvironment(),
            'created_at' => $shipment->getCreatedAt() ?? current_time('mysql'),
            'updated_at' => $shipment->getUpdatedAt() ?? current_time('mysql'),
        ];
    }

    private function rowFormat(array $data): array {
        $formats = [];

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'order_id':
                    $formats[] = '%d';
                    break;
                case 'charges':
                    $formats[] = '%f';
                    break;
                case 'created_at':
                case 'updated_at':
                    $formats[] = '%s';
                    break;
                default:
                    $formats[] = $value === null ? '%s' : '%s';
            }
        }

        return $formats;
    }
}
