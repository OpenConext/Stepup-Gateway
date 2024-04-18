<?php

/**
 * Copyright 2016 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Controller;

use Surfnet\StepupGateway\GatewayBundle\Exception\RequesterFailureException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\Exception\InvalidAdfsRequestException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\Exception\InvalidAdfsResponseException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Exception\InvalidSecondFactorMethodException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\AdfsService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\RespondService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\LoginService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Entry point for the Stepup SFO flow.
 *
 * See docs/GatewayState.md for a high-level diagram on how this controller
 * interacts with outside actors and other parts of Stepup.
 */
class SecondFactorOnlyController extends AbstractController
{
    /**
     * Receive an AuthnRequest from a service provider.
     *
     * This action will forward the user using an internal redirect to the
     * SecondFactorController to start the actual second factor verification.
     *
     * This action also detects if the request is made by ADFS, and tracks
     * some additional information in the session of the user in order to send
     * a non-standard response back to ADFS.
     *
     * @param Request $httpRequest
     * @return Response
     * @throws InvalidAdfsRequestException
     */
    public function sso(Request $httpRequest): Response
    {
        $logger = $this->get('logger');

        if (!$this->getParameter('second_factor_only')) {
            $logger->notice('Access to ssoAction denied, second_factor_only parameter set to false.');

            throw $this->createAccessDeniedException('Second Factor Only feature is disabled');
        }

        $logger->notice('Received AuthnRequest on second-factor-only endpoint, started processing');

        $secondFactorLoginService = $this->getSecondFactorLoginService();

        // Handle binding
        $originalRequest = $secondFactorLoginService->handleBinding($httpRequest);

        // Transform ADFS request to Authn request if applicable
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequest->getRequestId());
        $httpRequest = $this->getSecondFactorAdfsService()->handleAdfsRequest($logger, $httpRequest, $originalRequest);

        try {
            $secondFactorLoginService->singleSignOn($httpRequest, $originalRequest);
        } catch (RequesterFailureException $e) {
            /** @var \Surfnet\StepupGateway\GatewayBundle\Service\ResponseRenderingService $responseRendering */
            $responseRendering = $this->get('second_factor_only.response_rendering');

            return $responseRendering->renderRequesterFailureResponse($this->getResponseContext(), $httpRequest);
        }

        $logger->notice('Forwarding to second factor controller for loa determination and handling');

        // Forward to the selectSecondFactorForVerificationSsoAction,
        // this in turn will forward to the correct
        // verification action (based on authentication type sso/sfo)
        return $this->forward('Surfnet\StepupGateway\GatewayBundle\Controller\SecondFactorController::selectSecondFactorForVerificationSfo');

    }

    /**
     * Send a SAML response back to the service provider.
     *
     * Second factor verification handled by SecondFactorController is
     * finished. The user was forwarded back to this action with an internal
     * redirect. This method sends a AuthnResponse back to the service
     * provider in response to the AuthnRequest received in ssoAction().
     *
     * When responding to an ADFS authentication, the additional ADFS
     * parameters (Context, AuthMethod) are added to the POST response data.
     * In this case, the SAMLResponse parameter is prepended with an
     * underscore. And finally the ACS location the SAMLResponse wil be sent
     * to, is updated to use the ACS location set in the original AuthNRequest.
     *
     * @return Response
     * @throws InvalidAdfsResponseException
     */
    public function respond(Request $request): Response
    {
        $responseContext = $this->getResponseContext();
        $originalRequestId = $responseContext->getInResponseTo();

        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $responseRendering = $this->get('second_factor_only.response_rendering');

        if (!$this->getParameter('second_factor_only')) {
            $logger->notice(sprintf(
                'Access to %s denied, second_factor_only parameter set to false.',
                __METHOD__
            ));
            throw $this->createAccessDeniedException('Second Factor Only feature disabled');
        }

        try {
            $response = $this->getSecondFactorRespondService()->respond($responseContext, $request);
        } catch (InvalidSecondFactorMethodException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        // Reset state
        $this->getSecondFactorRespondService()->resetRespondState($responseContext);

        // Check if ADFS response, if it is, we use the ADFS ACS twig template
        $adfsParameters = $this->getSecondFactorAdfsService()->handleAdfsResponse($logger, $responseContext);
        if (!is_null($adfsParameters)) {
            // Handle Adfs response
            $xmlResponse = $responseRendering->getResponseAsXML($response);

            $httpResponse = $this->render(
                '@SurfnetStepupGatewaySecondFactorOnly/adfs/consume_assertion.html.twig',
                [
                    'acu' => $responseContext->getDestinationForAdfs(),
                    'samlResponse' => $xmlResponse,
                    'adfs' => $adfsParameters,
                ]
            );
        } else {
            // Render the regular SAML response, we do not return it yet, the SSO on 2FA handler will use it to store
            // the SSO on 2FA cookie.
            $httpResponse =  $responseRendering->renderResponse($responseContext, $response, $request);
        }

        if ($response->isSuccess()) {
            $ssoCookieService = $this->get('gateway.service.sso_2fa_cookie');
            $ssoCookieService->handleSsoOn2faCookieStorage($responseContext, $request, $httpResponse);
        }
        // We can now forget the selected second factor.
        $responseContext->finalizeAuthentication();

        return $httpResponse;
    }

    /**
     * @return \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext
     */
    public function getResponseContext()
    {
        return $this->get('second_factor_only.response_context');
    }

    /**
     * @return LoginService
     */
    public function getSecondFactorLoginService()
    {
        return $this->get('second_factor_only.login_service');
    }

    /**
     * @return RespondService
     */
    public function getSecondFactorRespondService()
    {
        return $this->get('second_factor_only.respond_service');
    }

    /**
     * @return AdfsService
     */
    public function getSecondFactorAdfsService()
    {
        return $this->get('second_factor_only.adfs_service');
    }
}
