<?php

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Test\Service;

use Mockery as m;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaAliasLookupService;

class LoaAliasLookupServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group secondFactorOnly
     */
    public function it_looksup_loa_id_by_alias()
    {
        $service = new LoaAliasLookupService(['a' => 'b', 'c' => 'd']);
        $loaId = $service->findLoaIdByAlias('b');

        $this->assertEquals('a', $loaId);

        $loaId = $service->findLoaIdByAlias('d');

        $this->assertEquals('c', $loaId);
    }

    /**
     * @test
     * @group secondFactorOnly
     */
    public function it_returns_false_on_no_match()
    {
        $service = new LoaAliasLookupService(['a' => 'b']);
        $loaId = $service->findLoaIdByAlias('schaap');

        $this->assertFalse($loaId);
    }

    /**
     * @test
     * @group secondFactorOnly
     */
    public function it_looksup_loa_alias_by_loa()
    {
        $loaA = m::mock('Surfnet\StepupBundle\Value\Loa');
        $loaA->shouldReceive('isIdentifiedBy')->withArgs(['a'])->andReturn(true);

        $service = new LoaAliasLookupService(['a' => 'b', 'aap' => 'noot']);
        $alias = $service->findAliasByLoa($loaA);

        $this->assertEquals('b', $alias);

        $loaAap = m::mock('Surfnet\StepupBundle\Value\Loa');
        $loaAap->shouldReceive('isIdentifiedBy')->withArgs(['a'])->andReturn(false);
        $loaAap->shouldReceive('isIdentifiedBy')->withArgs(['aap'])->andReturn(true);

        $alias = $service->findAliasByLoa($loaAap);

        $this->assertEquals('noot', $alias);
    }

    /**
     * @test
     * @group secondFactorOnly
     * @expectedException \Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException
     */
    public function it_rejects_invalid_mappings()
    {
        new LoaAliasLookupService(['a' => ['a', 'b']]);
    }
}
