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

    public function testCreateWithAdditionalContext(): void
    {
        $factory = new EmailFactory();

        $sendTo = $this->faker->email();
        $subject = $this->faker->title();
        $content = $this->faker->text();
        $template = 'email/confirm.html.twig';
        $additionalContext = [
            'userName' => $this->faker->name(),
            'resetLink' => $this->faker->url(),
        ];

        $email = $factory->create($sendTo, $subject, $content, $template, $additionalContext);

        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame($sendTo, $email->getTo()[0]->getAddress());
        $this->assertSame($subject, $email->getSubject());
        $this->assertSame($template, $email->getHtmlTemplate());
        
        // This test catches the UnwrapArrayMerge mutation by ensuring
        // both content and additionalContext are merged properly
        $expectedContext = array_merge(['content' => $content], $additionalContext);
        $this->assertSame($expectedContext, $email->getContext());
        
        // Verify specific context keys exist
        $this->assertArrayHasKey('content', $email->getContext());
        $this->assertArrayHasKey('userName', $email->getContext());
        $this->assertArrayHasKey('resetLink', $email->getContext());
    }
}
