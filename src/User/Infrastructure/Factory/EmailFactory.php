<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Factory;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;

final class EmailFactory implements EmailFactoryInterface
{
    public function create(
        string $sendTo,
        string $subject,
        string $content,
        string $template
    ): Email {
        return (new TemplatedEmail())
            ->to($sendTo)
            ->subject($subject)
            ->context([
                'content' => $content,
            ])->htmlTemplate($template);
    }
}
