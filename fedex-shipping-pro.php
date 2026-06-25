<?php
/**
 * Plugin Name: FedEx Shipping Pro
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/vendor/autoload.php';
(new FedExShippingPro\Core\Plugin())->boot();
