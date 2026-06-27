<?php
namespace FedExShippingPro\Shipping;

use FedExShippingPro\WooCommerce\PackageBuilder;

final class ShipmentBuilder {
    private PackageBuilder $package_builder;

    public function __construct(?PackageBuilder $package_builder = null) {
        $this->package_builder = $package_builder ?? new PackageBuilder();
    }

    public function fromOrder(array $order_data): Shipment {
        $carrier = $this->stringValue($order_data['carrier'] ?? 'fedex');

        return new Shipment(
            $carrier,
            $this->stringValue($order_data['status'] ?? ShipmentStatus::DRAFT),
            isset($order_data['id']) ? (int) $order_data['id'] : null,
            isset($order_data['order_id']) ? (int) $order_data['order_id'] : null,
            $this->nullableString($order_data['shipment_id'] ?? null),
            $this->nullableString($order_data['tracking_number'] ?? null),
            $this->nullableString($order_data['master_tracking_number'] ?? null),
            $this->nullableString($order_data['service_type'] ?? null),
            $this->nullableString($order_data['packaging_type'] ?? null),
            isset($order_data['charges']) && is_numeric($order_data['charges']) ? (float) $order_data['charges'] : null,
            $this->nullableString($order_data['currency'] ?? null),
            $order_data['label'] instanceof Label ? $order_data['label'] : null,
            $this->nullableString($order_data['environment'] ?? null),
            $this->nullableString($order_data['created_at'] ?? null),
            $this->nullableString($order_data['updated_at'] ?? null),
            is_array($order_data['warnings'] ?? null) ? $order_data['warnings'] : [],
            is_array($order_data['notes'] ?? null) ? $order_data['notes'] : []
        );
    }

    public function buildPackage(array $package): array {
        return $this->package_builder->build($package);
    }

    private function nullableString($value): ?string {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function stringValue($value, string $default = ''): string {
        if (!is_scalar($value)) {
            return $default;
        }

        $value = trim((string) $value);
        return $value === '' ? $default : $value;
    }
}
