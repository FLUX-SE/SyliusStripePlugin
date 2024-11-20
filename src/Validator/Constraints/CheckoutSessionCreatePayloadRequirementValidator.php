<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Validator\Constraints;

use Sylius\Bundle\ApiBundle\Command\Payment\AddPaymentRequest;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class CheckoutSessionCreatePayloadRequirementValidator extends ConstraintValidator
{
    /**
     * @param string[] $supportedFactoryNames
     * @param string[] $supportedActions
     * @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository
     */
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private GatewayFactoryNameProviderInterface $gatewayFactoryNameProvider,
        private array $supportedFactoryNames,
        private array $supportedActions,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($constraint, CheckoutSessionCreatePayloadRequirement::class);

        $addPaymentRequest = $this->context->getObject();
        Assert::isInstanceOf($addPaymentRequest, AddPaymentRequest::class);

        /** @var PaymentMethodInterface|null $paymentMethod */
        $paymentMethod = $this->paymentMethodRepository->findOneBy([
            'code' => $addPaymentRequest->paymentMethodCode
        ]);

        if (null === $paymentMethod) {
            return;
        }

        if (false === in_array(
            $this->gatewayFactoryNameProvider->provide($paymentMethod),
            $this->supportedFactoryNames,
            true
        )) {
            return;
        }

        if (false === in_array(
            $addPaymentRequest->action,
            $this->supportedActions,
            true
        )) {
            return;
        }

        if (false === is_array($value)) {
            return;
        }

        if (false === isset($value['success_url'])) {
            $this->context->addViolation($constraint->noSuccessUrlFound);
        }

        if (false === isset($value['cancel_url'])) {
            $this->context->addViolation($constraint->noCancelUrlFound);
        }
    }
}
