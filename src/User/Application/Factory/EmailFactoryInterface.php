<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use Symfony\Component\Mime\Email;

interface EmailFactoryInterface
{
    /**
     * @param array<string, string> $additionalContext
     */
    public function create(
        string $sendTo,
        string $subject,
        string $content,
        string $template,
        array $additionalContext = []
    ): Email;
}
