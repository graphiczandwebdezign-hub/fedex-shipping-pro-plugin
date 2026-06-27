# Shipping Domain and FedEx Provider

## Purpose

The Shipping domain is carrier-agnostic.

FedEx is the first provider implementation of that domain.

## Domain Flow

WooCommerce Order data -> `CarrierRequest` -> `FedExShipmentProvider` -> `ShipRequestBuilder` -> FedEx Ship API -> `ShipResponseParser` -> `CarrierResponse` -> `ShipmentRepository`

## Carrier Boundary

- `src/Shipping/` contains the reusable shipping domain.
- `src/FedEx/Ship/` contains the FedEx provider implementation.
- The provider does not contain WooCommerce-specific UI logic.

## Reusable Concepts

- `Shipment`
- `ShipmentStatus`
- `Label`
- `TrackingInfo`
- `CarrierInterface`
- `CarrierRequest`
- `CarrierResponse`
- `ShipmentRepository`

## Provider Responsibilities

- validate shipment input
- authenticate with FedEx
- build the Ship API request
- submit the request
- parse the response
- normalize into the Shipping domain
- persist successful shipments

## Logging Strategy

Shipment activity should be logged with:

- request payload
- response payload
- HTTP status
- execution time
- warnings
- errors

Secrets must remain masked.

## Extension Strategy

Future providers can implement `CarrierInterface` without changing the Shipping domain.

Examples:

- UPS
- DHL
- USPS
- Aramex
- The Courier Guy
- Pargo
