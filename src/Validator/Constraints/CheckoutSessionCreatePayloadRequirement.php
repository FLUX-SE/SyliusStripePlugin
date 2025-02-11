<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class CheckoutSessionCreatePayloadRequirement extends Constraint
{
    public string $noSuccessUrlFound = 'flux_se_sylius_stripe_plugin.stripe_checkout.success_url.not_found';

    public string $noCancelUrlFound = 'flux_se_sylius_stripe_plugin.stripe_checkout.cancel_url.not_found';

    public function validatedBy(): string
    {
        return 'flux_se_sylius_stripe_checkout_session_create_payload_requirement';
    }
}
