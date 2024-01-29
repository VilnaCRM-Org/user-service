import { Box, Stack } from '@mui/material';
import React from 'react';
import { Controller, useForm } from 'react-hook-form';
import { Trans, useTranslation } from 'react-i18next';

import {
  UiButton,
  UiCheckbox,
  UiInput,
  UiTypography,
  UiLink,
  FormRulesTooltip,
} from '@/components';
import { colorTheme } from '@/components/UiColorTheme';

import { RegisterItem } from '../../../types/authentication/form';

import styles from './styles';
import {
  validateEmail,
  validateFullName,
  validatePassword,
} from './validation';

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
    <Box sx={styles.formWrapper}>
      <Box sx={styles.backgroundImage} />
      <Box sx={styles.backgroundBlock} />
      <Box sx={styles.formContent}>
        <form onSubmit={handleSubmit(onSubmit)}>
          <UiTypography variant="h4" sx={styles.formTitle}>
            {t('sign_up.form.heading_main')}
          </UiTypography>
          <Stack sx={styles.inputsWrapper}>
            <Stack sx={styles.inputWrapper}>
              <Stack direction="row" alignItems="center" gap="0.5rem">
                <UiTypography variant="medium14" sx={styles.inputTitle}>
                  {fullNameTitle}
                </UiTypography>
                <FormRulesTooltip
                  placement="right"
                  arrow
                  title={
                    <Stack direction="column" gap="0.5rem">
                      <UiTypography
                        variant="medium14"
                        sx={{
                          pb: '5px',
                          borderBottom: `2px solid ${colorTheme.palette.grey400.main}`,
                        }}
                      >
                        Правила реєстрації.
                      </UiTypography>
                      <ul
                        style={{
                          display: 'flex',
                          flexDirection: 'column',
                          gap: '0.5rem',
                        }}
                      >
                        <li
                          style={{
                            fontSize: '0.8rem',
                            color: colorTheme.palette.darkPrimary.main,
                            fontWeight: 400,
                          }}
                        >
                          напишіть ваше ім&apos;я та прізвище
                        </li>
                        <li
                          style={{
                            fontSize: '0.8rem',
                            fontWeight: 400,
                            color: colorTheme.palette.darkPrimary.main,
                          }}
                        >
                          відсутні пробіли
                        </li>
                        <li
                          style={{
                            fontSize: '0.8rem',
                            fontWeight: 400,
                            color: colorTheme.palette.darkPrimary.main,
                          }}
                        >
                          відсутні спеціальні символи
                        </li>
                      </ul>
                    </Stack>
                  }
                >
                  <Stack
                    justifyContent="center"
                    alignItems="center"
                    sx={{
                      width: '16px',
                      height: '16px',
                      border: '1px solid black',
                      borderRadius: '50%',
                      cursor: 'pointer',
                    }}
                  >
                    <UiTypography variant="medium14">?</UiTypography>
                  </Stack>
                </FormRulesTooltip>
              </Stack>

              <Controller
                control={control}
                name="FullName"
                rules={{
                  required: 'Виникла помилка. Перевірте ще раз',
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
                  />
                )}
              />
              {errors.FullName && (
                <UiTypography variant="medium14" sx={styles.errorText}>
                  {errors.FullName.message}
                </UiTypography>
              )}
            </Stack>
            <Stack sx={styles.inputWrapper}>
              <UiTypography variant="medium14" sx={styles.inputTitle}>
                {emailTitle}
              </UiTypography>
              <Controller
                control={control}
                name="Email"
                rules={{
                  required: 'Виникла помилка. Перевірте ще раз',
                  validate: validateEmail,
                }}
                render={({ field }) => (
                  <UiInput
                    type="text"
                    placeholder={emailPlaceholder}
                    onChange={e => field.onChange(e)}
                    value={field.value}
                    fullWidth
                    error={!!errors.Email?.message}
                  />
                )}
              />
              {errors.Email && (
                <UiTypography variant="medium14" sx={styles.errorText}>
                  {errors.Email.message}
                </UiTypography>
              )}
            </Stack>
            <Stack sx={styles.inputWrapper}>
              <UiTypography variant="medium14" sx={styles.inputTitle}>
                {passwordTitle}
              </UiTypography>
              <Controller
                control={control}
                name="Password"
                rules={{
                  required: 'Виникла помилка. Перевірте ще раз',
                  validate: validatePassword,
                }}
                render={({ field }) => (
                  <UiInput
                    type="password"
                    placeholder={passwordPlaceholder}
                    onChange={e => field.onChange(e)}
                    value={field.value}
                    error={!!errors.Password?.message}
                    fullWidth
                  />
                )}
              />
              {errors.Password && (
                <UiTypography variant="medium14" sx={styles.errorText}>
                  {errors.Password.message}
                </UiTypography>
              )}
            </Stack>
          </Stack>
          <Controller
            control={control}
            name="Privacy"
            rules={{
              required: 'Виникла помилка. Перевірте ще раз',
            }}
            render={({ field }) => (
              <UiCheckbox
                onChange={e => field.onChange(e)}
                sx={styles.labelText}
                error={!errors.Privacy?.message}
                label={
                  <UiTypography variant="medium14" sx={styles.privacyText}>
                    <Trans i18nKey="sign_up.form.confidential_text.fullText">
                      Я прочитав та приймаю
                      <UiLink href="/">Політику Конфіденційності</UiLink>
                      та
                      <UiLink href="/">Політику Використання</UiLink>
                      сервісу VilnaCRM
                    </Trans>
                  </UiTypography>
                }
              />
            )}
          />
          <Box sx={styles.buttonWrapper}>
            <UiButton
              variant="contained"
              size="medium"
              type="submit"
              fullWidth
              sx={styles.button}
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
