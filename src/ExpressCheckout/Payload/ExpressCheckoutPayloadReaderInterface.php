<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Payload;

use Symfony\Component\HttpFoundation\Request;

interface ExpressCheckoutPayloadReaderInterface
{
    public function read(Request $request): ExpressCheckoutPayload;
}
