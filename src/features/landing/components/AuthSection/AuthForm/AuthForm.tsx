import { Box } from '@mui/material';
import React from 'react';
import { Controller, useForm } from 'react-hook-form';
import { Trans, useTranslation } from 'react-i18next';

import {
  UiButton,
  UiCheckbox,
  UiInput,
  UiLabel,
  UiTypography,
  UiLink,
} from '@/components';

import { authFormStyles } from './styles';
import {
  validateEmail,
  validateFullName,
  validatePassword,
} from './validation';

export interface RegisterItem {
  FullName: string;
  Email: string;
  Password: string;
  Privacy: boolean;
}

function AuthForm() {
  const { t } = useTranslation();

  const {
    handleSubmit,
    control,
    formState: { errors },
  } = useForm<RegisterItem>({
    defaultValues: {
      FullName: '',
      Email: '',
      Password: '',
      Privacy: false,
    },
  });

  const onSubmit = (data: RegisterItem) => console.log(data);

  const fullNameTitle = t('sign_up.form.name_input.label');
  const fullNamePlaceholder = t('sign_up.form.name_input.placeholder');

  const emailTitle = t('sign_up.form.email_input.label');
  const emailPlaceholder = t('sign_up.form.email_input.placeholder');

  const passwordTitle = t('sign_up.form.password_input.label');
  const passwordPlaceholder = t('sign_up.form.password_input.placeholder');

  return (
    <Box sx={authFormStyles.formWrapper}>
      <Box sx={authFormStyles.backgroundImage} />
      <Box sx={authFormStyles.backgroundBlock} />
      <Box sx={authFormStyles.formContent}>
        <form onSubmit={handleSubmit(onSubmit)}>
          <UiTypography variant="h4" sx={authFormStyles.formTitle}>
            {t('sign_up.form.heading_main')}
          </UiTypography>
          <UiLabel
            title={fullNameTitle}
            sx={authFormStyles.labelTitle}
            hasError={!!errors.Email}
          >
            <Controller
              control={control}
              name="FullName"
              rules={{
                required: 'Full name is required',
                validate: validateFullName,
              }}
              render={({ field }) => (
                <UiInput
                  type="text"
                  placeholder={fullNamePlaceholder}
                  onChange={e => field.onChange(e)}
                  value={field.value}
                  fullWidth
                  error={!!errors.FullName?.message}
                  helperText={errors?.FullName?.message}
                />
              )}
            />
          </UiLabel>
          <UiLabel
            title={emailTitle}
            sx={authFormStyles.labelTitle}
            hasError={!!errors.Email}
          >
            <Controller
              control={control}
              name="Email"
              rules={{ required: 'Email is required', validate: validateEmail }}
              render={({ field }) => (
                <UiInput
                  type="text"
                  placeholder={emailPlaceholder}
                  onChange={e => field.onChange(e)}
                  value={field.value}
                  fullWidth
                  error={!!errors.Email?.message}
                  helperText={errors?.Email?.message}
                />
              )}
            />
          </UiLabel>
          <UiLabel
            title={passwordTitle}
            sx={authFormStyles.labelTitle}
            hasError={!!errors.Email}
          >
            <Controller
              control={control}
              name="Password"
              rules={{
                required: 'Password is required',
                validate: validatePassword,
              }}
              render={({ field }) => (
                <UiInput
                  type="password"
                  placeholder={passwordPlaceholder}
                  onChange={e => field.onChange(e)}
                  value={field.value}
                  error={!!errors.Password?.message}
                  helperText={errors?.Password?.message}
                  fullWidth
                />
              )}
            />
          </UiLabel>
          <Controller
            control={control}
            name="Privacy"
            rules={{
              required: 'Privacy is required',
            }}
            render={({ field }) => (
              <UiCheckbox
                onChange={e => field.onChange(e)}
                sx={authFormStyles.labelText}
                label={
                  <UiTypography
                    variant="medium14"
                    sx={authFormStyles.privacyText}
                  >
                    <Trans i18nKey="sign_up.form.confidential_text.fullText">
                      Я прочитав та приймаю
                      <UiLink>Політику Конфіденційності</UiLink>
                      та
                      <UiLink>Політику Використання</UiLink>
                      сервісу VilnaCRM
                    </Trans>
                  </UiTypography>
                }
              />
            )}
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
