<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Twig\Component\WebElements;

use FluxSE\SyliusStripePlugin\Exception\WebElementsSummaryException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sylius\Bundle\UiBundle\Twig\Component\ResourceLivePropTrait;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent()]
class SummaryPaymentComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;
    use ResourceLivePropTrait;
    use TemplatePropTrait;

    #[LiveProp(hydrateWith: 'hydrateResource', dehydrateWith: 'dehydrateResource')]
    #[ExposeInTemplate('order')]
    public ?ResourceInterface $order = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly PaymentRequestRepositoryInterface $paymentRequestRepository,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function mount(): void
    {
        $this->order = $this->getOrderFromPaymentRequest();
    }

    private function getOrderFromPaymentRequest(): OrderInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new WebElementsSummaryException('No active request found.');
        }

        $tokenHash = $this->ensureExists(
            $request->get('hash'),
            'Token hash is required to load the payment summary component.'
        );

        $paymentRequest = $this->ensureExists(
            $this->paymentRequestRepository->findOneBy(['hash' => $tokenHash]),
            sprintf('No payment request found for token hash "%s".', $tokenHash)
        );

        $payment = $paymentRequest->getPayment();

        $order = $this->ensureExists(
            $payment->getOrder(),
            sprintf('No order found for token hash "%s".', $tokenHash)
        );

        return $order;
    }

    private function ensureExists(mixed $value, string $message): mixed
    {
        if ($value === null) {
            throw new WebElementsSummaryException($message);
        }

        return $value;
    }
}
