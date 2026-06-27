<?php
namespace FedExShippingPro\Shipping;

final class ShipmentStatus {
    public const DRAFT = 'draft';
    public const PENDING = 'pending';
    public const CREATED = 'created';
    public const LABELED = 'labeled';
    public const VOIDED = 'voided';
    public const FAILED = 'failed';

    public static function isFinal(string $status): bool {
        return in_array($status, [self::LABELED, self::VOIDED, self::FAILED], true);
    }
}
