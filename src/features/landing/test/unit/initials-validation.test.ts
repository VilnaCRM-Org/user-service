import { t } from 'i18next';

import { validateFullName } from '../../components/AuthSection/Validations';

const fullNameRequiredError: string = t('sign_up.form.name_input.required');
const testFullName: string = 'John Doe';
const invalidFullName: string = 'John Michael Doe';

describe('validateFullName', () => {
  it('should return true when a valid full name is provided', () => {
    const result: string | boolean = validateFullName(testFullName);
    expect(result).toBe(true);
  });

  it('should return an error message when full name is empty', () => {
    const fullName: string = '';
    const result: string | boolean = validateFullName(fullName);
    expect(result).toBe(t(fullNameRequiredError));
  });

  it('should return true when a valid full name with middle name is provided', () => {
    const result: string | boolean = validateFullName(invalidFullName);
    expect(result).toBeFalsy();
  });
});
