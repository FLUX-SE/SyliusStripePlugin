<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class LineItemImagesProvider implements LineItemImagesProviderInterface
{
    public function __construct(
        private CacheManager $imagineCacheManager,
        private ?string $filterName,
        private string $fallbackImage,
        private string $localhostPattern,
    ) {
    }

    public function getImageUrls(PaymentRequestInterface $paymentRequest, OrderItemInterface $orderItem): array
    {
        $product = $orderItem->getProduct();

        if (null === $product) {
            return [];
        }

        $imageUrl = $this->getImageUrlFromProduct($paymentRequest, $product);
        if ('' === $imageUrl) {
            return [];
        }

        return [
            $imageUrl,
        ];
    }

    public function getImageUrlFromProduct(PaymentRequestInterface $paymentRequest, ProductInterface $product): string
    {
        $path = '';

        /** @var ProductImageInterface|false $firstImage */
        $firstImage = $product->getImages()->first();
        if (false !== $firstImage) {
            $first = $firstImage;
            $path = $first->getPath();
        }

        if (null === $path) {
            return $this->fallbackImage;
        }

        if ('' === $path) {
            return $this->fallbackImage;
        }

        return $this->getUrlFromPath($path);
    }

    private function getUrlFromPath(string $path): string
    {
        // if the given path is empty, InvalidParameterException will be thrown in filter action
        if ('' === $path) {
            return $this->fallbackImage;
        }

        try {
            if (null === $this->filterName) {
                $url = $this->imagineCacheManager->getRuntimePath($path, []);
            } else {
                $url = $this->imagineCacheManager->getBrowserPath($path, $this->filterName);
            }
        } catch (\Exception) {
            return $this->fallbackImage;
        }

        if ('' === $this->localhostPattern) {
            return $url;
        }

        if (0 !== preg_match($this->localhostPattern, $url)) {
            $url = $this->fallbackImage;
        }

        return $url;
    }
}
