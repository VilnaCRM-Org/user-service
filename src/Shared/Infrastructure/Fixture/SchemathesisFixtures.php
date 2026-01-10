<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixture;

final class SchemathesisFixtures
{
    public const USER_ID = '018dd6ba-e901-7a8c-b27d-65d122caca6b';
    public const USER_EMAIL = 'user@example.com';
    public const USER_INITIALS = 'Name Surname';
    public const USER_PASSWORD = 'Password1!';

    public const UPDATE_USER_ID = '018dd6ba-e901-7a8c-b27d-65d122caca6c';
    public const UPDATE_USER_EMAIL = 'update-user@example.com';
    public const UPDATE_USER_INITIALS = 'Update User';

    public const CREATE_USER_ID = '018dd6ba-e901-7a8c-b27d-65d122caca70';
    public const CREATE_USER_EMAIL = 'create-user@example.com';
    public const CREATE_USER_INITIALS = 'Create User';
    public const CREATE_USER_PASSWORD = 'CreatePass1!';

    public const CREATE_BATCH_FIRST_USER_ID =
        '018dd6ba-e901-7a8c-b27d-65d122caca71';
    public const CREATE_BATCH_FIRST_USER_EMAIL =
        'batch-user-one@example.com';
    public const CREATE_BATCH_FIRST_USER_INITIALS = 'Batch User One';
    public const CREATE_BATCH_FIRST_USER_PASSWORD = 'BatchPass1!';

    public const CREATE_BATCH_SECOND_USER_ID =
        '018dd6ba-e901-7a8c-b27d-65d122caca72';
    public const CREATE_BATCH_SECOND_USER_EMAIL =
        'batch-user-two@example.com';
    public const CREATE_BATCH_SECOND_USER_INITIALS = 'Batch User Two';
    public const CREATE_BATCH_SECOND_USER_PASSWORD = 'BatchPass2!';

    public const DELETE_USER_ID = '018dd6ba-e901-7a8c-b27d-65d122caca6d';
    public const DELETE_USER_EMAIL = 'delete-user@example.com';
    public const DELETE_USER_INITIALS = 'Delete User';

    public const PASSWORD_RESET_REQUEST_USER_ID =
        '018dd6ba-e901-7a8c-b27d-65d122caca6e';
    public const PASSWORD_RESET_REQUEST_EMAIL = 'password-reset@example.com';
    public const PASSWORD_RESET_REQUEST_INITIALS = 'Password Reset';

    public const PASSWORD_RESET_CONFIRM_USER_ID =
        '018dd6ba-e901-7a8c-b27d-65d122caca6f';
    public const PASSWORD_RESET_CONFIRM_EMAIL =
        'password-reset-confirm@example.com';
    public const PASSWORD_RESET_CONFIRM_INITIALS = 'Password Reset Confirm';
    public const PASSWORD_RESET_CONFIRM_TOKEN = 'reset-confirm-token';
    public const PASSWORD_RESET_CONFIRM_TOKEN_LD = 'reset-confirm-token-ld';

    public const CONFIRMATION_TOKEN = 'confirm-token';

    public const OAUTH_CLIENT_NAME = 'Schemathesis Client';
    public const OAUTH_CLIENT_ID = 'dc0bc6323f16fecd4224a3860ca894c5';
    public const OAUTH_CLIENT_SECRET =
        '8897b24436ac63e457fbd7d0bd5b678686c0cb214ef92fa9e8464fc7';
    public const OAUTH_REDIRECT_URI = 'https://example.com';
    public const AUTHORIZATION_CODE = 'e7f8c62113a47f7a5a9dca1f';
    public const OAUTH_SCOPE = 'profile email';
    public const OAUTH_STATE = 'af0ifjsldkj';
}
