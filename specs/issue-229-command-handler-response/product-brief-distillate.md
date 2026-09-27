# Product Brief Distillate

Change only the confirm-password-reset command flow requested by issue #229:

- `ConfirmPasswordResetCommandHandler::__invoke()` returns `ConfirmPasswordResetCommandResponse`.
- `ConfirmPasswordResetCommand` becomes input-only.
- No command bus contract change in this PR.
- No external API behavior change.
