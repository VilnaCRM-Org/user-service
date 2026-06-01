#!/usr/bin/env php
<?php

declare(strict_types=1);

$specPath = $argv[1] ?? '.github/graphql-spec/spec';
$contents = file_get_contents($specPath);

if ($contents === false) {
    fwrite(STDERR, sprintf("Unable to read GraphQL spec at %s\n", $specPath));
    exit(1);
}

// API Platform rewrites custom mutation descriptions for these AuthPayload
// GraphQL operations during export. Normalize only the passkey operations
// that are part of the passkey-authentication contract.
$signUpOptions = 'Creates WebAuthn public key creation options for a new passkey-backed account.';
$signUpComplete = sprintf(
    '%s%s',
    'Verifies WebAuthn attestation, creates the account, stores the passkey, ',
    'and returns authentication data.'
);
$signInOptions = 'Creates WebAuthn public key request options for passkey sign-in.';
$signInComplete = 'Verifies WebAuthn assertion and returns authentication data.';
$registrationOptions = 'Creates WebAuthn public key creation options for the authenticated user.';
$registrationComplete = sprintf(
    '%s%s',
    'Verifies WebAuthn attestation and stores a passkey ',
    'for the authenticated user.'
);

$descriptionsByOperation = [
    'passkeySignUpOptions' => $signUpOptions,
    'passkeySignUpComplete' => $signUpComplete,
    'passkeySignInOptions' => $signInOptions,
    'passkeySignInComplete' => $signInComplete,
    'passkeyRegistrationOptions' => $registrationOptions,
    'passkeyRegistrationComplete' => $registrationComplete,
];

$normalized = $contents;
$errors = [];
$replacementCount = 0;

foreach ($descriptionsByOperation as $operationName => $description) {
    $fieldName = sprintf('%sUser', $operationName);
    $generatedDefault = sprintf('%ss a AuthPayload.', ucfirst($operationName));
    $normalized = str_replace(
        $generatedDefault,
        $description,
        $normalized,
        $replacements
    );
    $replacementCount += $replacements;

    if ($replacements === 0) {
        $errors[] = sprintf('Missing generated default description for %s.', $fieldName);
    }
}

foreach ($descriptionsByOperation as $operationName => $description) {
    $fieldName = sprintf('%sUser', $operationName);
    $generatedDefault = sprintf('%ss a AuthPayload.', ucfirst($operationName));

    if (str_contains($normalized, $generatedDefault)) {
        $errors[] = sprintf('Generated default description remains for %s.', $fieldName);
    }

    $descriptionPattern = sprintf(
        '/"%s"\s+%s\(input:/',
        preg_quote($description, '/'),
        preg_quote($fieldName, '/')
    );

    if (preg_match($descriptionPattern, $normalized) !== 1) {
        $errors[] = sprintf('Normalized description not found on %s.', $fieldName);
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

if (file_put_contents($specPath, $normalized) === false) {
    fwrite(STDERR, sprintf("Unable to write GraphQL spec at %s\n", $specPath));
    exit(1);
}

echo sprintf(
    "Normalized %d passkey GraphQL descriptions in %s\n",
    $replacementCount,
    $specPath
);
