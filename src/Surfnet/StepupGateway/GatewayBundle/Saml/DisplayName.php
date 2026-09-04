<?php

/**
 * Copyright 2026 SURFnet bv
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

use InvalidArgumentException;

class DisplayName
{
    public function __construct(
        public readonly string $lang,
        public readonly string $value
    ) {
        if ($lang === '' || $value === '') {
            throw new InvalidArgumentException(
                'DisplayName requires both a non-empty lang and a non-empty value'
            );
        }
    }

    public function toArray(): array
    {
        return ['lang' => $this->lang, 'value' => $this->value];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['lang'] ?? '', $data['value'] ?? '');
    }

    // Reduces to the primary language subtag (e.g. "en_GB" -> "en"): the result is used both
    // as an outgoing xml:lang value and to compare locales against each other, and region
    // would make otherwise-matching locales compare unequal. Falls back to 'en' if empty.
    public static function normalizeLocale(string $locale): string
    {
        $locale = $locale ?: 'en';
        return strtolower(strtok($locale, '_-') ?: 'en');
    }
}
