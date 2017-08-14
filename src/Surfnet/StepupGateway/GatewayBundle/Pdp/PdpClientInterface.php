<?php
namespace Surfnet\StepupGateway\GatewayBundle\Pdp;

use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Request;

interface PdpClientInterface
{
    /**
     * @param Request $request
     * @return PolicyDecision $policyDecision
     */
    public function requestDecisionFor(Request $request);
}
