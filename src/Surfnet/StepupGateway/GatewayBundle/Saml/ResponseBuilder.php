<?php

/**
 * Copyright 2014 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Saml;

use SAML2_Const;
use SAML2_Response;
use Surfnet\SamlBundle\Exception\LogicException;

class ResponseBuilder
{
    /**
     * @var \SAML2_Response
     */
    private $response;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext
     */
    private $responseContext;

    public function createNewResponse(ResponseContext $context)
    {
        if ($this->response) {
            throw new LogicException('Cannot create a new Response when still building a response.');
        }

        $this->responseContext = $context;

        $response = new SAML2_Response();
        $response->setDestination($context->getDestination());
        $response->setIssuer($context->getIssuer());
        $response->setIssueInstant($context->getIssueInstant());
        $response->setInResponseTo($context->getInResponseTo());

        $this->response = $response;

        return $this;
    }

    /**
     * @param string $status
     * @param string|null $subStatus
     * @param string|null $message
     * @return $this
     */
    public function setResponseStatus($status, $subStatus = null, $message = null)
    {
        if (!$this->isValidResponseStatus($status)) {
            throw new LogicException(sprintf('Trying to set invalid Response Status'));
        }

        if ($subStatus && !$this->isValidResponseSubStatus($subStatus)) {
            throw new LogicException(sprintf('Trying to set invalid Response SubStatus'));
        }

        $status = ['Code' => $status];
        if ($subStatus) {
            $status['SubCode'] = $subStatus;
        }
        if ($message) {
            $status['Message'] = $message;
        }

        $this->response->setStatus($status);

        return $this;
    }

    public function get()
    {
        $response = $this->response;

        $this->response = null;
        $this->responseContext = null;

        return $response;
    }

    private function isValidResponseStatus($status)
    {
        return in_array($status, [
            SAML2_Const::STATUS_AUTHN_FAILED,       // failed authentication
            SAML2_Const::STATUS_NO_AUTHN_CONTEXT,   // insufficient Loa or Loa cannot be met
            SAML2_Const::STATUS_SUCCESS,            // weeee!
            SAML2_Const::STATUS_REQUESTER,          // Something is wrong with the AuthnRequest
            SAML2_Const::STATUS_RESPONDER           // Something went wrong with the Response
        ]);
    }

    private function isValidResponseSubStatus($subStatus)
    {
        return in_array($subStatus, [
            SAML2_Const::STATUS_REQUEST_UNSUPPORTED
        ]);
    }
}
