# Shipping Domain Architecture

## Purpose

The Shipping domain is a carrier-agnostic foundation for shipment creation, labels, tracking, returns, pickup scheduling, and multi-package shipments.

It is intentionally separate from the live rate engine.

## Core Principles

- Preserve the existing WooCommerce rate path.
- Keep FedEx-specific code isolated under provider namespaces later.
- Use small value objects for shipment data.
- Keep transport, persistence, and UI concerns separate.
- Avoid introducing abstractions that do not have a near-term purpose.

## Namespace Layout

- `src/Shipping/`
- Future carrier-specific code should live under provider namespaces such as `src/FedEx/`

## Classes

- `Shipment`
  - Canonical shipment record used across carriers.
- `ShipmentStatus`
  - Standard status constants for shipment lifecycle states.
- `Label`
  - Label format and storage reference.
- `TrackingInfo`
  - Normalized tracking state and event container.
- `CarrierInterface`
  - Contract implemented by carriers that can create, void, and track shipments.
- `CarrierRequest`
  - Generic carrier request wrapper.
- `CarrierResponse`
  - Generic carrier response wrapper.
- `ShipmentBuilder`
  - Converts WooCommerce order/shipment input into canonical shipment data.
- `ShipmentRepository`
  - Persists and retrieves shipments from the database.
- `ShipmentService`
  - Coordinates shipment creation workflow and persistence.

## Data Flow

WooCommerce Order -> ShipmentBuilder -> ShipmentService -> CarrierInterface implementation -> CarrierResponse -> ShipmentRepository

Tracking and label workflows use the same canonical `Shipment` and `TrackingInfo` objects.

## Persistence Model

Recommended shipment table fields:

- WooCommerce order ID
- carrier
- shipment ID
- tracking number
- master tracking number
- service type
- shipment status
- charges
- currency
- label reference
- environment
- created date
- updated date

## Future Extension Strategy

Future carriers should implement the same interface without requiring changes to the Shipping domain.

Examples:

- UPS
- DHL
- USPS
- Aramex
- The Courier Guy
- Pargo

Provider-specific request and response handling should remain outside the core domain.
