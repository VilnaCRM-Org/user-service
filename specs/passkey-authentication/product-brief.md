# Passkey Authentication Product Brief

## Problem

The user-service only supports password-based local sign-in, social OAuth, and TOTP-based 2FA. Users cannot create or use passkeys, which means the service lacks a modern phishing-resistant authentication option.

## Users

- New users who want to create an account with a passkey.
- Existing users who want to add a passkey to their account.
- Returning users who want to sign in without typing a password.
- Frontend clients that need WebAuthn ceremony options and completion endpoints.

## Product Goals

- Provide backend WebAuthn ceremony endpoints for passkey sign-up, passkey enrollment, and passkey sign-in.
- Persist passkey credentials with replay/counter protection using `web-auth/webauthn-lib`.
- Reuse existing session issuance and auth event publishing so passkey sign-in behaves like existing sign-in once verified.
- Document frontend integration, migration, and fallback paths.

## Non Goals

- Building frontend UI inside this backend repository.
- Username-less discoverable credential login in the first PR.
- Enterprise attestation policy, passkey sync-provider management, or credential device management UI.
- Removing passwords from existing accounts.

## Success Criteria

- New users can complete a passkey sign-up ceremony and receive auth tokens.
- Authenticated users can add a passkey to their account.
- Users with registered passkeys can complete passkey sign-in and receive auth tokens.
- WebAuthn response verification is performed through `web-auth/webauthn-lib`.
- Credential records and active challenges are persisted in MongoDB with suitable indexes and expiry.
- Unit tests cover option generation, completion flows, invalid challenges, credential lookup, and session issuance.
- Documentation covers REST payloads, frontend ceremony expectations, migration, and fallback behavior.
