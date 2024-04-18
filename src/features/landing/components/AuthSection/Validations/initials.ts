import { t } from 'i18next';

const hasSpecialCharacters: (str: string) => boolean = (
  str: string
): boolean => {
  const specialCharactersRegex: RegExp = /[!@#$%^&*(),.?":{}|<>~[\]\\/]/;
  return specialCharactersRegex.test(str);
};

export const isValidFullNameFormat: (fullName: string) => boolean = (
  fullName: string
): boolean => {
  const trimmedFullName: string = fullName.trim();
  return (
    /^[^\d\s]{2,}$/.test(trimmedFullName) &&
    !hasSpecialCharacters(trimmedFullName)
  );
};

const validateFullName: (fullName: string) => string | boolean = (
  fullName: string
): string | boolean => {
  const trimmedFullName: string = fullName.trim();

  if (!isValidFullNameFormat(trimmedFullName)) {
    return t('sign_up.form.name_input.error_text');
  }

  return true;
};

export default validateFullName;
