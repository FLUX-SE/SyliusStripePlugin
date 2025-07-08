<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin;

use FluxSE\SyliusStripePlugin\DependencyInjection\CompilerPass\LiveTwigComponentCompilerPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class FluxSESyliusStripePlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        // Before SyliusUiBundle compiler pass
        $container->addCompilerPass(new LiveTwigComponentCompilerPass(), priority: 501);
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
