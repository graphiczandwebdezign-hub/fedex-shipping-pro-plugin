<?php
namespace FedExShippingPro\Admin;

use FedExShippingPro\FedEx\Ship\FedExShipmentProvider;
use FedExShippingPro\FedEx\Ship\ShipRequestBuilder;
use FedExShippingPro\Shipping\CarrierRequest;
use FedExShippingPro\Shipping\Shipment;

class ShipTestPage {
    private const NONCE_ACTION = 'fedex_ship_test_action';
    private const NONCE_NAME = 'fedex_ship_test_nonce';

    public function register(): void {
        add_submenu_page(
            'fedex-shipping-pro',
            'FedEx Ship Test',
            'FedEx Ship Test',
            'manage_options',
            'fedex-ship-test',
            [$this, 'render']
        );
    }

    public function render(): void {
        $state = $this->defaultState();
        $result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[self::NONCE_NAME]) && wp_verify_nonce((string) $_POST[self::NONCE_NAME], self::NONCE_ACTION)) {
            $state = $this->collectState($_POST);
            $action = isset($_POST['fedex_ship_action']) ? sanitize_text_field((string) $_POST['fedex_ship_action']) : '';

            if ($action === 'clear') {
                $state = $this->defaultState();
            } elseif ($action === 'validate') {
                $result = $this->validatePayload($state);
                $this->persistRun($state, $result);
            } elseif ($action === 'create') {
                $result = $this->createShipment($state);
                $this->persistRun($state, $result);
            }
        }

        echo '<div class="wrap">';
        echo '<h1>FedEx Ship Test</h1>';
        echo $this->renderMessage($result);
        echo '<form method="post">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        echo '<input type="hidden" name="fedex_ship_action" value="validate" />';
        echo '<table class="form-table">';
        $this->renderSection('Sender', [
            'sender_country' => 'Country',
            'sender_postal_code' => 'Postal Code',
            'sender_city' => 'City',
            'sender_address' => 'Address',
        ], $state);
        $this->renderSection('Recipient', [
            'recipient_country' => 'Country',
            'recipient_postal_code' => 'Postal Code',
            'recipient_city' => 'City',
            'recipient_address' => 'Address',
        ], $state);
        $this->renderSection('Package', [
            'weight' => 'Weight',
            'length' => 'Length',
            'width' => 'Width',
            'height' => 'Height',
        ], $state);
        $this->renderSection('Shipping', [
            'service_type' => 'Service Type',
            'packaging_type' => 'Packaging Type',
        ], $state);
        $this->renderSection('Label', [
            'label_format' => 'Label Format',
            'label_image_type' => 'Image Type',
        ], $state);
        echo '</table>';
        echo '<p>';
        echo '<button type="submit" class="button button-secondary" name="fedex_ship_action" value="validate">Validate Payload</button> ';
        echo '<button type="submit" class="button button-primary" name="fedex_ship_action" value="create">Create Sandbox Shipment</button> ';
        echo '<button type="submit" class="button" name="fedex_ship_action" value="clear">Clear Results</button>';
        echo '</p>';
        echo '</form>';

        if (is_array($result)) {
            $this->renderDiagnostics($result);
        }

        echo '</div>';
    }

    private function createShipment(array $state): array {
        $provider = new FedExShipmentProvider();
        $request = new CarrierRequest('fedex', $this->buildPayload($state), [
            'carrier' => 'fedex',
            'environment' => SettingsPage::get_environment(),
        ]);

        $start = microtime(true);
        $carrier_response = $provider->createShipment($request);
        $execution_time_ms = round((microtime(true) - $start) * 1000, 2);
        $shipment = $this->extractShipment($carrier_response);
        $payload = $carrier_response->getPayload();
        $raw_response = is_array($payload) && isset($payload['raw']) ? $payload['raw'] : $payload;

        return [
            'action' => 'create',
            'success' => $carrier_response->isSuccess(),
            'http_status' => $carrier_response->getHttpStatus(),
            'oauth_status' => $carrier_response->isSuccess() ? 'success' : 'failed',
            'execution_time_ms' => $execution_time_ms,
            'request_json' => wp_json_encode($request->getPayload()),
            'response_json' => wp_json_encode($raw_response),
            'warnings' => $carrier_response->getWarnings(),
            'notes' => $carrier_response->getNotes(),
            'exception' => $carrier_response->isSuccess() ? '' : $this->firstMessage($carrier_response->getWarnings(), $carrier_response->getNotes()),
            'shipment' => $shipment,
            'tracking_number' => $shipment ? $shipment->getTrackingNumber() : null,
            'shipment_id' => $shipment ? $shipment->getShipmentId() : null,
            'master_tracking_number' => $shipment ? $shipment->getMasterTrackingNumber() : null,
            'charges' => $shipment ? $shipment->getCharges() : null,
            'currency' => $shipment ? $shipment->getCurrency() : null,
            'label' => $shipment ? $shipment->getLabel() : null,
        ];
    }

    private function validatePayload(array $state): array {
        $payload = $this->buildPayload($state);
        $request = new CarrierRequest('fedex', $payload, ['carrier' => 'fedex', 'environment' => SettingsPage::get_environment()]);

        try {
            $builder = new ShipRequestBuilder();
            $built = $builder->build($request);

            return [
                'action' => 'validate',
                'success' => true,
                'http_status' => 0,
                'oauth_status' => 'not-run',
                'execution_time_ms' => 0.0,
                'request_json' => wp_json_encode($built),
                'response_json' => '',
                'warnings' => [],
                'notes' => [],
                'exception' => '',
            ];
        } catch (\Throwable $exception) {
            return [
                'action' => 'validate',
                'success' => false,
                'http_status' => 0,
                'oauth_status' => 'not-run',
                'execution_time_ms' => 0.0,
                'request_json' => wp_json_encode($payload),
                'response_json' => '',
                'warnings' => [],
                'notes' => [],
                'exception' => $exception->getMessage(),
            ];
        }
    }

    private function persistRun(array $state, array $result): void {
        if (($result['action'] ?? '') === 'create' && !empty($result['success'])) {
            return;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'fedex_shipments';
        $now = current_time('mysql');
        $status = $result['success'] ? 'created' : ($result['action'] === 'validate' ? 'validation_failed' : 'failed');

        $wpdb->insert($table, [
            'order_id' => null,
            'carrier' => 'fedex',
            'shipment_id' => $result['shipment_id'] ?? null,
            'tracking_number' => $result['tracking_number'] ?? null,
            'master_tracking_number' => $result['master_tracking_number'] ?? null,
            'service_type' => $state['service_type'],
            'shipment_status' => $status,
            'charges' => $result['charges'] ?? null,
            'currency' => $result['currency'] ?? null,
            'label_reference' => $result['label'] ? $result['label']->getReference() : null,
            'environment' => SettingsPage::get_environment(),
            'request_json' => $result['request_json'] ?? '',
            'response_json' => $result['response_json'] ?? '',
            'execution_time_ms' => isset($result['execution_time_ms']) ? $result['execution_time_ms'] : null,
            'warnings' => wp_json_encode($result['warnings'] ?? []),
            'notes' => wp_json_encode($result['notes'] ?? []),
            'errors' => $result['exception'] ?? '',
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            '%d','%s','%s','%s','%s','%s','%s','%f','%s','%s','%s','%s','%s','%f','%s','%s','%s','%s','%s'
        ]);
    }

    private function extractShipment(\FedExShippingPro\Shipping\CarrierResponse $carrier_response): ?Shipment {
        $payload = $carrier_response->getPayload();
        return isset($payload['shipment']) && $payload['shipment'] instanceof Shipment ? $payload['shipment'] : null;
    }

    private function renderDiagnostics(array $result): void {
        echo '<h2>Diagnostics</h2>';
        echo '<table class="widefat striped"><tbody>';
        $this->row('HTTP Status', (string) ($result['http_status'] ?? '0'));
        $this->row('OAuth Status', (string) ($result['oauth_status'] ?? 'n/a'));
        $this->row('Execution Time', isset($result['execution_time_ms']) ? (string) $result['execution_time_ms'] . ' ms' : 'n/a');
        $this->row('Tracking Number', (string) ($result['tracking_number'] ?? ''));
        $this->row('Shipment ID', (string) ($result['shipment_id'] ?? ''));
        $this->row('Master Tracking Number', (string) ($result['master_tracking_number'] ?? ''));
        $this->row('Charges', isset($result['charges']) ? (string) $result['charges'] : '');
        $this->row('Currency', (string) ($result['currency'] ?? ''));
        $this->row('Exception', (string) ($result['exception'] ?? ''));
        echo '</tbody></table>';

        echo '<h2>Request JSON</h2><pre style="white-space:pre-wrap">' . esc_html($this->maskSecrets((string) ($result['request_json'] ?? ''))) . '</pre>';
        echo '<h2>Response JSON</h2><pre style="white-space:pre-wrap">' . esc_html($this->maskSecrets((string) ($result['response_json'] ?? ''))) . '</pre>';
        echo '<h2>Warnings</h2><pre style="white-space:pre-wrap">' . esc_html(implode("\n", $result['warnings'] ?? []) ?: 'None') . '</pre>';
        echo '<h2>Notes</h2><pre style="white-space:pre-wrap">' . esc_html(implode("\n", $result['notes'] ?? []) ?: 'None') . '</pre>';
    }

    private function renderMessage(?array $result): string {
        if (!is_array($result)) {
            return '';
        }

        if (($result['success'] ?? false) === true) {
            return '<div class="notice notice-success"><p>Shipment flow completed successfully.</p></div>';
        }

        if (($result['exception'] ?? '') !== '') {
            return '<div class="notice notice-error"><p>' . esc_html($result['exception']) . '</p></div>';
        }

        return '';
    }

    private function renderSection(string $title, array $fields, array $state): void {
        echo '<tr><th colspan="2"><strong>' . esc_html($title) . '</strong></th></tr>';

        foreach ($fields as $key => $label) {
            echo '<tr>';
            echo '<th scope="row">' . esc_html($label) . '</th>';
            echo '<td><input class="regular-text" name="' . esc_attr($key) . '" value="' . esc_attr((string) ($state[$key] ?? '')) . '" /></td>';
            echo '</tr>';
        }
    }

    private function collectState(array $source): array {
        $defaults = $this->defaultState();
        $state = [];

        foreach ($defaults as $key => $value) {
            $state[$key] = isset($source[$key]) ? sanitize_text_field((string) $source[$key]) : $value;
        }

        return $state;
    }

    private function buildPayload(array $state): array {
        return [
            'account_number' => SettingsPage::get_account_number(),
            'sender' => [
                'name' => 'Sandbox Sender',
                'phone' => '5555555555',
                'address_1' => $state['sender_address'],
                'address_2' => '',
                'city' => $state['sender_city'],
                'state' => '',
                'postal_code' => $state['sender_postal_code'],
                'country_code' => $state['sender_country'],
                'residential' => false,
            ],
            'recipient' => [
                'name' => 'Sandbox Recipient',
                'phone' => '5555555555',
                'address_1' => $state['recipient_address'],
                'address_2' => '',
                'city' => $state['recipient_city'],
                'state' => '',
                'postal_code' => $state['recipient_postal_code'],
                'country_code' => $state['recipient_country'],
                'residential' => false,
            ],
            'packages' => [[
                'weight' => $state['weight'],
                'length' => $state['length'],
                'width' => $state['width'],
                'height' => $state['height'],
            ]],
            'service_type' => $state['service_type'],
            'packaging_type' => $state['packaging_type'],
            'payment' => [
                'type' => 'SENDER',
                'account_number' => SettingsPage::get_account_number(),
            ],
            'label_specification' => [
                'format' => $state['label_format'],
                'image_type' => $state['label_image_type'],
                'stock_type' => 'PAPER_LETTER',
            ],
            'ship_date' => gmdate('Y-m-d'),
        ];
    }

    private function defaultState(): array {
        return [
            'sender_country' => 'US',
            'sender_postal_code' => '38104',
            'sender_city' => 'Memphis',
            'sender_address' => '942 S Shady Grove Rd',
            'recipient_country' => 'US',
            'recipient_postal_code' => '30320',
            'recipient_city' => 'Atlanta',
            'recipient_address' => '1 CNN Center',
            'weight' => '1',
            'length' => '10',
            'width' => '10',
            'height' => '10',
            'service_type' => 'FEDEX_2_DAY',
            'packaging_type' => 'YOUR_PACKAGING',
            'label_format' => 'PDF',
            'label_image_type' => 'PDF',
        ];
    }

    private function maskSecrets(string $value): string {
        return str_replace(['client_secret', 'access_token', 'Authorization', 'Bearer '], ['client_secret***', 'access_token***', 'Authorization***', 'Bearer ***'], $value);
    }

    private function row(string $label, string $value): void {
        echo '<tr><th>' . esc_html($label) . '</th><td>' . esc_html($value) . '</td></tr>';
    }

    private function firstMessage(array $warnings, array $notes): string {
        return (string) ($warnings[0] ?? $notes[0] ?? 'Shipment request failed.');
    }
}
