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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Surfnet\StepupGateway\U2fVerificationBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\U2fVerificationBundle\Exception\LogicException;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\PublicKey;

class PublicKeyType extends Type
{
    const NAME = 'u2f_public_key';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof PublicKey) {
            return $value->getPublicKey();
        }

        if ($value === null) {
            return $value;
        }

        throw new LogicException(
            sprintf(
                'PHP value should be instance of PublicKey or NULL, got "%s"',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return $value;
        }

        try {
            return new PublicKey($value);
        } catch (InvalidArgumentException $e) {
            // Get a nice standard message to throw, keeping the exception chain.
            $doctrineExceptionMessage = ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            )->getMessage();

            throw new ConversionException($doctrineExceptionMessage, 0, $e);
        }
    }

    public function getName()
    {
        return self::NAME;
    }
}
