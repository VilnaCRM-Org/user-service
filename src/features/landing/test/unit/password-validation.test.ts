import { faker } from '@faker-js/faker';
import { t } from 'i18next';

import { validatePassword } from '../../components/AuthSection/Validations';

const textShortText: string = 'short';
const textNoNumbers: string = 'NoNumbers';
const textNoUppercaseLetter: string = 'shortshort1';
const correctPassword: string = faker.internet.password();

const passwordRequiredError: string = t('sign_up.form.password_input.required');
const passwordLengthError: string = t(
  'sign_up.form.password_input.error_length'
);
const passwordNumbersError: string = t(
  'sign_up.form.password_input.error_numbers'
);

const passwordUppercaseError: string = t(
  'sign_up.form.password_input.error_uppercase'
);

describe('code snippet', () => {
  it('should return true when password is valid', () => {
    const result: string | boolean = validatePassword(correctPassword);
    expect(result).toBe(true);
  });

  it('should return localized error message when password is empty', () => {
    const result: string | boolean = validatePassword('');
    expect(result).toBe(t(passwordRequiredError));
  });

  it('should return localized error message when password is too short', () => {
    const result: string | boolean = validatePassword(textShortText);
    expect(result).toBe(t(passwordLengthError));
  });

  it("should return localized error message when password doesn't contain numbers", () => {
    const result: string | boolean = validatePassword(textNoNumbers);
    expect(result).toBe(t(passwordNumbersError));
  });

  it('should return localized error message when password is too short', () => {
    const result: string | boolean = validatePassword(textNoUppercaseLetter);
    expect(result).toBe(t(passwordUppercaseError));
  });
});
