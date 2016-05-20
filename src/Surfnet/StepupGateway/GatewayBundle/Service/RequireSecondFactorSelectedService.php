<?php


namespace Surfnet\StepupGateway\GatewayBundle\Service;

use Psr\Log\LoggerInterface;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequireSecondFactorSelectedService
{
    /**
     * @var ResponseContext
     */
    private $responseContext;

    /**
     * RequireSecondFactorSelectedService constructor.
     * @param ResponseContext $responseContext
     */
    public function __construct(ResponseContext $responseContext)
    {
        $this->responseContext = $responseContext;
    }

    /**
     * @param LoggerInterface $contextualLogger
     * @return string
     */
    public function requireSelectedSecondFactor(LoggerInterface $contextualLogger)
    {
        $selectedSecondFactor = $this->responseContext->getSelectedSecondFactor();

        if (!$selectedSecondFactor) {
            $contextualLogger->error(
              'Cannot verify possession of an unknown second factor'
            );

            throw new BadRequestHttpException('Cannot verify possession of an unknown second factor.');
        }

        return $selectedSecondFactor;
    }
}
