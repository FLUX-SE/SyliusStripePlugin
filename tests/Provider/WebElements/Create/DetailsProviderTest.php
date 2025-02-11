<?php

namespace Provider\WebElements\Create;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Component\Payment\Model\PaymentRequest;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DetailsProviderTest extends KernelTestCase
{
    private PurgerLoader $loader;
    private EntityManager $entityManager;

    /** @var ParamsProviderInterface<PaymentIntent> */
    private ParamsProviderInterface $compositeParamsProvider;

    protected function setUp(): void
    {
        $this->loader = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $this->compositeParamsProvider = static::getContainer()->get('flux_se.sylius_stripe.provider.web_elements.create.params');

        $this->purgeDatabase();
    }

    protected function purgeDatabase(): void
    {
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        $this->entityManager->clear();
    }

    /**
     * @param string[] $files
     * @return object[]
     */
    protected function loadFixtures(array $files): array
    {
        foreach ($files as $i=>$file) {
            $files[$i] = sprintf('%s/../DataFixtures/ORM/%s', static::$kernel->getProjectDir(), $file);
        }

        return $this->loader->load($files);
    }

    /**
     * @dataProvider getPaymentRequestAndExpectedDetails
     *
     * @param array{
     *     metadata: array{
     *         token_hash: string
     *     }
     * } $expectedDetails
     */
    public function test_it_get_checkout_session_create_details(
        string $paymentRequestName,
        array $expectedDetails
    ): void {
        $fixtures = $this->loadFixtures([
            'channel.yaml',
            'customer.yaml',
            'payment.yaml',
            'payment_method.yaml',
            'product_variant.yaml',
            'shipping_category.yaml',
            'tax_category.yaml',
            'shipping_method.yaml',
            'order_awaiting_payment.yaml',
            'stripe_web_elements/payment_request.yaml',
        ]);

        /** @var PaymentRequestInterface $paymentRequest */
        $paymentRequest = $fixtures[$paymentRequestName];

        $params = $this->compositeParamsProvider->getParams($paymentRequest);

        $expectedDetails['metadata']['token_hash'] = $paymentRequest->getId();

        self::assertEquals($expectedDetails, $params);
    }

    /**
     * @return iterable<array{string, mixed[]}>
     */
    public static function getPaymentRequestAndExpectedDetails(): iterable
    {
        $expected = [
            'amount' => 1500,
            'currency' => 'USD',
            'metadata' => [
                'token_hash' => '',
            ],
        ];

        yield 'capture' => [
            'payment_request_capture',
            $expected,
        ];

        yield 'authorize' => [
            'payment_request_authorize',
            array_merge($expected, [
                'capture_method' => 'manual',
            ]),
        ];
    }
}
