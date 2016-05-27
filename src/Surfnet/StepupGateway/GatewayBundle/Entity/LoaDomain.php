<?php

namespace Surfnet\StepupGateway\GatewayBundle\Entity;

use Surfnet\StepupBundle\Value\Loa;

final class LoaDomain
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array<string,string>
     */
    private $loaAuthnContextClassMapping;

    /**
     * @param string $id
     * @param array<string,string> $loaAuthnContextClassMapping
     */
    public function __construct($id, array $loaAuthnContextClassMapping)
    {
        $this->id = $id;
        $this->loaAuthnContextClassMapping = $loaAuthnContextClassMapping;
    }

    /**
     * @param string $ref
     * @return string|bool
     */
    public function findLoaIdByAuthnContextClassRef($ref)
    {
        return array_search($ref, $this->loaAuthnContextClassMapping);
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
