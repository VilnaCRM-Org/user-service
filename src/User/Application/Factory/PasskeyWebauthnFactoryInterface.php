<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use Symfony\Component\Serializer\SerializerInterface;

interface PasskeyWebauthnFactoryInterface
{
    public function createSerializer(): SerializerInterface;

    public function createAttestationValidator(): object;

    public function createAssertionValidator(): object;
}
