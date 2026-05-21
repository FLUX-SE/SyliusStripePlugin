<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Twig\Extension;

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use FluxSE\SyliusStripePlugin\Twig\Extension\LegacyStripeKeyExtension;
use PHPUnit\Framework\TestCase;

final class LegacyStripeKeyExtensionTest extends TestCase
{
    public function test_it_exposes_the_legacy_secret_key_function(): void
    {
        $detector = $this->createStub(LegacyKeyDetectorInterface::class);

        $functions = (new LegacyStripeKeyExtension($detector))->getFunctions();

        self::assertCount(1, $functions);
        self::assertSame('sylius_stripe_is_legacy_secret_key', $functions[0]->getName());
    }

    public function test_the_function_delegates_to_the_detector(): void
    {
        $detector = $this->createMock(LegacyKeyDetectorInterface::class);
        $detector->expects(self::once())
            ->method('isLegacy')
            ->with('sk_test_abc')
            ->willReturn(true)
        ;

        $function = (new LegacyStripeKeyExtension($detector))->getFunctions()[0];
        $callable = $function->getCallable();

        self::assertIsCallable($callable);
        self::assertTrue($callable('sk_test_abc'));
    }
}
