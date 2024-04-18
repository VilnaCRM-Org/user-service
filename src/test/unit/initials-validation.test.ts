import { faker } from '@faker-js/faker';

import { validateFullName } from '../../features/landing/components/AuthSection/Validations';
import {
  isValidFullNameFormat,
  hasSpecialCharacters,
} from '../../features/landing/components/AuthSection/Validations/initials';

const erorrText: string = 'Invalid full name format';
const testFullName: string = faker.person.fullName();
const testFirstName: string = faker.person.firstName();
const testSecondName: string = faker.person.lastName();

describe('validateFullName', () => {
  describe('hasSpecialCharacters', () => {
    it('should return true if the string contains special characters', () => {
      expect(hasSpecialCharacters(`${testFullName}@`)).toBe(true);
      expect(hasSpecialCharacters(`${testFullName}!`)).toBe(true);
      expect(hasSpecialCharacters(`${testFullName}123`)).toBe(false);
      expect(hasSpecialCharacters(testFullName)).toBe(false);
    });
  });

  describe('isValidFullNameFormat', () => {
    it('should return true if the full name is valid', () => {
      expect(isValidFullNameFormat(testFullName)).toBe(true);
      expect(isValidFullNameFormat(testFullName + testSecondName)).toBe(true);
    });

    it('should return false if the full name is not valid', () => {
      expect(isValidFullNameFormat(testFirstName)).toBe(true);
      expect(isValidFullNameFormat(`   ${testFullName}   `)).toBe(true);
      expect(isValidFullNameFormat(`${testFullName}123`)).toBe(false);
      expect(isValidFullNameFormat(`${testFullName}@`)).toBe(false);
    });
  });

  describe('validateFullName', () => {
    it('should return true if the full name is valid', () => {
      expect(validateFullName(testFullName)).toBe(true);
      expect(validateFullName(testFullName + testSecondName)).toBe(true);
    });

    it('should return an error message if the full name is not valid', () => {
      expect(validateFullName(`${testFullName}123`)).toEqual(erorrText);
      expect(validateFullName(`${testFullName}@`)).toEqual(erorrText);
    });
  });
});
