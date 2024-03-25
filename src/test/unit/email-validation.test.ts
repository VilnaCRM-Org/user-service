import { faker } from '@faker-js/faker';
import { t } from 'i18next';

import { validateEmail } from '../../features/landing/components/AuthSection/Validations';

const emailRequiredError: string = t('sign_up.form.email_input.required');
const emailStepError: string = t('sign_up.form.email_input.step_error_message');
const emailInvalidError: string = t('sign_up.form.email_input.invalid_message');
const invalidTestEmailWithoutDot: string = 'test@example';
const invalidTestEmailWithDot: string = 'test@example.';
const correctEmail: string = faker.internet.email();

describe('validateEmail', () => {
  it('should return true when email is valid', () => {
    const result: string | boolean = validateEmail(correctEmail);
    expect(result).toBe(true);
  });

  it('should return localized error message when email is an empty string', () => {
    const result: string | boolean = validateEmail('');
    expect(result).toBe(t(emailRequiredError));
  });

  it('should return localized error message when email is an empty string', () => {
    const result: string | boolean = validateEmail('');
    expect(result).toBe(t(emailRequiredError));
  });

  it("should return localized error message when email does not contain '@' or '.'", () => {
    const result: string | boolean = validateEmail(invalidTestEmailWithoutDot);
    expect(result).toBe(t(emailStepError));
  });

  it("should return localized error message when email does not contain '@' or '.'", () => {
    const result: string | boolean = validateEmail(invalidTestEmailWithDot);
    expect(result).toBe(t(emailInvalidError));
  });
});
