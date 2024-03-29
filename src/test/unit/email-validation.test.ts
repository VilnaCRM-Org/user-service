import { faker } from '@faker-js/faker';

import { validateEmail } from '../../features/landing/components/AuthSection/Validations';

const emailStepError: string = "Email must contain '@' and '.' symbols";
const emailInvalidError: string = 'Invalid email address';
const invalidTestEmailWithoutDot: string = 'test@example';
const invalidTestEmailWithDot: string = 'test@example.';
const correctEmail: string = faker.internet.email();

describe('validateEmail', () => {
  it('should return localized error message when email is an empty string', () => {
    const result: string | boolean = validateEmail('');
    expect(result).toBe(emailStepError);
  });

  it('should return true when email is valid', () => {
    const result: string | boolean = validateEmail(correctEmail);
    expect(result).toBe(true);
  });

  it("should return localized error message when email does not contain '@' or '.'", () => {
    const result: string | boolean = validateEmail(invalidTestEmailWithoutDot);
    expect(result).toBe(emailStepError);
  });

  it("should return localized error message when email does not contain '@' or '.'", () => {
    const result: string | boolean = validateEmail(invalidTestEmailWithDot);
    expect(result).toBe(emailInvalidError);
  });
});
