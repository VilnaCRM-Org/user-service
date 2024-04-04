<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Factory\EmailFactory;
use Symfony\Component\Mime\Email;

final class EmailFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $factory = new EmailFactory();

        $sendTo = $this->faker->email();
        $subject = $this->faker->title();
        $content = $this->faker->text();
        $template = 'email/confirm.html.twig';
        $email = $factory->create($sendTo, $subject, $content, $template);

        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame($sendTo, $email->getTo()[0]->getAddress());
        $this->assertSame($subject, $email->getSubject());
        $this->assertSame($template, $email->getHtmlTemplate());
        $this->assertSame(['content' => $content], $email->getContext());
    }
}
