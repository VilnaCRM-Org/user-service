import { faker } from '@faker-js/faker';

import { validateFullName } from '../../features/landing/components/AuthSection/Validations';
import {
  isValidFullNameFormat,
  hasSpecialCharacters,
} from '../../features/landing/components/AuthSection/Validations/initials';

let testFullName: string = faker.person.fullName();
const testFirstName: string = faker.person.firstName();
const testSecondName: string = faker.person.lastName();
const errorText: string = 'Invalid full name format';

if (testFullName.split(' ').length > 2)
  testFullName = testFullName.replace(/\./g, '');

describe('validateFullName', () => {
  describe('hasSpecialCharacters', () => {
    it('should return true if the string contains special characters', () => {
      expect(hasSpecialCharacters(`${testFirstName}@`)).toBe(true);
      expect(hasSpecialCharacters(`${testSecondName}!`)).toBe(true);
      expect(hasSpecialCharacters(`#${testFirstName}#`)).toBe(true);
      expect(hasSpecialCharacters(`.${testSecondName}.`)).toBe(true);
    });

    it('should return false if the string does not contain special characters', () => {
      expect(hasSpecialCharacters(testFirstName)).toBe(false);
      expect(hasSpecialCharacters(`${testFirstName}'`)).toBe(false);
      expect(hasSpecialCharacters(`'${testSecondName}`)).toBe(false);
    });
  });

  describe('isValidFullNameFormat', () => {
    it('should return true if the full name is valid', () => {
      expect(isValidFullNameFormat(testFullName)).toBe(true);
      expect(isValidFullNameFormat(`${testFirstName}  ${testSecondName}`)).toBe(
        true
      );
      expect(isValidFullNameFormat(`${testFirstName}\n${testSecondName}`)).toBe(
        true
      );
    });

    it('should return false if the full name is not valid', () => {
      expect(isValidFullNameFormat(`${testFullName}123`)).toBe(false);
      expect(isValidFullNameFormat(`123${testFullName}456`)).toBe(false);
      expect(isValidFullNameFormat(`# ${testFullName} %`)).toBe(false);
      expect(isValidFullNameFormat(`${testFullName}@`)).toBe(false);
    });
  });

  describe('validateFullName', () => {
    it('should return true if the full name is valid', () => {
      expect(validateFullName(testFullName)).toBe(true);
      expect(validateFullName(`  ${testFullName}  `)).toBe(true);
      expect(validateFullName(`${testFirstName} ${testSecondName}`)).toBe(true);
    });

    it('should return an error message if the full name is not valid', () => {
      expect(validateFullName(`${testFullName}123`)).toEqual(errorText);
      expect(validateFullName(`123${testFullName}456`)).toEqual(errorText);
      expect(validateFullName(`${testFullName} @ `)).toEqual(errorText);
      expect(validateFullName(` # ${testFullName} # `)).toEqual(errorText);
    });
  });
});
