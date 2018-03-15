<?php

/**
 * Copyright 2018 SURFnet bv
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

use Surfnet\StepupBundle\Value\Provider\ViewConfigInterface;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\LogicException;
use Symfony\Component\HttpFoundation\RequestStack;

class ViewConfig implements ViewConfigInterface
{
    /**
     * @var string
     */
    private $logo;

    /**
     * @var array
     */
    private $title;

    /**
     * The arrays are arrays of translated text, indexed on locale.
     *
     * @param RequestStack $requestStack
     * @param string $logo
     * @param array $title
     */
    public function __construct(
        RequestStack $requestStack,
        $logo,
        array $title
    ) {
        $this->logo = $logo;
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getTranslation($this->title);
    }

    /**
     * @param array $translations
     * @return mixed
     * @throws LogicException
     */
    private function getTranslation(array $translations)
    {
        $currentLocale = $this->requestStack->getCurrentRequest()->getLocale();
        if (is_null($currentLocale)) {
            throw new LogicException('The current language is not set');
        }
        if (isset($translations[$currentLocale])) {
            return $translations[$currentLocale];
        }
        throw new LogicException(
            sprintf(
                'The requested translation is not available in this language: %s. Available languages: %s',
                $currentLocale,
                implode(', ', array_keys($translations))
            )
        );
    }
}
