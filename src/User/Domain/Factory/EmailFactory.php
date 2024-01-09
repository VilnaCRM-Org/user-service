<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EmailFactory
{
    public function create(string $sendTo, string $subject, string $content): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->to($sendTo)
            ->subject($subject)
            ->context([
                'content' => $content,
            ])->htmlTemplate('email/confirm.html.twig');
    }
}
