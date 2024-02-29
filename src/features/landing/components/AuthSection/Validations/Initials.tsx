import { t } from 'i18next';

const isValidFullNameFormat: (fullName: string) => boolean = (
  fullName: string
): boolean => /^[^\d\s]+\s[^\d\s]+$/.test(fullName);

const validateFullName: (fullName: string) => string | boolean = (
  fullName: string
): string | boolean => {
  if (!isValidFullNameFormat(fullName))
    return t('sign_up.form.name_input.error_text');
  return true;
};

export default validateFullName;
