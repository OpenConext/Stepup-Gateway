<?php

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;

final class AuthnContextClassRefLookupService
{
    /**
     * @var array<string,string>
     */
    private $loaAuthnContextClassMapping;

    /**
     * @param array<string,string> $loaAuthnContextClassMapping
     */
    public function __construct(array $loaAuthnContextClassMapping)
    {
        foreach ($loaAuthnContextClassMapping as $loaId => $authnContextClassRef) {
            if (!is_string($loaId)) {
                throw InvalidArgumentException::invalidType(
                    'string',
                    'authnContextClassRef',
                    $authnContextClassRef
                );
            }
            if (!is_string($authnContextClassRef)) {
                throw InvalidArgumentException::invalidType(
                    'string',
                    'ref',
                    $authnContextClassRef
                );
            }
        }

        $this->loaAuthnContextClassMapping = $loaAuthnContextClassMapping;
    }

    /**
     * @param string $authnContextClassRef
     * @return string|bool
     */
    public function findLoaIdByAuthnContextClassRef($authnContextClassRef)
    {
        if (!is_string($authnContextClassRef)) {
            throw InvalidArgumentException::invalidType(
                'string',
                'authnContextClassRef',
                $authnContextClassRef
            );
        }
        return array_search($authnContextClassRef, $this->loaAuthnContextClassMapping);
    }

    /**
     * @param Loa $loa
     * @return string|bool
     */
    public function findAuthnContextClassRefByLoa(Loa $loa)
    {
        foreach ($this->loaAuthnContextClassMapping as $loaId => $ref) {
            if ($loa->isIdentifiedBy($loaId)) {
                return $ref;
            }
        }
        return false;
    }
}
