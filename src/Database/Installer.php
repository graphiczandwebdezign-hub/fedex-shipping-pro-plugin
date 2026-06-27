<?php
namespace FedExShippingPro\Database;

class Installer {
    public static function install(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = $wpdb->prefix . 'fedex_shipments';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned DEFAULT NULL,
            carrier varchar(50) NOT NULL DEFAULT 'fedex',
            shipment_id varchar(191) DEFAULT NULL,
            tracking_number varchar(191) DEFAULT NULL,
            master_tracking_number varchar(191) DEFAULT NULL,
            service_type varchar(100) DEFAULT NULL,
            shipment_status varchar(50) NOT NULL DEFAULT 'draft',
            charges decimal(20,4) DEFAULT NULL,
            currency char(3) DEFAULT NULL,
            label_reference varchar(191) DEFAULT NULL,
            environment varchar(20) DEFAULT NULL,
            request_json longtext DEFAULT NULL,
            response_json longtext DEFAULT NULL,
            execution_time_ms decimal(20,2) DEFAULT NULL,
            warnings longtext DEFAULT NULL,
            notes longtext DEFAULT NULL,
            errors longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY shipment_id (shipment_id),
            KEY tracking_number (tracking_number)
        ) {$charset_collate};";

        dbDelta($sql);
    }
}
