<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\Processor;

use App\Tests\Integration\User\UserIntegrationTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RegisterUserRestIntegrationTest extends UserIntegrationTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearUserDocumentState();
    }

    public function testPostUsersRejectsCaseInsensitiveDuplicateEmail(): void
    {
        $email = $this->createMixedCaseEmail();
        $this->createPersistedUserWithEmail($email);
        $duplicateEmail = mb_strtoupper($email, 'UTF-8');

        $response = $this->requestJsonPost('/api/users', [
            'email' => $duplicateEmail,
            'initials' => $this->faker->name(),
            'password' => $this->validPassword(),
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertStringContainsString(
            'This email address is already registered',
            (string) $response->getContent()
        );
    }

    private function validPassword(): string
    {
        return sprintf(
            '%sA1!',
            $this->faker->regexify('[A-Za-z0-9]{12}')
        );
    }
}
