<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

final class ApiRateLimitRequestResolverGraphQlPayloadLimitersTest extends GraphQlRateLimitTestCase
{
    public function testPasskeySigninOptionsUsesMultipartOperationsPayloadLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $request = $this->createGraphQlOperationsRequest(
            [
                'query' => $this->passkeySigninOptionsInputQuery(),
                'variables' => ['input' => ['email' => $email]],
            ],
            $clientIp,
            self::MULTIPART_CONTENT_TYPE
        );

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsFormOperationsOperationNameUsesQueryStringQuery(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $request = $this->createNamedFormOperationsRequest($clientIp);
        $request->query->set('query', $this->passkeySigninOptionsInputQuery());
        $request->query->set('variables', $this->encodeVariables(['input' => ['email' => $email]]));

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testFormOperationsOperationNameOverridesQueryStringSelectedOperation(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createGraphQlOperationsRequest(
            ['operationName' => 'Real'],
            $clientIp,
            self::FORM_CONTENT_TYPE
        );
        $request->query->set('query', $this->decoyAndRealOperationQuery());

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testPasskeySigninCompleteFormOperationsOperationNameUsesQueryStringQuery(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createNamedFormOperationsRequest($clientIp);
        $request->query->set('query', $this->passkeySigninCompleteQuery());

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testMalformedFormOperationsFallsBackToQueryStringLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $request = $this->createMalformedOperationsRequest($clientIp, self::FORM_CONTENT_TYPE);
        $request->query->set('query', sprintf(self::PASSKEY_SIGNIN_OPTIONS_MUTATION, $email));

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testFormOperationsVariablesOverlayQueryStringQuery(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $request = $this->createNamedFormOperationsRequest(
            $clientIp,
            ['variables' => ['input' => ['email' => $email]]]
        );
        $request->query->set('query', $this->passkeySigninOptionsInputQuery());

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testTextPlainOperationsParameterIsIgnored(): void
    {
        $request = $this->createOperationsParameterRequest(
            ['query' => sprintf(self::PASSKEY_SIGNIN_OPTIONS_MUTATION, $this->faker->email())],
            $this->faker->ipv4(),
            'text/plain'
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testInvalidJsonPayloadFallsBackToQueryStringLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $request = $this->createInvalidJsonRequest($clientIp);
        $request->query->set('query', sprintf(self::PASSKEY_SIGNIN_OPTIONS_MUTATION, $email));

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testJsonStringVariablesPreserveInputAfterNestedVariable(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $request = $this->createJsonStringVariablesRequest($clientIp, $email);

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testInvalidQueryStringVariablesUsesOnlyIpLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createGraphQlRequest($this->passkeySigninOptionsInputQuery(), $clientIp);
        $request->headers->set('CONTENT_TYPE', 'text/plain');
        $request->query->set('variables', '{');

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testRawGraphQlContentTypeUsesRequestBodyLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createPasskeyCompleteRawGraphQlRequest($clientIp);
        $request->setFormat('graphql', ['application/graphql']);

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testApplicationGraphQlMimeTypeUsesRequestBodyWithoutRegisteredFormat(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createPasskeyCompleteRawGraphQlRequest($clientIp);

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testApplicationGraphQlMimeTypeOverridesQueryStringWithoutFormat(): void
    {
        $request = $this->createProjectUpdateWithQueryStringPasskeyRequest(
            $this->faker->ipv4(),
            $this->faker->email()
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testApplicationGraphQlMimeTypeComparisonIsCaseInsensitive(): void
    {
        $request = $this->createProjectUpdateWithQueryStringPasskeyRequest(
            $this->faker->ipv4(),
            $this->faker->email(),
            'APPLICATION/GRAPHQL'
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testRawGraphQlBodyOverridesQueryStringQuery(): void
    {
        $request = $this->createProjectUpdateWithQueryStringPasskeyRequest(
            $this->faker->ipv4(),
            $this->faker->email()
        );
        $request->setFormat('graphql', ['application/graphql']);

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testRawGraphQlEmptyBodyUsesQueryStringLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $request = $this->createRawGraphQlRequest('', $clientIp);
        $request->query->set('query', sprintf(self::PASSKEY_SIGNIN_OPTIONS_MUTATION, $email));

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsMultipartOperationsIgnoresNestedCredentialEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createGraphQlOperationsRequest(
            [
                'query' => $this->passkeySigninOptionsInputQuery(),
                'variables' => ['input' => ['credential' => ['email' => $this->faker->email()]]],
            ],
            $clientIp,
            self::FORM_CONTENT_TYPE
        );

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }
}
