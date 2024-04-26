import { t } from 'i18next';

const MAX_INITIALS_LENGTH: number = 255;

const validateFullName: (fullName: string) => string | boolean = (
  fullName: string
): string | boolean => {
  const trimmedFullName: string = fullName.trim();

  if (Boolean(trimmedFullName) && trimmedFullName.length <= MAX_INITIALS_LENGTH)
    return true;

  return t('sign_up.form.name_input.error_text');
};

export default validateFullName;
