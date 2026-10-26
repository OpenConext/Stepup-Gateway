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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Saml;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\GatewayBundle\Saml\DisplayName;

class DisplayNameTest extends TestCase
{
    #[Test]
    public function it_rejects_an_empty_lang(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DisplayName('', 'Some Name');
    }

    #[Test]
    public function it_rejects_an_empty_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DisplayName('en', '');
    }

    #[Test]
    public function it_accepts_a_non_empty_lang_and_value(): void
    {
        $displayName = new DisplayName('en', 'Some Name');

        $this->assertSame('en', $displayName->lang);
        $this->assertSame('Some Name', $displayName->value);
    }

    #[Test]
    public function from_array_rejects_missing_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DisplayName::fromArray([]);
    }

    #[Test]
    public function to_array_and_from_array_round_trip(): void
    {
        $displayName = new DisplayName('nl', 'Naam');

        $this->assertEquals($displayName, DisplayName::fromArray($displayName->toArray()));
    }
}
