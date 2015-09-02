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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Surfnet\StepupGateway\U2fVerificationBundle\Entity\Registration;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;

class RegistrationRepository extends EntityRepository
{
    /**
     * @param KeyHandle $keyHandle
     * @return Registration|null
     */
    public function findByKeyHandle(KeyHandle $keyHandle)
    {
        return $this->findOneBy(['keyHandle' => $keyHandle->getKeyHandle()]);
    }

    /**
     * @param Registration $registration
     */
    public function save(Registration $registration)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($registration);
        $entityManager->flush();
    }

    /**
     * @param Registration $registration
     */
    public function revoke(Registration $registration)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($registration);
        $entityManager->flush();
    }
}
