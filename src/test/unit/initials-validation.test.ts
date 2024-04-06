import { faker } from '@faker-js/faker';
import { t } from 'i18next';

import { validateFullName } from '../../features/landing/components/AuthSection/Validations';
import { isValidFullNameFormat } from '../../features/landing/components/AuthSection/Validations/initials';

const fullNameRequiredError: string = 'Invalid full name format';
const testFullName: string = faker.person.fullName();
const testFirstName: string = faker.person.firstName();
const testSecondName: string = faker.person.lastName();

describe('validateFullName', () => {
  it('should return true when a valid full name is provided', () => {
    const result: string | boolean = validateFullName(testFullName);
    expect(result).toBe(true);
  });

  it('should return an error message when full name is empty', () => {
    const result: string | boolean = validateFullName('');
    expect(result).toBe(fullNameRequiredError);
  });
  it('should return true for a valid full name format', () => {
    expect(isValidFullNameFormat(testFullName)).toBe(true);
  });

  it('should return false for an invalid full name format', () => {
    expect(isValidFullNameFormat(testFirstName)).toBe(false);
    expect(isValidFullNameFormat(`${testFullName}123`)).toBe(false);
    expect(isValidFullNameFormat('')).toBe(false);
  });
});

describe('validateFullName', () => {
  it('should return true for a valid full name', () => {
    expect(validateFullName(testFullName)).toBe(true);
  });

  it('should return the error message for an invalid full name', () => {
    expect(validateFullName(testFirstName)).toEqual(
      t('sign_up.form.name_input.error_text')
    );
    expect(validateFullName(`${testFullName}123`)).toEqual(
      t('sign_up.form.name_input.error_text')
    );
    expect(validateFullName(`${testFirstName}  ${testSecondName}`)).toEqual(
      t('sign_up.form.name_input.error_text')
    );
    expect(validateFullName('')).toEqual(
      t('sign_up.form.name_input.error_text')
    );
  });

  it('should trim the input before validation', () => {
    expect(validateFullName(`   ${testFullName}   `)).toBe(true);
  });
});
