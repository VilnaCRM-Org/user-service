# Passkey Manual Browser Run 1780280604724-451d4e

This transcript is a durable, sanitized artifact for the current-head
browser/WebAuthn run summarized in `manual-browser-evidence.md`. It excludes
bearer tokens, refresh tokens, credential private material, TOTP secrets, and
recovery-code values.

## Metadata

- Tester: Codex
- Execution date/time (UTC): 2026-06-01T02:23:28.150Z
- API origin: `http://localhost:19081`
- RP ID: `localhost`
- Browser: Google Chrome headless through Chrome DevTools Protocol
- Authenticator: Chrome DevTools virtual CTAP2 authenticators with resident
  keys, user verification, and automatic presence simulation enabled
- Runtime source base commit: `69af2cf13c46f797da7076bff272fa7736e01ce9`
- Source scope: production/runtime source at the base commit above. Subsequent
  remediation edits are tests, test-container wiring, and evidence docs only.
- Raw local JSON evidence:
  `/home/kravtsov/tmp/pr286-manual-webauthn-current.json`

## Observed Scenarios

| Scenario                        | Sanitized result                                                                                                                                                          |
| ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| New-email signup ceremony       | Returned a challenge id, RP ID `localhost`, `residentKey=required`, `2fa=false`, and access/refresh token fields after browser credential creation and signup completion. |
| Existing-email signup rejection | Returned HTTP `409` and no challenge id.                                                                                                                                  |
| Challenge replay rejection      | Returned HTTP `401` and no access token.                                                                                                                                  |
| Passkey sign-in before 2FA      | Returned empty `allowCredentials`, `userVerification=required`, `2fa=false`, and access/refresh token fields after browser assertion completion.                          |
| Authenticated registration      | Returned a challenge id, an `excludeCredentials` array, `residentKey=required`, and a credential id after browser credential creation and registration completion.        |
| 2FA setup payload               | Returned a setup secret and an otpauth URI; values are intentionally omitted.                                                                                             |
| 2FA setup parity                | Returned 8 recovery codes; values are intentionally omitted.                                                                                                              |
| Passkey sign-in after 2FA       | Returned `2fa=true`, a pending session id, and no access/refresh token fields.                                                                                            |
| Expiration rejection            | Returned HTTP `401` and no access token.                                                                                                                                  |

## Acceptance Notes

- The run exercised real browser WebAuthn APIs through Chrome DevTools virtual
  authenticators, not only HTTP stubs.
- The run used RP ID `localhost` and API origin `http://localhost:19081`.
- The evidence proves the current runtime source still satisfies sign-up,
  replay rejection, sign-in, authenticated registration, 2FA parity, and expiry
  behavior after the strict BMAD remediation.
