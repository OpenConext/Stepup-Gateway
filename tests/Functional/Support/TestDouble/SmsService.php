<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support\TestDouble;

use RuntimeException;
use Surfnet\StepupBundle\Command\SendSmsCommand;
use Surfnet\StepupBundle\Service\SmsService as SmsServiceInterface;

/**
 * SMS service for the functional tests: it does not send the SMS, but keeps it, so the test can read the OTP the
 * Gateway sent with getOtpSentTo().
 *
 * Only the sending is replaced: the Gateway generates the OTP, stores it in the session (per second factor) and
 * verifies it as usual. The test client reboots the kernel before every request, so every request gets a new
 * instance of this service: the messages are kept in a static property, and GatewayWebTestCase::setUp() calls
 * reset() so a new test starts without messages.
 *
 * Unlike the Behat test double (ApiBundle\Tests\TestDouble\Service\SmsService), that writes the message to a cookie
 * with setcookie(), which the test client does not see.
 */
final class SmsService implements SmsServiceInterface
{
    /** @var SendSmsCommand[] The messages sent, in the order they were sent */
    private static array $sent = [];

    /**
     * Forget all sent messages. Called by GatewayWebTestCase::setUp(), so every test starts without messages.
     */
    public static function reset(): void
    {
        self::$sent = [];
    }

    public function sendSms(SendSmsCommand $command): bool
    {
        self::$sent[] = $command;

        return true;
    }

    /**
     * @return SendSmsCommand[]
     */
    public function getSentMessages(): array
    {
        return self::$sent;
    }

    /**
     * Get the OTP of the last SMS sent to $msisdn (the phone number without "+" and the "(0)", e.g. "31612345678").
     * The OTP is the last word of the message ("Your SMS code: %challenge%").
     */
    public function getOtpSentTo(string $msisdn): string
    {
        foreach (array_reverse(self::$sent) as $command) {
            if ($command->recipient === $msisdn && preg_match('/(\S+)$/', $command->body, $matches)) {
                return $matches[1];
            }
        }

        throw new RuntimeException(sprintf('No SMS was sent to "%s"', $msisdn));
    }
}
