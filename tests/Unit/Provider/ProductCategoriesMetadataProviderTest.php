<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use FluxSE\SyliusStripePlugin\Provider\ProductCategoriesMetadataProvider;
use PHPUnit\Framework\TestCase;
use Stripe\PaymentIntent;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class ProductCategoriesMetadataProviderTest extends TestCase
{
    /** @var ProductCategoriesMetadataProvider<PaymentIntent> */
    private ProductCategoriesMetadataProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ProductCategoriesMetadataProvider();
    }

    public function test_it_joins_distinct_lowercased_codes(): void
    {
        $paymentRequest = $this->wrapOrderWithTaxonCodes(['MUGS', 'TEA']);

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(['product_categories' => 'mugs,tea'], $params);
    }

    public function test_it_lowercases_mixed_case_codes(): void
    {
        $paymentRequest = $this->wrapOrderWithTaxonCodes(['Kitchen', 'GROCERIES']);

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(['product_categories' => 'kitchen,groceries'], $params);
    }

    public function test_it_deduplicates_repeated_taxon_codes(): void
    {
        $paymentRequest = $this->wrapOrderWithTaxonCodes(['MUGS', 'MUGS', 'MUGS', 'TEA']);

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(['product_categories' => 'mugs,tea'], $params);
    }

    public function test_it_deduplicates_codes_that_differ_only_by_case(): void
    {
        $paymentRequest = $this->wrapOrderWithTaxonCodes(['MUGS', 'mugs', 'Mugs']);

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(['product_categories' => 'mugs'], $params);
    }

    public function test_it_caps_categories_at_ten(): void
    {
        $codes = [];
        for ($i = 0; $i < 15; ++$i) {
            $codes[] = 'CAT_' . $i;
        }

        $paymentRequest = $this->wrapOrderWithTaxonCodes($codes);

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(
            ['product_categories' => 'cat_0,cat_1,cat_2,cat_3,cat_4,cat_5,cat_6,cat_7,cat_8,cat_9'],
            $params,
        );
    }

    public function test_it_omits_key_when_no_taxons(): void
    {
        $paymentRequest = $this->wrapOrderWithTaxonCodes([]);

        $params = [];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame([], $params);
    }

    public function test_it_skips_items_without_product_or_taxon_or_code(): void
    {
        $itemWithoutProduct = $this->createMock(OrderItemInterface::class);
        $itemWithoutProduct->method('getProduct')->willReturn(null);

        $itemWithoutTaxon = $this->createMock(OrderItemInterface::class);
        $productWithoutTaxon = $this->createMock(ProductInterface::class);
        $productWithoutTaxon->method('getMainTaxon')->willReturn(null);
        $itemWithoutTaxon->method('getProduct')->willReturn($productWithoutTaxon);

        $itemWithValidTaxon = $this->createOrderItem('TEA');

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection([
            $itemWithoutProduct,
            $itemWithoutTaxon,
            $itemWithValidTaxon,
        ]));

        $params = [];
        $this->provider->provide($this->wrapOrder($order), $params);

        self::assertSame(['product_categories' => 'tea'], $params);
    }

    public function test_it_returns_early_when_order_is_null(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn(null);
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        $params = ['untouched' => true];
        $this->provider->provide($paymentRequest, $params);

        self::assertSame(['untouched' => true], $params);
    }

    /**
     * @param list<string> $taxonCodes
     */
    private function wrapOrderWithTaxonCodes(array $taxonCodes): PaymentRequestInterface
    {
        $items = [];
        foreach ($taxonCodes as $code) {
            $items[] = $this->createOrderItem($code);
        }

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection($items));

        return $this->wrapOrder($order);
    }

    private function createOrderItem(string $taxonCode): OrderItemInterface
    {
        $taxon = $this->createMock(TaxonInterface::class);
        $taxon->method('getCode')->willReturn($taxonCode);

        $product = $this->createMock(ProductInterface::class);
        $product->method('getMainTaxon')->willReturn($taxon);

        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getProduct')->willReturn($product);

        return $item;
    }

    private function wrapOrder(OrderInterface $order): PaymentRequestInterface
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getOrder')->willReturn($order);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        return $paymentRequest;
    }
}
