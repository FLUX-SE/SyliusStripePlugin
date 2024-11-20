<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider\Checkout\Create\LineItem\PriceData\ProductData;

use FluxSE\SyliusStripePlugin\Provider\Checkout\Create\OrderItemLineItemProviderInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Stripe\StripeObject;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/**
 * @implements OrderItemLineItemProviderInterface<StripeObject>
 */
final class ProductImagesProvider implements OrderItemLineItemProviderInterface
{
    public function __construct(
        private CacheManager $imagineCacheManager,
        private ?string $filterName,
        private string $fallbackImage,
        private string $localhostPattern,
    ) {
    }

    public function provideFromOrderItem(
        OrderItemInterface $orderItem,
        PaymentRequestInterface $paymentRequest,
        array &$params,
    ): void {
        $params['images'] = $this->getImageUrls($orderItem);
    }

    /**
     * @return string[]
     */
    private function getImageUrls(OrderItemInterface $orderItem): array
    {
        $product = $orderItem->getProduct();

        if (null === $product) {
            return [];
        }

        $imageUrl = $this->getImageUrlFromProduct($product);
        if ('' === $imageUrl) {
            return [];
        }

        return [
            $imageUrl,
        ];
    }

    private function getImageUrlFromProduct(ProductInterface $product): string
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
