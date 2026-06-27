<?php
namespace FedExShippingPro\Shipping;

interface CarrierInterface {
    public function getCarrierCode(): string;

    public function createShipment(CarrierRequest $request): CarrierResponse;

    public function voidShipment(CarrierRequest $request): CarrierResponse;

    public function trackShipment(CarrierRequest $request): CarrierResponse;
}
