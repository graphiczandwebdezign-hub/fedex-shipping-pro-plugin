<?php
namespace FedExShippingPro\FedEx\Ship;

use FedExShippingPro\Api\FedExApiClient;
use FedExShippingPro\Admin\SettingsPage;
use FedExShippingPro\Auth\FedExAuthService;
use FedExShippingPro\Logging\Logger;
use FedExShippingPro\Shipping\CarrierInterface;
use FedExShippingPro\Shipping\CarrierRequest;
use FedExShippingPro\Shipping\CarrierResponse;
use FedExShippingPro\Shipping\Shipment;
use FedExShippingPro\Shipping\ShipmentRepository;
use FedExShippingPro\Shipping\ShipmentStatus;

class FedExShipmentProvider implements CarrierInterface {
    private FedExAuthService $auth_service;
    private FedExApiClient $api_client;
    private ShipRequestBuilder $request_builder;
    private ShipResponseParser $response_parser;
    private ShipmentRepository $shipment_repository;

    public function __construct(
        ?FedExAuthService $auth_service = null,
        ?FedExApiClient $api_client = null,
        ?ShipRequestBuilder $request_builder = null,
        ?ShipResponseParser $response_parser = null,
        ?ShipmentRepository $shipment_repository = null
    ) {
        $this->auth_service = $auth_service ?? new FedExAuthService();
        $this->api_client = $api_client ?? new FedExApiClient();
        $this->request_builder = $request_builder ?? new ShipRequestBuilder();
        $this->response_parser = $response_parser ?? new ShipResponseParser();
        $this->shipment_repository = $shipment_repository ?? new ShipmentRepository();
    }

    public function getCarrierCode(): string {
        return 'fedex';
    }

    public function createShipment(CarrierRequest $request): CarrierResponse {
        $start = microtime(true);

        try {
            $endpoint = $this->shipEndpoint();
            $oauth = $this->auth_service->getAccessToken();

            if (empty($oauth['success']) || empty($oauth['access_token'])) {
                return new CarrierResponse(false, isset($oauth['status']) ? (int) $oauth['status'] : 0, [], [], [$this->stringValue($oauth['error'] ?? 'OAuth token request failed.')]);
            }

            $payload = $this->request_builder->build($request);
            $response = $this->api_client->post($endpoint, $payload, (string) $oauth['access_token']);

            if ($this->isUnauthorized($response)) {
                $this->auth_service->clearCachedToken();
                $retry = $this->auth_service->getAccessToken(true);

                if (empty($retry['success']) || empty($retry['access_token'])) {
                    return new CarrierResponse(false, 401, [], [], [$this->stringValue($retry['error'] ?? 'FedEx authentication failed.')]);
                }

                $response = $this->api_client->post($endpoint, $payload, (string) $retry['access_token']);
            }

            $normalized = $this->response_parser->parse($response, $request->getMeta());
            $this->logInteraction($request, $response, $start, $normalized);

            if (!$normalized->isSuccess()) {
                return $normalized;
            }

            $shipment = $this->extractShipment($normalized);

            if ($shipment instanceof Shipment) {
                $saved = $this->shipment_repository->save($shipment);

                if (!$saved) {
                    Logger::info('FedEx shipment persisted failed for order ' . (string) ($shipment->getOrderId() ?? 'unknown'));
                }
            }

            return $normalized;
        } catch (FedExShipmentException $exception) {
            Logger::info('FedEx shipment validation failed: ' . $exception->getMessage());
            return new CarrierResponse(false, 0, [], [], [$exception->getMessage()]);
        } catch (\Throwable $exception) {
            Logger::info('FedEx shipment exception: ' . $exception->getMessage());
            return new CarrierResponse(false, 0, [], [], ['Unable to create FedEx shipment.']);
        }
    }

    public function voidShipment(CarrierRequest $request): CarrierResponse {
        return new CarrierResponse(false, 0, [], [], ['Shipment voiding is not implemented in this sprint.']);
    }

    public function trackShipment(CarrierRequest $request): CarrierResponse {
        return new CarrierResponse(false, 0, [], [], ['Shipment tracking is not implemented in this sprint.']);
    }

    private function shipEndpoint(): string {
        $environment = $this->environment();

        return $environment === 'production'
            ? 'https://apis.fedex.com/ship/v1/shipments'
            : 'https://apis-sandbox.fedex.com/ship/v1/shipments';
    }

    private function environment(): string {
        return SettingsPage::get_environment();
    }

    private function extractShipment(CarrierResponse $response): ?Shipment {
        $payload = $response->getPayload();
        $shipment = $payload['shipment'] ?? null;

        return $shipment instanceof Shipment ? $shipment : null;
    }

    private function isUnauthorized(array $response): bool {
        return isset($response['status']) && (int) $response['status'] === 401;
    }

    private function logInteraction(CarrierRequest $request, array $response, float $start, CarrierResponse $normalized): void {
        $execution_time = round((microtime(true) - $start) * 1000, 2);
        $request_json = wp_json_encode($request->getPayload());
        $response_json = wp_json_encode($response);

        Logger::info(sprintf(
            'FedEx shipment request completed: status=%s execution_time=%sms warnings=%s notes=%s request=%s response=%s',
            (string) ($response['status'] ?? 0),
            (string) $execution_time,
            implode('|', $normalized->getWarnings()),
            implode('|', $normalized->getNotes()),
            $this->maskSecrets((string) $request_json),
            $this->maskSecrets((string) $response_json)
        ));
    }

    private function maskSecrets(string $value): string {
        return str_replace(['Bearer ', 'client_secret', 'access_token', 'Authorization'], ['Bearer ***', 'client_secret***', 'access_token***', 'Authorization***'], $value);
    }

    private function stringValue($value): string {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
