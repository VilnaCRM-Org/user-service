import { faker } from '@faker-js/faker';

import { validateFullName } from '../../features/landing/components/AuthSection/Validations';

const testFullName: string = faker.person.fullName();
const testFirstName: string = faker.person.firstName();
const testSecondName: string = faker.person.lastName();
const errorText: string = 'Invalid full name format';

describe('initials Tests', () => {
  describe('validateFullName', () => {
    it('should return true if the full name is valid', () => {
      expect(validateFullName(testFullName)).toBe(true);
      expect(validateFullName(`!@${testFullName} #%`)).toBe(true);
      expect(validateFullName(`123 ${testFullName}456`)).toBe(true);
      expect(validateFullName(`  ${testFullName}  `)).toBe(true);
      expect(validateFullName(`${testFirstName} ${testSecondName}`)).toBe(true);
      expect(validateFullName(`123 456 789`)).toBe(true);
      expect(validateFullName(`!@# $%^ &*()`)).toBe(true);
    });

    it('should return an error message if the full name is not valid', () => {
      expect(validateFullName(` `)).toBe(errorText);
      expect(validateFullName(`  `)).toBe(errorText);
      expect(validateFullName(`\n`)).toBe(errorText);
      expect(validateFullName(` \n `)).toBe(errorText);
      expect(validateFullName(testFullName.repeat(40))).toBe(errorText);
    });
  });
});
