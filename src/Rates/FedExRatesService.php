<?php
namespace FedExShippingPro\Rates;

use FedExShippingPro\Api\FedExApiClient;
use FedExShippingPro\Auth\FedExAuthService;
use FedExShippingPro\Logging\Logger;
use FedExShippingPro\WooCommerce\PackageBuilder;
use FedExShippingPro\Admin\SettingsPage;

class FedExRatesService {

    private PackageBuilder $package_builder;

    private RateRequestBuilder $request_builder;

    private RateResponseParser $response_parser;

    private FedExApiClient $api_client;

    private FedExAuthService $auth_service;

    private ?string $last_request_json = null;

    private ?string $last_response_json = null;

    private ?array $last_parsed_response = null;

    private ?float $execution_time = null;

    private ?int $http_status = null;

    private ?string $oauth_status = null;

    private ?array $last_warnings = null;

    private ?array $last_notes = null;

    private ?string $last_exception = null;

    public function __construct(
        ?PackageBuilder $package_builder = null,
        ?RateRequestBuilder $request_builder = null,
        ?RateResponseParser $response_parser = null,
        ?FedExApiClient $api_client = null,
        ?FedExAuthService $auth_service = null
    ) {
        $this->package_builder = $package_builder ?? new PackageBuilder();
        $this->request_builder = $request_builder ?? new RateRequestBuilder();
        $this->response_parser = $response_parser ?? new RateResponseParser();
        $this->api_client = $api_client ?? new FedExApiClient();
        $this->auth_service = $auth_service ?? new FedExAuthService();
    }

    public function getCheckoutRates(array $package): array {
        $start = microtime(true);
        $this->resetDiagnostics();

        try {
            $built_package = $this->package_builder->build($package);
            $request_payload = $this->request_builder->build($built_package);
            $this->last_request_json = $this->encodeJson($request_payload);

            $endpoint = $this->api_client->rateEndpoint(SettingsPage::get_environment());
            $normalized = $this->executeRateRequest($endpoint, $request_payload);

            if ($normalized === null) {
                return [];
            }

            $this->last_parsed_response = $normalized;

            return $this->convertToWooRates($normalized);
        } catch (\Throwable $exception) {
            $this->last_exception = $exception->getMessage();
            Logger::info('FedEx rate exception: ' . $this->last_exception);
            return [];
        } finally {
            $this->execution_time = round((microtime(true) - $start) * 1000, 2);
        }
    }

    public function getLastRequestJson(): ?string {
        return $this->last_request_json;
    }

    public function getLastResponseJson(): ?string {
        return $this->last_response_json;
    }

    public function getLastParsedResponse(): ?array {
        return $this->last_parsed_response;
    }

    public function getExecutionTime(): ?float {
        return $this->execution_time;
    }

    public function getHttpStatus(): ?int {
        return $this->http_status;
    }

    public function getOAuthStatus(): ?string {
        return $this->oauth_status;
    }

    public function getLastWarnings(): array {
        return $this->last_warnings ?? [];
    }

    public function getLastNotes(): array {
        return $this->last_notes ?? [];
    }

    public function getLastException(): ?string {
        return $this->last_exception;
    }

    private function convertToWooRates(array $normalized_rates): array {
        $rates = [];

        foreach ($normalized_rates as $rate) {
            if (!is_array($rate)) {
                continue;
            }

            $service_name = $this->stringValue($rate['service_name'] ?? '');
            $service_code = $this->stringValue($rate['service_code'] ?? '');
            $label = $service_name !== '' ? $service_name : $service_code;

            if ($label === '') {
                continue;
            }

            $rates[] = [
                'service' => $label,
                'service_code' => $service_code,
                'service_name' => $service_name,
                'currency' => $this->stringValue($rate['currency'] ?? ''),
                'amount' => isset($rate['amount']) && is_numeric($rate['amount']) ? (float) $rate['amount'] : 0.0,
                'transit_days' => $this->stringValue($rate['transit_days'] ?? ''),
                'delivery_date' => $this->stringValue($rate['delivery_date'] ?? $rate['estimated_delivery'] ?? ''),
                'estimated_delivery' => $this->stringValue($rate['estimated_delivery'] ?? $rate['delivery_date'] ?? ''),
                'raw' => $rate['raw'] ?? [],
            ];
        }

        return $rates;
    }

