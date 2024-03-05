<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\UserTimedOutException;
use DateTimeImmutable;
use DateTimeInterface;

class UserTimedOutExceptionTest extends UnitTestCase
{
    public function testCreateException(): void
    {
        $timeOutTill = new DateTimeImmutable('+1 hour');

        $exception = new UserTimedOutException($timeOutTill);

        $this->assertEquals(
            'Cannot send new email till ' . $timeOutTill->format(DateTimeInterface::ATOM),
            $exception->getMessage()
        );
    }
}