<?php
namespace FedExShippingPro\Shipping;

final class Label {
    private string $format;
    private ?string $reference;
    private ?string $url;
    private ?string $base64;
    private ?string $mime_type;

    public function __construct(
        string $format,
        ?string $reference = null,
        ?string $url = null,
        ?string $base64 = null,
        ?string $mime_type = null
    ) {
        $this->format = strtoupper(trim($format));
        $this->reference = $reference !== null ? trim($reference) : null;
        $this->url = $url !== null && trim($url) !== '' ? trim($url) : null;
        $this->base64 = $base64 !== null && trim($base64) !== '' ? trim($base64) : null;
        $this->mime_type = $mime_type !== null && trim($mime_type) !== '' ? trim($mime_type) : null;
    }

    public function getFormat(): string {
        return $this->format;
    }

    public function getReference(): ?string {
        return $this->reference;
    }

    public function getUrl(): ?string {
        return $this->url;
    }

    public function getBase64(): ?string {
        return $this->base64;
    }

    public function getMimeType(): ?string {
        return $this->mime_type;
    }
}
