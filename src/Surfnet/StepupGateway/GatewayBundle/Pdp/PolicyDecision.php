<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupGateway\GatewayBundle\Pdp;

use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Response;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;

final class PolicyDecision
{
    const DECISION_DENY = 'Deny';
    const DECISION_INDETERMINATE = 'Indeterminate';
    const DECISION_NOT_APPLICABLE = 'NotApplicable';
    const DECISION_PERMIT = 'Permit';

    /**
     * @var string
     */
    private $decision;

    /**
     * @var string[]
     */
    private $localizedDenyMessages = [];

    /**
     * @var string|null
     */
    private $statusMessage;

    /**
     * @var null
     */
    private $statusCode;

    /**
     * @param Response $response
     * @return PolicyDecision
     */
    public static function fromResponse(Response $response)
    {
        $policyDecision = new self;
        $policyDecision->decision = $response->decision;

        $policyDecision->statusCode = $response->status->statusCode->value;

        if (isset($response->status->statusMessage)) {
            $policyDecision->statusMessage = $response->status->statusMessage;
        }

        if ($policyDecision->permitsAccess()) {
            return $policyDecision;
        }

        if (isset($response->associatedAdvices)) {
            $localizedDenyMessages = [];
            foreach ($response->associatedAdvices as $associatedAdvice) {
                foreach ($associatedAdvice->attributeAssignments as $attributeAssignment) {
                    list($identifier, $locale) = explode(':', $attributeAssignment->attributeId);

                    if ($identifier === 'DenyMessage') {
                        $localizedDenyMessages[$locale] = $attributeAssignment->value;
                    }
                }
            }
            $policyDecision->localizedDenyMessages = $localizedDenyMessages;
        }

        return $policyDecision;
    }

    /**
     * @return bool
     */
    public function permitsAccess()
    {
        return $this->decision === self::DECISION_PERMIT || $this->decision === self::DECISION_NOT_APPLICABLE;
    }

    /**
     * @param string $locale
     * @param string $defaultLocale
     * @return string
     */
    public function getLocalizedDenyMessage($locale, $defaultLocale = 'en')
    {
        if (!$this->hasLocalizedDenyMessage()) {
            throw new RuntimeException(sprintf(
                'No localized deny messages present for decision "%s"',
                $this->decision
            ));
        }

        if (isset($this->localizedDenyMessages[$locale])) {
            return $this->localizedDenyMessages[$locale];
        }

        if (!isset($this->localizedDenyMessages[$defaultLocale])) {
            throw new RuntimeException(sprintf(
                'No localized deny message for locale "%s" or default locale "%s" found',
                $locale,
                $defaultLocale
            ));
        }

        return $this->localizedDenyMessages[$defaultLocale];
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        if (!$this->hasStatusMessage()) {
            throw new RuntimeException('No status message found');
        }

        return $this->statusMessage;
    }

    /**
     * Get the status message or status code of the decision.
     *
     * If no status message was present in the response this method will
     * return the status code instead.
     *
     * @return string
     */
    public function getFormattedStatus()
    {
        if ($this->hasStatusMessage()) {
            return $this->statusMessage;
        }

        return $this->statusCode;
    }

    /**
     * @return bool
     */
    public function hasLocalizedDenyMessage()
    {
        return !empty($this->localizedDenyMessages);
    }

    /**
     * @return bool
     */
    public function hasStatusMessage()
    {
        return isset($this->statusMessage);
    }
}
