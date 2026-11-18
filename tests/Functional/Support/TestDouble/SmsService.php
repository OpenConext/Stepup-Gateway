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
 * verifies it as usual.
 * Because the Symfony kernel is rebooted before every request, every request gets a new instance of this service.
 * By storing the messages in a static property in this class, the messages survive a kernel reboot.
 * GatewayWebTestCase::setUp() calls reset() so a new test starts clean, without previous messages.
 *
 * ApiBundle\Tests\TestDouble\Service\SmsService used in the Behat tests is not suitable because it uses a cookie
 * to persist the SMS message between requests.

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
