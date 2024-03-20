import { t } from 'i18next';

import { validateEmail } from '../../components/AuthSection/Validations';

const emailRequiredError: string = t('sign_up.form.email_input.required');
const emailStepError: string = t('sign_up.form.email_input.step_error_message');
const emailInvalidError: string = t('sign_up.form.email_input.invalid_message');

describe('validateEmail', () => {
  it('should return localized error message when email is an empty string', () => {
    const email: string = '';
    const result: string | boolean = validateEmail(email);
    expect(result).toBe(t(emailRequiredError));
  });

  it("should return localized error message when email does not contain '@' or '.'", () => {
    const email: string = 'test@example';
    const result: string | boolean = validateEmail(email);
    expect(result).toBe(t(emailStepError));
  });

  it("should return localized error message when email does not contain '@' or '.'", () => {
    const email: string = 'test@example.';
    const result: string | boolean = validateEmail(email);
    expect(result).toBe(t(emailInvalidError));
  });
});
