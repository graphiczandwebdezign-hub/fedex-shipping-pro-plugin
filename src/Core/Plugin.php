<?php

namespace FedExShippingPro\Core;

use FedExShippingPro\WooCommerce\Bootstrap;
use FedExShippingPro\Admin\SettingsPage;
use FedExShippingPro\Admin\DiagnosticsPage;
use FedExShippingPro\Admin\TokenInspectorPage;
use FedExShippingPro\Admin\OAuthTestPage;
use FedExShippingPro\Admin\RateTestPage;
use FedExShippingPro\Admin\ShipTestPage;

class Plugin {

    public function boot(): void {

        // Register WooCommerce shipping FIRST
        (new Bootstrap())->register();

        // Admin pages
        add_action('admin_menu', function () {

            (new SettingsPage())->register();
            (new DiagnosticsPage())->register();
            (new TokenInspectorPage())->register();
            (new OAuthTestPage())->register();
            (new RateTestPage())->register();
            (new ShipTestPage())->register();

        });
    }
}
