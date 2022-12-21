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

use SAML2\Assertion;

class AssertionAdapter
{
    private $assertion;

    public function __construct(Assertion $assertion)
    {
        $this->assertion = $assertion;
    }

    /**
     * @param string $inResponseTo
     * @return bool
     */
    public function inResponseToMatches($inResponseTo)
    {
         return $this->getInResponseTo() === $inResponseTo;
    }

    /**
     * @return null|string
     */
    public function getInResponseTo()
    {
        /** @var \SAML2\XML\saml\SubjectConfirmation $subjectConfirmation */
        $subjectConfirmation = $this->assertion->getSubjectConfirmation()[0];

        return $subjectConfirmation->getSubjectConfirmationData()->getInResponseTo();
    }
}
