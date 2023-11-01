<?php declare(strict_types=1);

/**
 * Copyright 2023 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Test\Sso2fa\DateTime;

use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\DateTime\ExpirationHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\InvalidAuthenticationTimeException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValueInterface;

class ExpirationHelperTest extends TestCase
{
    /**
     * @dataProvider expirationExpectations
     */
    public function test_is_expired(bool $isExpired, ExpirationHelper $helper, CookieValue $cookieValue)
    {
        self::assertEquals($isExpired, $helper->isExpired($cookieValue));
    }

    /**
     * @dataProvider gracePeriodExpectations
     */
    public function test_grace_period(bool $isExpired, ExpirationHelper $helper, CookieValue $cookieValue)
    {
        self::assertEquals($isExpired, $helper->isExpired($cookieValue));
    }

    /**
     * @dataProvider invalidTimeExpectations
     */
    public function test_strange_authentication_time_values(ExpirationHelper $helper, CookieValue $cookieValue)
    {
        self::expectException(InvalidAuthenticationTimeException::class);
        $helper->isExpired($cookieValue);
    }

    public function expirationExpectations()
    {
        return [
            'not expired' => [false, $this->makeExpirationHelper(3600, time()), $this->makeCookieValue(time())],
            'not expired but about to be' => [false, $this->makeExpirationHelper(3600, time() + 3600), $this->makeCookieValue(time())],
            'expired' => [true, $this->makeExpirationHelper(3600, time() + 3601), $this->makeCookieValue(time())],
            'expired more' => [true, $this->makeExpirationHelper(3600, time() + 36000), $this->makeCookieValue(time())],
        ];
    }

    public function gracePeriodExpectations()
    {
        // Cookie lifetime 3600 with a grace period of 5 seconds
        $helper = $this->makeExpirationHelper(3600, time(), 5);
        return [
            'within grace period (outer bound)' => [false, $helper, $this->makeCookieValue(time() - 3605)],
            'within grace period' => [false, $helper, $this->makeCookieValue(time() - 3601)],
            'within grace period (lower bound)' => [false, $helper, $this->makeCookieValue(time() - 3600)],
            'outside grace period' => [true, $helper, $this->makeCookieValue(time() - 3606)],
        ];
    }

    public function invalidTimeExpectations()
    {
        $goodOldHelper = $this->makeExpirationHelper(3600, time());
        return [
            'before epoch' => [$goodOldHelper, $this->makeCookieValue(-1)],
            'from the future' => [$goodOldHelper, $this->makeCookieValue(time() + 42)],
            'invalid time input 1' => [$goodOldHelper, $this->makeCookieValueUnrestrictedAuthTime('aint-no-time')],
            'invalid time input 2' => [$goodOldHelper, $this->makeCookieValueUnrestrictedAuthTime('9999-01-01')],
            'invalid time input 3' => [$goodOldHelper, $this->makeCookieValueUnrestrictedAuthTime('0001-01-01')],
            'invalid time input 4' => [$goodOldHelper, $this->makeCookieValueUnrestrictedAuthTime(-1.0)],
            'invalid time input 5' => [$goodOldHelper, $this->makeCookieValueUnrestrictedAuthTime(2.999)],
            'invalid time input 6' => [$goodOldHelper, $this->makeCookieValueUnrestrictedAuthTime(42)],
            'invalid time input 7' => [$goodOldHelper, $this->makeCookieValueUnrestrictedAuthTime(true)],
            'invalid time input 8' => [$goodOldHelper, $this->makeCookieValueUnrestrictedAuthTime(false)],
            'invalid time input 9' => [$goodOldHelper, $this->makeCookieValueUnrestrictedAuthTime(null)],
        ];
    }

    private function makeExpirationHelper(int $expirationTime, int $now, int $gracePeriod = 0) : ExpirationHelper
    {
        $time = new \DateTime();
        $time->setTimestamp($now);
        return new ExpirationHelper($expirationTime, $gracePeriod, $time);
    }

    private function makeCookieValue(int $authenticationTime) : CookieValueInterface
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($authenticationTime);
        $data = [
            'tokenId' => 'tokenId',
            'identityId' => 'identityId',
            'loa' => 2.0,
            'authenticationTime' => $dateTime->format(DATE_ATOM),
        ];
        return CookieValue::deserialize(json_encode($data));
    }

    private function makeCookieValueUnrestrictedAuthTime($authenticationTime) : CookieValueInterface
    {
        $data = [
            'tokenId' => 'tokenId',
            'identityId' => 'identityId',
            'loa' => 2.0,
            'authenticationTime' => $authenticationTime,
        ];
        return CookieValue::deserialize(json_encode($data));
    }
}
