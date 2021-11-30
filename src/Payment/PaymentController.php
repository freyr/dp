<?php

namespace Freyr\DP\Payment;

use Freyr\DP\Payment\Service\PaymentService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\ServerRequestCreatorFactory;

class PaymentController
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function registerPayment()
    {
        $request = ServerRequestCreatorFactory::create();
        /** @var PaymentService $paymentService */
        $paymentService = $this->container->get(PaymentService::class);
        $userId = (int) $request->getParsedBody()['user_id'];
        $amount = (int) $request->getParsedBody()['amount'];
        $paymentService->registerPayment($amount, $userId);
    }
}
