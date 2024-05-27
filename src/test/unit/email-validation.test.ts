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
      expect(isValidEmailFormat('@example-domain.com')).toBe(false);
    });
  });

  describe('validateEmail', () => {
    it('should return localized error message when email is an empty string', () => {
      expect(validateEmail('')).toBe(emailStepError);
    });

    it('should return true when email is valid', () => {
      expect(validateEmail(correctEmail)).toBe(true);
      expect(validateEmail('user.name@example.com')).toBe(true);
      expect(validateEmail('user123@example.co.uk')).toBe(true);
      expect(validateEmail('user@example-domain.com')).toBe(true);
    });

    it("should return localized error message when email does not contain '@' or '.'", () => {
      expect(validateEmail(invalidTestEmailWithoutDot)).toBe(emailStepError);
      expect(validateEmail('test.example')).toBe(emailStepError);
    });

    it("should return localized error message when email does not contain '@' and '.'", () => {
      expect(validateEmail(invalidTestEmailWithDot)).toBe(emailInvalidError);
      expect(validateEmail('example@.com')).toBe(emailInvalidError);
      expect(validateEmail('@domain.com')).toBe(emailInvalidError);
      expect(validateEmail('example@domain.')).toBe(emailInvalidError);
    });
  });
});
