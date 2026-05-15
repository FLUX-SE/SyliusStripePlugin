<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\ExpressCheckout\Shipping;

use FluxSE\SyliusStripePlugin\ExpressCheckout\Shipping\ShippingRateAssembler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Shipping\Calculator\DelegatingCalculatorInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;

final class ShippingRateAssemblerTest extends TestCase
{
    /** @var DelegatingCalculatorInterface&MockObject */
    private DelegatingCalculatorInterface $calculator;

    /** @var ShipmentInterface&MockObject */
    private ShipmentInterface $shipment;

    private ShippingRateAssembler $assembler;

    protected function setUp(): void
    {
        $this->calculator = $this->createMock(DelegatingCalculatorInterface::class);
        $this->shipment = $this->createMock(ShipmentInterface::class);
        $this->assembler = new ShippingRateAssembler($this->calculator);
    }

    public function test_it_returns_an_empty_list_when_there_are_no_methods(): void
    {
        $this->calculator->expects(self::never())->method('calculate');

        self::assertSame([], $this->assembler->assemble($this->shipment, [], 'USD'));
    }

    public function test_it_builds_a_rate_per_supported_method_with_lowercase_currency(): void
    {
        $first = $this->createShippingMethod('ups_ground', 'UPS Ground');
        $second = $this->createShippingMethod('dhl_express', null);

        $this->shipment->expects(self::exactly(2))
            ->method('setMethod')
            ->willReturnCallback(function (ShippingMethodInterface $method) use ($first, $second): void {
                static $calls = 0;
                $expected = [$first, $second];
                self::assertSame($expected[$calls], $method);
                ++$calls;
            });

        $this->calculator->method('calculate')
            ->with($this->shipment)
            ->willReturnOnConsecutiveCalls(599, 1299);

        $rates = $this->assembler->assemble($this->shipment, [$first, $second], 'USD');

        self::assertCount(2, $rates);
        self::assertSame('ups_ground', $rates[0]->id);
        self::assertSame('UPS Ground', $rates[0]->displayName);
        self::assertSame(599, $rates[0]->amount);
        self::assertSame('usd', $rates[0]->currency);

        self::assertSame('dhl_express', $rates[1]->id);
        self::assertSame('dhl_express', $rates[1]->displayName, 'falls back to the method code when name is null');
        self::assertSame(1299, $rates[1]->amount);
    }

    public function test_it_passes_through_a_null_currency(): void
    {
        $method = $this->createShippingMethod('flat_rate', 'Flat rate');
        $this->calculator->method('calculate')->willReturn(0);

        $rates = $this->assembler->assemble($this->shipment, [$method], null);

        self::assertNull($rates[0]->currency);
    }

    private function createShippingMethod(string $code, ?string $name): ShippingMethodInterface
    {
        $method = $this->createMock(ShippingMethodInterface::class);
        $method->method('getCode')->willReturn($code);
        $method->method('getName')->willReturn($name);

        return $method;
    }
}
