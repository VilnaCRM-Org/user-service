import { Box, Stack } from '@mui/material';
import Image from 'next/image';
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

import QuestionMark from '../../../assets/svg/auth-section/questionMark.svg';
import { RegisterItem } from '../../../types/authentication/form';
import { PasswordTip } from '../PasswordTip';

import styles from './styles';
import {
  validateEmail,
  validateFullName,
  validatePassword,
} from './validation';

function AuthForm({
  onSubmit,
}: {
  onSubmit: (data: RegisterItem) => void;
}): React.ReactElement {
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
    mode: 'onTouched',
  });
  const fullNameTitle: string = t('sign_up.form.name_input.label');
  const fullNamePlaceholder: string = t('sign_up.form.name_input.placeholder');

  const emailTitle: string = t('sign_up.form.email_input.label');
  const emailPlaceholder: string = t('sign_up.form.email_input.placeholder');

  const passwordTitle: string = t('sign_up.form.password_input.label');
  const passwordPlaceholder: string = t(
    'sign_up.form.password_input.placeholder'
  );
  const imageAlt: string = t('sign_up.form.password_tip.alt');
  const errorText: string = t('sign_up.form.error_text');

  const handleFormSubmit: (data: RegisterItem) => void = (
    data: RegisterItem
  ) => {
    onSubmit(data);
  };

  return (
    <Box sx={styles.formWrapper}>
      <Box sx={styles.backgroundImage} />
      <Box sx={styles.backgroundBlock} />
      <Box sx={styles.formContent}>
        <form onSubmit={handleSubmit(handleFormSubmit)}>
          <UiTypography variant="h4" sx={styles.formTitle}>
            {t('sign_up.form.heading_main')}
          </UiTypography>
          <Stack sx={styles.inputsWrapper}>
            <Stack sx={styles.inputWrapper}>
              <UiTypography variant="medium14" sx={styles.inputTitle}>
                {fullNameTitle}
              </UiTypography>
              <Controller
                control={control}
                name="FullName"
                rules={{
                  required: 'There was an error. Please check again',
                  validate: validateFullName,
                }}
                render={({ field }) => (
                  <UiInput
                    type="text"
                    placeholder={fullNamePlaceholder}
                    onChange={e => field.onChange(e)}
                    onBlur={field.onBlur}
                    value={field.value}
                    fullWidth
                    error={!!errors.FullName}
                  />
                )}
              />
              {errors.FullName && (
                <UiTypography variant="medium14" sx={styles.error_text}>
                  {errorText}
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
                  required: 'There was an error. Please check again',
                  validate: validateEmail,
                }}
                render={({ field }) => (
                  <UiInput
                    type="text"
                    placeholder={emailPlaceholder}
                    onBlur={field.onBlur}
                    onChange={e => field.onChange(e)}
                    value={field.value}
                    fullWidth
                    error={!!errors.Email}
                  />
                )}
              />
              {errors.Email && (
                <UiTypography variant="medium14" sx={styles.error_text}>
                  {errorText}
                </UiTypography>
              )}
            </Stack>
            <Stack sx={styles.inputWrapper}>
              <Stack direction="row" alignItems="center" gap="0.25rem">
                <UiTypography variant="medium14" sx={styles.inputTitle}>
                  {passwordTitle}
                </UiTypography>
                <FormRulesTooltip
                  placement="right"
                  arrow
                  title={<PasswordTip />}
                >
                  <Image
                    src={QuestionMark}
                    alt={imageAlt}
                    width={16}
                    height={16}
                  />
                </FormRulesTooltip>
              </Stack>
              <Controller
                control={control}
                name="Password"
                rules={{
                  required: 'There was an error. Please check again',
                  validate: validatePassword,
                }}
                render={({ field }) => (
                  <UiInput
                    type="password"
                    placeholder={passwordPlaceholder}
                    onChange={e => field.onChange(e)}
                    onBlur={field.onBlur}
                    value={field.value}
                    error={!!errors.Password}
                    fullWidth
                  />
                )}
              />
              {errors.Password && (
                <UiTypography variant="medium14" sx={styles.error_text}>
                  {errorText}
                </UiTypography>
              )}
            </Stack>
          </Stack>
          <Controller
            control={control}
            name="Privacy"
            rules={{
              required: 'There was an error. Please check again',
            }}
            render={({ field }) => (
              <UiCheckbox
                onChange={e => field.onChange(e)}
                sx={styles.labelText}
                error={!errors.Privacy}
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
              {t('sign_up.form.button_text')}
            </UiButton>
          </Box>
        </form>
      </Box>
    </Box>
  );
}

export default AuthForm;
