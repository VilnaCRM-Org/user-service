import { Box } from '@mui/material';
import React from 'react';
import { useForm } from 'react-hook-form';
import { Trans, useTranslation } from 'react-i18next';

import {
  UiButton,
  UiCheckbox,
  UiInput,
  UiLabel,
  UiTypography,
} from '@/components/ui';
import { UiLink } from '@/components/ui/UiLink';

import { authFormStyles } from './styles';

export interface RegisterItem {
  FullName: string;
  Email: string;
  Password: string;
}

function AuthForm() {
  const { t } = useTranslation();

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<RegisterItem>();

  const onSubmit = (data: RegisterItem) => console.log(data);

  const isLengthValid = (value: string): boolean =>
    value.length >= 8 && value.length <= 64;

  const hasNumber = (value: string): boolean => /[0-9]/.test(value);

  const hasUppercase = (value: string): boolean => /[A-Z]/.test(value);

  const validatePassword = (value: string): string | boolean => {
    if (!isLengthValid(value))
      return 'Password must be between 8 and 64 characters long';
    if (!hasNumber(value)) return 'Password must contain at least one number';
    if (!hasUppercase(value))
      return 'Password must contain at least one uppercase letter';
    return true;
  };

  const isValidEmailFormat = (email: string): boolean =>
    /^.+@.+\..+$/.test(email);

  const validateEmail = (email: string): string | boolean => {
    if (!isValidEmailFormat(email)) return 'Invalid email format';
    return true;
  };

  const isValidFullNameFormat = (fullName: string): boolean =>
    /^[^\d\s]+\s[^\d\s]+$/.test(fullName);

  const hasEmptyParts = (fullName: string): boolean =>
    fullName.split(' ').some(part => part.length === 0);

  const validateFullName = (fullName: string): string | boolean => {
    if (!isValidFullNameFormat(fullName)) return 'Invalid full name format';
    if (hasEmptyParts(fullName))
      return 'Name and surname should have at least 1 character';
    return true;
  };

  const fullNameTitle = t('sign_up.form.name_input.label');
  const fullNamePlaceholder = t('sign_up.form.name_input.placeholder');

  const emailTitle = t('sign_up.form.email_input.label');
  const emailPlaceholder = t('sign_up.form.email_input.placeholder');

  const passwordTitle = t('sign_up.form.password_input.label');
  const passwordPlaceholder = t('sign_up.form.password_input.placeholder');

  return (
    <Box sx={authFormStyles.formWrapper}>
      <Box sx={authFormStyles.backgroundImage} />
      <Box sx={authFormStyles.formContent}>
        <form onSubmit={handleSubmit(onSubmit)}>
          <UiTypography variant="h4" sx={authFormStyles.formTitle}>
            {t('sign_up.form.heading_main')}
          </UiTypography>
          <UiLabel
            sx={authFormStyles.labelTitle}
            title={fullNameTitle}
            errorText={
              errors.FullName?.message || 'Виникла помилка. Перевірте ще раз'
            }
            hasError={errors.FullName}
          >
            <UiInput
              fullWidth
              error={!!errors.FullName}
              helperText={errors.FullName?.message}
              placeholder={fullNamePlaceholder}
              // eslint-disable-next-line react/jsx-props-no-spreading
              {...register('FullName', {
                required: true,
                validate: validateFullName,
                minLength: 2,
                maxLength: 50,
              })}
            />
          </UiLabel>
          <UiLabel
            sx={authFormStyles.labelTitle}
            title={emailTitle}
            errorText={
              errors.Email?.message || 'Виникла помилка. Перевірте ще раз'
            }
            hasError={errors.Email}
          >
            <UiInput
              fullWidth
              error={!!errors.Email?.message || false}
              helperText={errors.Email?.message}
              placeholder={emailPlaceholder}
              // eslint-disable-next-line react/jsx-props-no-spreading
              {...register('Email', {
                required: true,
                validate: validateEmail,
              })}
            />
          </UiLabel>
          <UiLabel
            sx={authFormStyles.labelTitle}
            title={passwordTitle}
            errorText={
              errors.Password?.message || 'Виникла помилка. Перевірте ще раз'
            }
            hasError={errors.Password}
          >
            <UiInput
              fullWidth
              error={!!errors.Password?.message || false}
              helperText={errors.Password?.message}
              type="password"
              placeholder={passwordPlaceholder}
              // eslint-disable-next-line react/jsx-props-no-spreading
              {...register('Password', {
                required: true,
                validate: validatePassword,
              })}
            />
          </UiLabel>

          <UiCheckbox
            sx={authFormStyles.labelText}
            label={
              <UiTypography variant="medium14" sx={authFormStyles.privacyText}>
                <Trans i18nKey="sign_up.form.confidential_text.fullText">
                  Я прочитав та приймаю
                  <UiLink>Політику Конфіденційності</UiLink>
                  та <UiLink>Політику Використання</UiLink> сервісу VilnaCRM
                </Trans>
              </UiTypography>
            }
          />
          <Box sx={authFormStyles.buttonWrapper}>
            <UiButton
              variant="contained"
              size="medium"
              type="submit"
              fullWidth
              sx={authFormStyles.button}
            >
              {t('sign_up.form.button-text')}
            </UiButton>
          </Box>
        </form>
      </Box>
    </Box>
  );
}

export default AuthForm;
