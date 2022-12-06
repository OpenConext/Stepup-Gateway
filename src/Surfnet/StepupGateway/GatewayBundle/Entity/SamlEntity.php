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

namespace Surfnet\StepupGateway\GatewayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use GuzzleHttp;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupGateway\GatewayBundle\Entity\DoctrineSamlEntityRepository")
 * @ORM\Table()
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class SamlEntity
{
    /**
     * Constants denoting the type of SamlEntity. Also used in the middleware to make that distinction
     */
    const TYPE_IDP = 'idp';
    const TYPE_SP = 'sp';

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(length=36)
     */
    private $id;

    /**
     * @ORM\Column
     *
     * @var string
     */
    private $entityId;

    /**
     * @ORM\Column
     *
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(type="text")
     *
     * @var string the configuration as json string
     */
    private $configuration;

    /**
     * @return IdentityProvider
     */
    public function toIdentityProvider()
    {
        if (!$this->type === self::TYPE_IDP) {
            throw new RuntimeException(sprintf(
                'Cannot cast a SAMLEntity to an IdentityProvider if it is not of the type "%s", current type: "%s"',
                self::TYPE_IDP,
                $this->type
            ));
        }

        $decodedConfiguration = $this->decodeConfiguration();

        // index based will be supported later on
        $configuration = [];
        $configuration['entityId']             = $this->entityId;
        $configuration['configuredLoas']       = $decodedConfiguration['loa'];

        return new IdentityProvider($configuration);
    }

    /**
     * @return ServiceProvider
     */
    public function toServiceProvider()
    {
        if (!$this->type === self::TYPE_SP) {
            throw new RuntimeException(sprintf(
                'Cannot cast a SAMLEntity to a ServiceProvider if it is not of the type "%s", current type: "%s"',
                self::TYPE_SP,
                $this->type
            ));
        }

        $decodedConfiguration = $this->decodeConfiguration();

        // Note that we don't set 'assertionConsumerUrl',
        // getAssertionConsumerUrl() on this service provider entity will
        // yield null. The ACS URL in the AuthnRequest is used instead, and
        // this URL is validated by matching against the configured 'allowed
        // ACS locations'. If it doesn't match, the gateway will fall back to
        // the first configured ACS location.
        $configuration = [];
        $configuration['allowedAcsLocations'] = $decodedConfiguration['acs'];
        $configuration['certificateData']     = $decodedConfiguration['public_key'];
        $configuration['entityId']            = $this->entityId;
        $configuration['configuredLoas']      = $decodedConfiguration['loa'];

        $configuration['secondFactorOnly'] = false;
        // Allow the sp to evaluate the SSO on 2FA cookie if present? (defaults to false)
        $configuration['allowSsoOn2fa'] = false;
        // Is the SP allowed to set a SSO on 2FA cookie in Gateway? (defautls to false)
        $configuration['setSsoCookieOn2fa'] = false;

        if (isset($decodedConfiguration['second_factor_only'])) {
            $configuration['secondFactorOnly'] = $decodedConfiguration['second_factor_only'];
        }
        $configuration['secondFactorOnlyNameIdPatterns'] = [];
        if (isset($decodedConfiguration['second_factor_only_nameid_patterns'])) {
            $configuration['secondFactorOnlyNameIdPatterns'] =
                $decodedConfiguration['second_factor_only_nameid_patterns'];
        }
        if (isset($decodedConfiguration['allow_sso_on_2fa'])) {
            $configuration['allowSsoOn2fa'] = $decodedConfiguration['allow_sso_on_2fa'];
        }
        if (isset($decodedConfiguration['set_sso_cookie_on_2fa'])) {
            $configuration['setSsoCookieOn2fa'] = $decodedConfiguration['set_sso_cookie_on_2fa'];
        }
        return new ServiceProvider($configuration);
    }

    /**
     * Returns the decoded configuration
     *
     * @return array
     */
    private function decodeConfiguration()
    {
        return GuzzleHttp\json_decode($this->configuration, true);
    }
}
