import { t } from 'i18next';

const MAX_INITIALS_LENGTH: number = 255;

export const isValidFullName: (fullName: string) => boolean = (fullName: string): boolean =>
  Boolean(fullName) && fullName.length <= MAX_INITIALS_LENGTH;

const validateFullName: (fullName: string) => string | boolean = (
  fullName: string
): string | boolean => {
  const trimmedFullName: string = fullName.trim();

  if (isValidFullName(trimmedFullName)) return true;

  return t('sign_up.form.name_input.error_text');
};

export default validateFullName;
