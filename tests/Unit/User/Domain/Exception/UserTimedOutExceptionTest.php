<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Exception;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use App\User\Domain\Exception\UserTimedOutException;
use DateTimeImmutable;
use DateTimeInterface;

final class UserTimedOutExceptionTest extends UnitTestCase
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

    public function testGetTranslationTemplate(): void
    {
        $timeOutTill = new DateTimeImmutable('+1 hour');
        $exception = new UserTimedOutException($timeOutTill);

        $this->assertEquals('error.user-timed-out', $exception->getTranslationTemplate());
    }

    public function testGetTranslationArgs(): void
    {
        $timeOutTill = new DateTimeImmutable('+1 hour');
        $exception = new UserTimedOutException($timeOutTill);

        $this->assertEquals(
            ['datetime' => $timeOutTill->format(DateTimeInterface::ATOM)],
            $exception->getTranslationArgs()
        );
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertTrue((new UserTimedOutException(
            new DateTimeImmutable('+1 hour')
        )) instanceof DomainException);
    }
}
