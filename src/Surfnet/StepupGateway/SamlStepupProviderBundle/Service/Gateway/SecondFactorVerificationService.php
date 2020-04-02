<?php

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway;

use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Symfony\Component\HttpFoundation\Request;

class SecondFactorVerificationService
{
    /** @var SamlAuthenticationLogger */
    private $samlLogger;

    /** @var ResponseContext */
    private $responseContext;

    /** @var ResponseContext */
    private $sfoResponseContext;

    /**
     * SecondFactorVerificationService constructor.
     * @param SamlAuthenticationLogger $samlLogger
     * @param ResponseContext $responseContext
     * @param ResponseContext $sfoResponseContext
     */
    public function __construct(SamlAuthenticationLogger $samlLogger, ResponseContext $responseContext, ResponseContext $sfoResponseContext)
    {
        $this->samlLogger = $samlLogger;
        $this->responseContext = $responseContext;
        $this->sfoResponseContext = $sfoResponseContext;
    }

    /**
     * Proxy a GSSP authentication request for use in the remote GSSP SSO endpoint.
     *
     * The user is about to be sent to the remote GSSP application for
     * registration. Verification is not initiated with a SAML AUthnRequest,
     *
     * The service provider in this context is SelfService (when registering
     * a token) or RA (when vetting a token).
     *
     * @param Provider $provider
     * @param string $subjectNameId
     * @param string $responseContextServiceId
     * @return AuthnRequest
     */
    public function sendSecondFactorVerificationAuthnRequest(
        Provider $provider,
        $subjectNameId,
        $responseContextServiceId
    ) {
        $stateHandler = $provider->getStateHandler();

        if ($responseContextServiceId === 'second_factor_only.response_context') {
            $originalRequestId = $this->sfoResponseContext->getInResponseTo();
        } else {
            $originalRequestId = $this->responseContext->getInResponseTo();
        }

        $authnRequest = AuthnRequestFactory::createNewRequest(
            $provider->getServiceProvider(),
            $provider->getRemoteIdentityProvider()
        );
        $authnRequest->setSubject($subjectNameId);

        $stateHandler
            ->setRequestId($originalRequestId)
            ->setGatewayRequestId($authnRequest->getRequestId())
            ->setSubject($subjectNameId)
            ->setResponseContextServiceId($responseContextServiceId)
            ->markRequestAsSecondFactorVerification();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->samlLogger->forAuthentication($originalRequestId);
        $logger->notice(sprintf(
            'Sending AuthnRequest to verify Second Factor with request ID: "%s" to GSSP "%s" at "%s" for subject "%s"',
            $authnRequest->getRequestId(),
            $provider->getName(),
            $provider->getRemoteIdentityProvider()->getSsoUrl(),
            $subjectNameId
        ));

        return $authnRequest;
    }
}
