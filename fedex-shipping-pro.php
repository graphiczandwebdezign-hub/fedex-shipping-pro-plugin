<?php
/**
 * Plugin Name: FedEx Shipping Pro
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/vendor/autoload.php';
register_activation_hook(__FILE__, ['FedExShippingPro\\Database\\Installer', 'install']);
(new FedExShippingPro\Core\Plugin())->boot();
