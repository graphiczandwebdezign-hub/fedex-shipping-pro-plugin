<?php
namespace FedExShippingPro\Api;

class FedExApiClient {

    public function rateEndpoint(string $environment): string {
        return $environment === 'production'
            ? 'https://apis.fedex.com/rate/v1/rates/quotes'
            : 'https://apis-sandbox.fedex.com/rate/v1/rates/quotes';
    }

    public function post(string $endpoint, array $payload, string $access_token): array {
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'X-locale' => 'en_US',
            ],
            'body' => wp_json_encode($payload),
            'timeout' => 60,
        ]);

        if (function_exists('is_wp_error') && is_wp_error($response)) {
            return [
                'status' => 0,
                'body' => '',
                'error' => $response->get_error_message(),
                'response_message' => '',
            ];
        }

        return [
            'status' => (int) wp_remote_retrieve_response_code($response),
            'body' => (string) wp_remote_retrieve_body($response),
            'error' => '',
            'response_message' => (string) wp_remote_retrieve_response_message($response),
        ];
    }
}
