<?php
/**
 * Copyright 2016 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Tests\Service;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaAliasLookupService;

class LoaAliasLookupServiceTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Group('secondFactorOnly')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_looksup_loa_id_by_alias(): void
    {
        $service = new LoaAliasLookupService(['a' => 'b', 'c' => 'd']);
        $loaId = $service->findLoaIdByAlias('b');

        $this->assertEquals('a', $loaId);

        $loaId = $service->findLoaIdByAlias('d');

        $this->assertEquals('c', $loaId);
    }

    #[\PHPUnit\Framework\Attributes\Group('secondFactorOnly')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_false_on_no_match(): void
    {
        $service = new LoaAliasLookupService(['a' => 'b']);
        $loaId = $service->findLoaIdByAlias('schaap');

        $this->assertFalse($loaId);
    }

    #[\PHPUnit\Framework\Attributes\Group('secondFactorOnly')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_looksup_loa_alias_by_loa(): void
    {
        $loaA = m::mock(Loa::class);
        $loaA->shouldReceive('isIdentifiedBy')->withArgs(['a'])->andReturn(true);

        $service = new LoaAliasLookupService(['a' => 'b', 'aap' => 'noot']);
        $alias = $service->findAliasByLoa($loaA);

        $this->assertEquals('b', $alias);

        $loaAap = m::mock(Loa::class);
        $loaAap->shouldReceive('isIdentifiedBy')->withArgs(['a'])->andReturn(false);
        $loaAap->shouldReceive('isIdentifiedBy')->withArgs(['aap'])->andReturn(true);

        $alias = $service->findAliasByLoa($loaAap);

        $this->assertEquals('noot', $alias);
    }

    #[\PHPUnit\Framework\Attributes\Group('secondFactorOnly')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_rejects_invalid_mappings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LoaAliasLookupService(['a' => ['a', 'b']]);
    }
}
