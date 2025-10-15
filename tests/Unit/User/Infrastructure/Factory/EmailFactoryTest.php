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
        $build = $this->buildEmail();

        $this->assertEmailBasics($build['email'], $build);
        $this->assertSame(
            ['content' => $build['content']],
            $build['email']->getContext()
        );
    }

    public function testCreateWithAdditionalContext(): void
    {
        $additionalContext = [
            'userName' => $this->faker->name(),
            'resetLink' => $this->faker->url(),
        ];

        $build = $this->buildEmail($additionalContext);

        $this->assertEmailBasics($build['email'], $build);

        // Ensures the UnwrapArrayMerge mutation still merges
        // both content and additionalContext correctly
        $expectedContext = array_merge(
            $additionalContext,
            ['content' => $build['content']]
        );
        $this->assertSame($expectedContext, $build['email']->getContext());

        // Verify specific context keys exist
        $this->assertArrayHasKey('content', $build['email']->getContext());
        $this->assertArrayHasKey('userName', $build['email']->getContext());
        $this->assertArrayHasKey('resetLink', $build['email']->getContext());
    }

    /**
     * @param array<string, string> $additionalContext
     *
     * @return array{
     *     email: Email,
     *     sendTo: string,
     *     subject: string,
     *     template: string,
     *     content: string
     * }
     */
    private function buildEmail(array $additionalContext = []): array
    {
        $factory = new EmailFactory();

        $sendTo = $this->faker->email();
        $subject = $this->faker->title();
        $content = $this->faker->text();
        $template = 'email/confirm.html.twig';

        return [
            'email' => $factory->create(
                $sendTo,
                $subject,
                $content,
                $template,
                $additionalContext
            ),
            'sendTo' => $sendTo,
            'subject' => $subject,
            'template' => $template,
            'content' => $content,
        ];
    }

    /**
     * @param array{email: Email, sendTo: string, subject: string, template: string} $build
     */
    private function assertEmailBasics(Email $email, array $build): void
    {
        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame($build['sendTo'], $email->getTo()[0]->getAddress());
        $this->assertSame($build['subject'], $email->getSubject());
        $this->assertSame($build['template'], $email->getHtmlTemplate());
    }
}
