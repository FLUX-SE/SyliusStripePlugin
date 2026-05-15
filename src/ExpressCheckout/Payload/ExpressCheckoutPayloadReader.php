<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout\Payload;

use Symfony\Component\HttpFoundation\Request;

final readonly class ExpressCheckoutPayloadReader implements ExpressCheckoutPayloadReaderInterface
{
    public function read(Request $request): ExpressCheckoutPayload
    {
        $content = $request->getContent();
        if ('' === $content) {
            return new ExpressCheckoutPayload([]);
        }

        try {
            $decoded = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new ExpressCheckoutPayload([]);
        }

        return new ExpressCheckoutPayload(is_array($decoded) ? $decoded : []);
    }
}
