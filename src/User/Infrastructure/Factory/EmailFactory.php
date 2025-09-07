<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Factory;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;

final class EmailFactory implements EmailFactoryInterface
{
    /**
     * @param array<string, mixed> $additionalContext
     */
    public function create(
        string $sendTo,
        string $subject,
        string $content,
        string $template,
        array $additionalContext = []
    ): Email {
        return (new TemplatedEmail())
            ->to($sendTo)
            ->subject($subject)
            ->context(array_merge(
                $additionalContext,
                ['content' => $content],
            ))
            ->htmlTemplate($template);
    }
}
