<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Twig\Component\WebElements;

use FluxSE\SyliusStripePlugin\Exception\WebElementsSummaryException;
use Sylius\Bundle\UiBundle\Twig\Component\ResourceLivePropTrait;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

class SummaryPaymentComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;
    use TemplatePropTrait;

    /** @use ResourceLivePropTrait<OrderInterface> */
    use ResourceLivePropTrait;

    #[LiveProp(hydrateWith: 'hydrateResource', dehydrateWith: 'dehydrateResource')]
    #[ExposeInTemplate('order')]
    public ?ResourceInterface $order = null;

    /**
     * @param PaymentRequestRepositoryInterface<PaymentRequestInterface> $paymentRequestRepository
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly PaymentRequestRepositoryInterface $paymentRequestRepository,
    ) {
    }

    public function mount(): void
    {
        $this->order = $this->getOrderFromPaymentRequest();
    }

    private function getOrderFromPaymentRequest(): OrderInterface
    {
        $request = $this->ensureExists(
            $this->requestStack->getCurrentRequest(),
            'No active request found.',
        );

        /** @var string $tokenHash */
        $tokenHash = $this->ensureExists(
            $request->get('hash'),
            'Token hash is required to load the payment summary component.',
        );

        $paymentRequest = $this->ensureExists(
            $this->paymentRequestRepository->findOneBy(['hash' => $tokenHash]),
            sprintf('No payment request found for token hash "%s".', $tokenHash),
        );

        /** @var PaymentInterface $payment */
        $payment = $paymentRequest->getPayment();

        return $this->ensureExists(
            $payment->getOrder(),
            sprintf('No order found for token hash "%s".', $tokenHash),
        );
    }

    /**
     * @template T
     *
     * @param T|null $value
     *
     * @return T
     */
    private function ensureExists(mixed $value, string $message): mixed
    {
        if ($value === null) {
            throw new WebElementsSummaryException($message);
        }

        return $value;
    }
}
