import { faker } from '@faker-js/faker';

import { validatePassword } from '../../features/landing/components/AuthSection/Validations';

const textShortText: string = 'short';
const textNoNumbers: string = 'NoNumbers';
const textNoUppercaseLetter: string = 'shortshort1';
const correctPassword: string = faker.internet.password();

const passwordLengthError: string =
  'Password must be between 8 and 64 characters long';

const passwordNumbersError: string =
  'Password must contain at least one number';

const passwordUppercaseError: string =
  'Password must contain at least one uppercase letter';

describe('code snippet', () => {
  it('should return true when password is valid', () => {
    const result: string | boolean = validatePassword(correctPassword);
    expect(result).toBe(true);
  });

  it('should return localized error message when password is empty', () => {
    const result: string | boolean = validatePassword('');
    expect(result).toBe(passwordLengthError);
  });

  it('should return localized error message when password is too short', () => {
    const result: string | boolean = validatePassword(textShortText);
    expect(result).toBe(passwordLengthError);
  });

  it("should return localized error message when password doesn't contain numbers", () => {
    const result: string | boolean = validatePassword(textNoNumbers);
    expect(result).toBe(passwordNumbersError);
  });

  it('should return localized error message when password is too short', () => {
    const result: string | boolean = validatePassword(textNoUppercaseLetter);
    expect(result).toBe(passwordUppercaseError);
  });
});
