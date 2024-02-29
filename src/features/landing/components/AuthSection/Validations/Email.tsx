import { t } from 'i18next';

const isValidEmailFormat: (email: string) => boolean = (
  email: string
): boolean => /^.+@.+\..+$/.test(email);

const validateEmail: (email: string) => string | boolean = (
  email: string
): string | boolean => {
  if (!isValidEmailFormat(email)) {
    if (!email.includes('@') || !email.includes('.')) {
      return t('sign_up.form.email_input.step_error_message');
    }
    return t('sign_up.form.email_input.invalid_message');
  }
  return true;
};

export default validateEmail;
