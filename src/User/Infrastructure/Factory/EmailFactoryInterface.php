<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Factory;

use Symfony\Component\Mime\Email;

interface EmailFactoryInterface
{
    public function create(
        string $sendTo,
        string $subject,
        string $content,
        string $template
    ): Email;
}
