<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Functional\Provider\Checkout\Create;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use FluxSE\SyliusStripePlugin\Provider\ParamsProviderInterface;
use Stripe\Checkout\Session;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RequestContext;

class DetailsProviderTest extends KernelTestCase
{
    private PurgerLoader $loader;

    private EntityManager $entityManager;

    /** @var ParamsProviderInterface<Session> */
    private ParamsProviderInterface $compositeParamsProvider;

    private RequestContext $requestContext;

    protected function setUp(): void
    {
        $this->loader = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $this->compositeParamsProvider = static::getContainer()->get('flux_se.sylius_stripe.provider.checkout.create.params');

        $this->requestContext = static::getContainer()->get('router.request_context');

        $this->purgeDatabase();
    }

    /**
     * @param string[] $files
     *
     * @return object[]
     */
    protected function loadFixtures(array $files): array
    {
        foreach ($files as $i => $file) {
            $files[$i] = sprintf('%s/../Functional/DataFixtures/ORM/%s', static::$kernel->getProjectDir(), $file);
        }

        return $this->loader->load($files);
    }

    protected function purgeDatabase(): void
    {
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        $this->entityManager->clear();
    }

    /**
     * @dataProvider getPaymentRequestAndExpectedDetails
     *
     * @param array{
     *     metadata: array{token_hash: string},
     *     success_url: string,
     *     cancel_url: string,
     * } $expectedDetails
     */
    public function test_it_get_checkout_session_create_details(
        string $paymentRequestName,
        array $expectedDetails,
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
            'stripe_checkout/payment_request.yaml',
        ]);

        /** @var PaymentRequestInterface $paymentRequest */
        $paymentRequest = $fixtures[$paymentRequestName];

        $expectedDetails['metadata']['token_hash'] = $paymentRequest->getId();
        if (null === $paymentRequest->getPayload()) {
            // Using Shop UI, the locale context is given by the current request context, here we forced it.
            $locale = 'en_US';
            $this->requestContext->setParameter('_locale', $locale);

            $url = sprintf('http://localhost/%s/payment-request/pay/%s', $locale, $paymentRequest->getId());
            $expectedDetails['success_url'] = $url;
            $expectedDetails['cancel_url'] = $url;
        }

        $details = $this->compositeParamsProvider->getParams($paymentRequest);

        if (isset($details['expand'])) {
            $expectedDetails['expand'] = $details['expand'];
        }

        self::assertEquals($expectedDetails, $details);

        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();

        // Check if tests data are corresponding
        self::assertEquals(
            $payment->getAmount(),
            $payment->getOrder()?->getTotal(),
        );
    }

    /**
     * @return iterable<array{string, mixed[]}>
     */
    public static function getPaymentRequestAndExpectedDetails(): iterable
    {
        $expected = [
            'customer_email' => 'oliver@doe.com',
            'line_items' => [
                [
                    'price_data' => [
                        'unit_amount' => 1000,
                        'currency' => 'USD',
                        'product_data' => [
                            'name' => '1x - Mug',
                            'images' => [
                                'https://placehold.co/300',
                            ],
                        ],
                    ],
                    'quantity' => 1,
                ],
                [
                    'price_data' => [
                        'unit_amount' => 500,
                        'currency' => 'USD',
                        'product_data' => [
                            'name' => 'UPS',
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => 'https://myshop.tld/target-path',
            'cancel_url' => 'https://myshop.tld/after-path',
            'metadata' => [
                'token_hash' => '',
            ],
        ];

        yield 'capture' => [
            'payment_request_capture',
            $expected,
        ];

        yield 'capture_via_api' => [
            'payment_request_capture_via_api',
            array_merge($expected, [
                'success_url' => 'https://myshop.tld/target-path',
                'cancel_url' => 'https://myshop.tld/after-path',
            ]),
        ];

        yield 'authorize' => [
            'payment_request_authorize',
            array_merge($expected, [
                'payment_intent_data' => [
                    'capture_method' => 'manual',
                ],
            ]),
        ];

        yield 'authorize_via_api' => [
            'payment_request_authorize_via_api',
            array_merge($expected, [
                'success_url' => 'https://myshop.tld/target-path',
                'cancel_url' => 'https://myshop.tld/after-path',
                'payment_intent_data' => [
                    'capture_method' => 'manual',
                ],
            ]),
        ];
    }
}