    private function executeRateRequest(string $endpoint, array $request_payload): ?array {
        $oauth = $this->auth_service->getAccessToken();
        $this->oauth_status = !empty($oauth['success']) ? 'success' : 'failed';

        if (empty($oauth['success']) || empty($oauth['access_token'])) {
            $this->last_exception = $this->stringValue($oauth['error'] ?? 'OAuth token request failed.');
            $this->http_status = isset($oauth['status']) ? (int) $oauth['status'] : null;
            Logger::info('FedEx OAuth failed: ' . $this->last_exception);
            return null;
        }

        $response = $this->api_client->post($endpoint, $request_payload, (string) $oauth['access_token']);

        if ($this->isUnauthorizedResponse($response)) {
            $this->auth_service->clearCachedToken();
            $retry_oauth = $this->auth_service->getAccessToken(true);
            $this->oauth_status = !empty($retry_oauth['success']) ? 'refreshed' : 'failed';

            if (!empty($retry_oauth['success']) && !empty($retry_oauth['access_token'])) {
                $response = $this->api_client->post($endpoint, $request_payload, (string) $retry_oauth['access_token']);
            } elseif (empty($retry_oauth['success'])) {
                $this->last_exception = $this->stringValue($retry_oauth['error'] ?? 'OAuth token refresh failed.');
                Logger::info('FedEx OAuth refresh failed: ' . $this->last_exception);
                return null;
            }
        }

        $this->http_status = isset($response['status']) ? (int) $response['status'] : null;
        $this->last_response_json = isset($response['body']) ? (string) $response['body'] : null;

        if (!empty($response['error'])) {
            $this->last_exception = (string) $response['error'];
            Logger::info('FedEx rate API error: ' . $this->last_exception);
            return null;
        }

        if ($this->http_status !== null && $this->http_status >= 400) {
            $this->last_exception = $this->responseErrorMessage($response, $this->http_status);
            Logger::info('FedEx rate API HTTP ' . $this->http_status . ': ' . $this->last_exception);
            return null;
        }

        $decoded = json_decode((string) $this->last_response_json, true);

        if (!is_array($decoded)) {
            $this->last_exception = 'Unable to decode FedEx rate response.';
            Logger::info($this->last_exception);
            return null;
        }

        $normalized = $this->response_parser->parse($decoded);
        $this->last_warnings = $this->response_parser->getWarnings();
        $this->last_notes = $this->response_parser->getNotes();

        if ($normalized === []) {
            $warnings = array_merge($this->last_warnings, $this->last_notes);
            if ($warnings !== []) {
                $this->last_exception = $warnings[0];
            }
        }

        return $normalized;
    }

    private function isUnauthorizedResponse(array $response): bool {
        return isset($response['status']) && (int) $response['status'] === 401;
    }

    private function responseErrorMessage(array $response, int $status): string {
        $body = (string) ($response['body'] ?? '');

        if ($body !== '') {
            $decoded = json_decode($body, true);

            if (is_array($decoded)) {
                foreach ([
                    $decoded['errors'][0]['message'] ?? null,
                    $decoded['errors'][0]['message'] ?? null,
                    $decoded['error_description'] ?? null,
                    $decoded['message'] ?? null,
                    $decoded['title'] ?? null,
                ] as $candidate) {
                    $message = $this->stringValue($candidate);

                    if ($message !== '') {
                        return $message;
                    }
                }
            }
        }

        if (!empty($response['response_message'])) {
            return 'FedEx request failed with HTTP ' . $status . ' (' . $this->stringValue($response['response_message']) . ').';
        }

        return 'FedEx request failed with HTTP ' . $status . '.';
    }

    private function resetDiagnostics(): void {
        $this->last_request_json = null;
        $this->last_response_json = null;
        $this->last_parsed_response = null;
        $this->execution_time = null;
        $this->http_status = null;
        $this->oauth_status = null;
        $this->last_warnings = [];
        $this->last_notes = [];
        $this->last_exception = null;
    }

    private function encodeJson(array $payload): ?string {
        $json = wp_json_encode($payload);

        return is_string($json) ? $json : null;
    }

    private function stringValue($value): string {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
