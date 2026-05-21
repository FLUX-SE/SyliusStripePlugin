<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Stripe\SecretKey;

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetector;
use PHPUnit\Framework\TestCase;

final class LegacyKeyDetectorTest extends TestCase
{
    /** @dataProvider legacyKeyProvider */
    public function test_it_recognises_legacy_secret_keys(string $secretKey): void
    {
        $detector = new LegacyKeyDetector();

        self::assertTrue($detector->isLegacy($secretKey));
    }

    /** @return iterable<string, array{string}> */
    public static function legacyKeyProvider(): iterable
    {
        yield 'secret key in test mode' => ['sk_test_abc123'];
        yield 'secret key in live mode' => ['sk_live_abc123'];
    }

    /** @dataProvider nonLegacyKeyProvider */
    public function test_it_does_not_flag_non_legacy_values(?string $secretKey): void
    {
        $detector = new LegacyKeyDetector();

        self::assertFalse($detector->isLegacy($secretKey));
    }

    /** @return iterable<string, array{?string}> */
    public static function nonLegacyKeyProvider(): iterable
    {
        yield 'restricted key in test mode' => ['rk_test_abc123'];
        yield 'restricted key in live mode' => ['rk_live_abc123'];
        yield 'publishable key' => ['pk_test_abc123'];
        yield 'webhook signing secret' => ['whsec_abc123'];
        yield 'empty string' => [''];
        yield 'null' => [null];
    }
}
