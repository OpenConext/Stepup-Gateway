<?php

/**
 * Copyright 2015 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Provider;

use DOMDocument;
use LogicException;
use Surfnet\SamlBundle\Signing\Signable;
use Stringable;
use DOMElement;
use DOMNode;

class Metadata implements Signable, Stringable
{
    /**
     * @var string
     */
    public $entityId;

    /**
     * @var string
     */
    public $assertionConsumerUrl;

    /**
     * @var string
     */
    public $ssoUrl;

    /**
     * @var string
     */
    public $idpCertificateFile;

    /**
     * @var DOMDocument
     */
    public $document;

    public function getRootDomElement(): DOMElement
    {
        if (!$this->document) {
            throw new LogicException('Cannot get the rootElement of Metadata before the document has been generated');
        }

        return $this->document->documentElement;
    }

    public function getAppendBeforeNode(): ?DOMNode
    {
        if (!$this->document) {
            throw new LogicException(
                'Cannot get the appendBeforeNode of Metadata before the document has been generated',
            );
        }

        return $this->document->documentElement->childNodes->item(0);
    }

    public function __toString(): string
    {
        return (string) $this->document->saveXML();
    }
}
