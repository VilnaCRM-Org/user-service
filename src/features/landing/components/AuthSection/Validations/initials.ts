import { t } from 'i18next';

export const hasSpecialCharacters: (str: string) => boolean = (
  str: string
): boolean => {
  const specialCharactersRegex: RegExp = /[!@#$%^&*(),.?":{}|<>~[\]\\/]/;
  return specialCharactersRegex.test(str);
};

export const isValidFullNameFormat: (fullName: string) => boolean = (
  fullName: string
): boolean => {
  const words: string[] = fullName.trim().split(/\s+/);
  for (const word of words) {
    if (!/^[^\d\s]{2,}$/.test(word) || hasSpecialCharacters(word)) {
      return false;
    }
  }
  return true;
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
