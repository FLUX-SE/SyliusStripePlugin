<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Webmozart\Assert\Assert;

class StripeContext implements Context
{
    /**
     * @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository
     */
    public function __construct(
        private SharedStorageInterface $sharedStorage,
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private ExampleFactoryInterface $paymentMethodExampleFactory,
        private EntityManagerInterface $paymentMethodManager,
    ) {
    }

    /**
     * @Given the store has a payment method :paymentMethodName with a code :paymentMethodCode and Stripe Checkout payment gateway
     * @Given the store has a payment method :paymentMethodName with a code :paymentMethodCode and Stripe Checkout payment gateway without using authorize
     */
    public function theStoreHasAPaymentMethodWithACodeAndStripeCheckoutSessionPaymentGateway(
        string $paymentMethodName,
        string $paymentMethodCode,
        bool $useAuthorize = false,
    ): void {
        $paymentMethod = $this->createPaymentMethodStripe(
            $paymentMethodName,
            $paymentMethodCode,
            'stripe_checkout',
            'Stripe (Checkout)',
        );

        $this->createPaymentMethod($paymentMethod, $useAuthorize);
    }

    /**
     * @Given the store has a payment method :paymentMethodName with a code :paymentMethodCode and Stripe Checkout payment gateway using authorize
     */
    public function theStoreHasAPaymentMethodWithACodeAndStripeCheckoutSessionPaymentGatewayUsingAuthorize(
        string $paymentMethodName,
        string $paymentMethodCode,
    ): void {
        $this->theStoreHasAPaymentMethodWithACodeAndStripeCheckoutSessionPaymentGateway($paymentMethodName, $paymentMethodCode, true);
    }

    /**
     * @Given the store has a payment method :paymentMethodName with a code :paymentMethodCode and Stripe Web Elements payment gateway
     * @Given the store has a payment method :paymentMethodName with a code :paymentMethodCode and Stripe Web Elements payment gateway without using authorize
     */
    public function theStoreHasAPaymentMethodWithACodeAndStripeJsPaymentGateway(
        string $paymentMethodName,
        string $paymentMethodCode,
        bool $useAuthorize = false,
    ): void {
        $paymentMethod = $this->createPaymentMethodStripe(
            $paymentMethodName,
            $paymentMethodCode,
            'stripe_web_elements',
            'Stripe (Web Elements)',
        );

        $this->createPaymentMethod($paymentMethod, $useAuthorize);
    }

    /**
     * @Given the store has a payment method :paymentMethodName with a code :paymentMethodCode and Stripe Web Elements payment gateway using authorize
     */
    public function theStoreHasAPaymentMethodWithACodeAndStripeJsPaymentGatewayUsingAuthorize(
        string $paymentMethodName,
        string $paymentMethodCode,
    ): void {
        $this->theStoreHasAPaymentMethodWithACodeAndStripeJsPaymentGateway($paymentMethodName, $paymentMethodCode, true);
    }

    private function createPaymentMethodStripe(
        string $name,
        string $code,
        string $factoryName,
        string $description = '',
        bool $addForCurrentChannel = true,
        int $position = null,
    ): PaymentMethodInterface {
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $this->paymentMethodExampleFactory->create([
            'name' => ucfirst($name),
            'code' => $code,
            'description' => $description,
            'gatewayName' => $factoryName,
            'gatewayFactory' => $factoryName,
            'enabled' => true,
            'channels' => ($addForCurrentChannel && $this->sharedStorage->has('channel')) ? [$this->sharedStorage->get('channel')] : [],
        ]);
        if (null !== $position) {
            $paymentMethod->setPosition($position);
        }
        $this->sharedStorage->set('payment_method', $paymentMethod);
        $this->paymentMethodRepository->add($paymentMethod);

        return $paymentMethod;
    }

    private function createPaymentMethod(PaymentMethodInterface $paymentMethod, bool $useAuthorize): void
    {
        /** @var GatewayConfigInterface|null $gatewayConfig */
        $gatewayConfig = $paymentMethod->getGatewayConfig();
        Assert::notNull($gatewayConfig);

        $gatewayConfig->setUsePayum(false);

        $gatewayConfig->setConfig([
            'publishable_key' => 'pk_test_publishablekey',
            'secret_key' => 'sk_test_secretkey',
            'webhook_secret_keys' => [
                'whsec_test_1',
            ],
            'use_authorize' => $useAuthorize,
        ]);
        $this->paymentMethodManager->flush();
    }
}
