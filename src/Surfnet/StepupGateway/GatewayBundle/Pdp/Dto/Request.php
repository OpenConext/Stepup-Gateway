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

namespace Surfnet\StepupGateway\GatewayBundle\Pdp\Dto;

use JsonSerializable;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Request\AccessSubject;
use Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Request\Resource;
use Webmozart\Assert\Assert;

final class Request implements JsonSerializable
{
    const NAMEIDFORMAT_UNSPECIFIED = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';

    /**
     * @var bool
     */
    public $returnPolicyIdList = true;

    /**
     * @var bool
     */
    public $combinedDecision = false;

    /**
     * @var AccessSubject
     */
    public $accessSubject;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Pdp\Dto\Request\Resource
     */
    public $resource;

    /**
     * @var string $subjectId
     * @param string $subjectId
     * @param string $idpEntityId
     * @param string $spEntityId
     * @param array $responseAttributes
     * @return Request $request
     */
    public static function from($subjectId, $idpEntityId, $spEntityId, array $responseAttributes)
    {
        Assert::string($subjectId, 'The SubjectId must be a string, received "%s"');
        Assert::string($idpEntityId, 'The IDPentityID must be a string, received "%s"');
        Assert::string($spEntityId, 'The SPentityID must be a string, received "%s"');
        Assert::allString(
            array_keys($responseAttributes),
            'The keys of the Response attributes must be strings'
        );
        Assert::allIsArray($responseAttributes, 'The values of the Response attributes must be arrays');

        $request = new self;

        $subjectIdAttribute = new Attribute;
        $subjectIdAttribute->attributeId = self::NAMEIDFORMAT_UNSPECIFIED;
        $subjectIdAttribute->value = $subjectId;

        $request->accessSubject = new AccessSubject;
        $request->accessSubject->attributes = [$subjectIdAttribute];

        $spEntityIdAttribute  = new Attribute;
        $spEntityIdAttribute->attributeId = 'SPentityID';
        $spEntityIdAttribute->value = $spEntityId;

        $idpEntityIdAttribute = new Attribute;
        $idpEntityIdAttribute->attributeId = 'IDPentityID';
        $idpEntityIdAttribute->value = $idpEntityId;

        $request->resource = new Resource;
        $request->resource->attributes = [$spEntityIdAttribute, $idpEntityIdAttribute];

        foreach ($responseAttributes as $id => $values) {
            foreach ($values as $value) {
                $attribute = new Attribute;
                $attribute->attributeId = $id;
                $attribute->value = $value;

                $request->accessSubject->attributes[] = $attribute;
            }
        }

        return $request;
    }

    public function jsonSerialize()
    {
        return [
            'Request' => [
                'ReturnPolicyIdList' => $this->returnPolicyIdList,
                'CombinedDecision'   => $this->combinedDecision,
                'AccessSubject'      => $this->accessSubject,
                'Resource'           => $this->resource,
            ]
        ];
    }
}
