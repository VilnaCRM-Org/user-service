import { t } from 'i18next';

const isLengthValid: (value: string) => boolean = (value: string): boolean =>
  value.length >= 8 && value.length <= 64;

const hasNumber: (value: string) => boolean = (value: string): boolean => /[0-9]/.test(value);

const hasUppercase: (value: string) => boolean = (value: string): boolean => /[A-Z]/.test(value);

const validatePassword: (value: string) => string | boolean = (value: string): string | boolean => {
  if (!isLengthValid(value)) return t('sign_up.form.password_input.error_length');
  if (!hasNumber(value)) return t('sign_up.form.password_input.error_numbers');
  if (!hasUppercase(value)) return t('sign_up.form.password_input.error_uppercase');
  return true;
};

export default validatePassword;
