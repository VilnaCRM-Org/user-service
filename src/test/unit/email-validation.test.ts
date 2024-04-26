import { faker } from '@faker-js/faker';

import { validateEmail } from '../../features/landing/components/AuthSection/Validations';
import { isValidEmailFormat } from '../../features/landing/components/AuthSection/Validations/email';

const emailStepError: string = "Must contain the characters '@' and '.'";
const emailInvalidError: string = 'Invalid email address';
const invalidTestEmailWithoutDot: string = 'test@example';
const invalidTestEmailWithDot: string = 'test@example.';
const correctEmail: string = faker.internet.email();

describe('Email Tests', () => {
  describe('isValidEmailFormat', () => {
    it('should return true for valid email formats', () => {
      expect(isValidEmailFormat(`${correctEmail}`)).toBe(true);
      expect(isValidEmailFormat('user.name@example.com')).toBe(true);
      expect(isValidEmailFormat('user123@example.co.uk')).toBe(true);
      expect(isValidEmailFormat('user@example-domain.com')).toBe(true);
    });

    it('should return false for invalid email formats', () => {
      expect(isValidEmailFormat(invalidTestEmailWithoutDot)).toBe(false);
      expect(isValidEmailFormat(invalidTestEmailWithDot)).toBe(false);
      expect(isValidEmailFormat('user@.com')).toBe(false);
      expect(isValidEmailFormat('user@domain')).toBe(false);
    });
  });

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
      const result: string | boolean = validateEmail(
        invalidTestEmailWithoutDot
      );
      expect(result).toBe(emailStepError);
    });

    it("should return localized error message when email does not contain '@' or '.'", () => {
      const result: string | boolean = validateEmail(invalidTestEmailWithDot);
      expect(result).toBe(emailInvalidError);
    });
  });
});
