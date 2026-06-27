<?php
namespace FedExShippingPro\Auth;

use FedExShippingPro\Admin\SettingsPage;

class FedExAuthService {
    private const EXPIRY_SAFETY_WINDOW = 300;

    private function tokenEndpoint(): string {
        return SettingsPage::get_environment() === 'production'
            ? 'https://apis.fedex.com/oauth/token'
            : 'https://apis-sandbox.fedex.com/oauth/token';
    }

    private function tokenOption(): string {
        return 'fedex_access_token_' . SettingsPage::get_environment();
    }

    private function tokenExpiresOption(): string {
        return 'fedex_access_token_expires_at_' . SettingsPage::get_environment();
    }

    public function getAccessToken(bool $force_refresh = false): array {
        if (!$force_refresh) {
            $cached_token = $this->getCachedToken();

            if ($cached_token !== null) {
                return [
                    'success' => true,
                    'status' => 200,
                    'access_token' => $cached_token['token'],
                    'expires_in' => max(0, $cached_token['expires_at'] - time()),
                    'cached' => true,
                ];
            }
        }

        return $this->requestToken();
    }

    public function requestToken(): array {
        $response = wp_remote_post($this->tokenEndpoint(), [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => SettingsPage::get_client_id(),
                'client_secret' => SettingsPage::get_client_secret(),
            ],
            'timeout' => 60,
        ]);

        if (function_exists('is_wp_error') && is_wp_error($response)) {
            return [
                'success' => false,
                'status' => 0,
                'error' => $response->get_error_message(),
            ];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $raw_body = (string) wp_remote_retrieve_body($response);
        $body = json_decode($raw_body, true);

        if (!is_array($body)) {
            return [
                'success' => false,
                'status' => $status,
                'error' => 'Unable to decode OAuth response.',
                'response' => $raw_body,
            ];
        }

        if (!empty($body['access_token'])) {
            $token = (string) $body['access_token'];
            $expires_in = (int) ($body['expires_in'] ?? 0);
            $expires_at = time() + max(0, $expires_in) - self::EXPIRY_SAFETY_WINDOW;

            if ($expires_at <= time()) {
                $expires_at = time() + max(60, $expires_in);
            }

            $this->storeToken($token, $expires_at);

            return [
                'success' => true,
                'status' => $status,
                'expires_in' => $expires_in,
                'access_token' => $token,
                'cached' => false,
            ];
        }

        return [
            'success' => false,
            'status' => $status,
            'error' => (string) ($body['error_description'] ?? $body['error'] ?? 'OAuth request failed.'),
            'response' => $body,
        ];
    }

    public function clearCachedToken(): void {
        delete_option($this->tokenOption());
        delete_option($this->tokenExpiresOption());
    }

    private function getCachedToken(): ?array {
        $token = (string) get_option($this->tokenOption(), '');
        $expires_at = (int) get_option($this->tokenExpiresOption(), 0);

        if ($token === '' || $expires_at <= time()) {
            return null;
        }

        return [
            'token' => $token,
            'expires_at' => $expires_at,
        ];
    }

    private function storeToken(string $token, int $expires_at): void {
        update_option($this->tokenOption(), $token, false);
        update_option($this->tokenExpiresOption(), $expires_at, false);
    }
}
