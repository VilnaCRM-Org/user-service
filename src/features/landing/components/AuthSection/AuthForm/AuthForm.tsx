import { Box, Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { Controller, useForm } from 'react-hook-form';
import { Trans, useTranslation } from 'react-i18next';

import {
  UiCheckbox,
  UiTextFieldForm,
  UiButton,
  UiLink,
  UiTypography,
  UiTooltip,
} from '@/components';

import QuestionMark from '../../../assets/svg/auth-section/questionMark.svg';
import { RegisterItem } from '../../../types/authentication/form';
import { PasswordTip } from '../PasswordTip';
import {
  validateFullName,
  validatePassword,
  validateEmail,
} from '../Validations';

import styles from './styles';
import { AuthFormProps } from './types';

function AuthForm({ onSubmit, error }: AuthFormProps): React.ReactElement {
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
                {t('sign_up.form.name_input.label')}
              </UiTypography>
              <UiTextFieldForm
                control={control}
                name="FullName"
                rules={{
                  required: t('sign_up.form.name_input.required'),
                  validate: validateFullName,
                }}
                errors={!!errors.FullName}
                placeholder={t('sign_up.form.name_input.placeholder')}
                type="text"
              />
              {errors.FullName && (
                <UiTypography variant="medium14" sx={styles.errorText}>
                  {errors.FullName.message}
                </UiTypography>
              )}
            </Stack>
            <Stack sx={styles.inputWrapper}>
              <UiTypography variant="medium14" sx={styles.inputTitle}>
                {t('sign_up.form.email_input.label')}
              </UiTypography>
              <UiTextFieldForm
                control={control}
                name="Email"
                rules={{
                  required: t('sign_up.form.email_input.required'),
                  validate: validateEmail,
                }}
                errors={!!errors.Email}
                placeholder={t('sign_up.form.email_input.placeholder')}
                type="text"
              />
              <UiTypography variant="medium14" sx={styles.errorText}>
                {error || errors.Email?.message}
              </UiTypography>
            </Stack>
            <Stack sx={styles.inputWrapper}>
              <Stack direction="row" alignItems="center" gap="0.25rem">
                <UiTypography variant="medium14" sx={styles.inputTitle}>
                  {t('sign_up.form.password_input.label')}
                </UiTypography>
                <UiTooltip
                  placement="right"
                  sx={styles.tip}
                  arrow
                  title={<PasswordTip />}
                >
                  <Image
                    src={QuestionMark}
                    alt={t('sign_up.form.password_tip.alt')}
                    width={16}
                    height={16}
                  />
                </UiTooltip>
              </Stack>
              <UiTextFieldForm
                control={control}
                name="Password"
                rules={{
                  required: t('sign_up.form.password_input.required'),
                  validate: validatePassword,
                }}
                errors={!!errors.Password}
                placeholder={t('sign_up.form.password_input.placeholder')}
                type="password"
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
            rules={{ required: true }}
            render={({ field }) => (
              <UiCheckbox
                onChange={e => field.onChange(e)}
                error={!!errors.Privacy}
                sx={styles.labelText as React.CSSProperties}
                label={
                  <UiTypography variant="medium14" sx={styles.privacyText}>
                    <Trans i18nKey="sign_up.form.confidential_text.fullText">
                      I have read and accept the
                      <UiLink href="https://github.com/VilnaCRM-Org/website/blob/main/README.md">
                        Privacy Policy
                      </UiLink>
                      та
                      <UiLink href="https://github.com/VilnaCRM-Org/website/blob/main/README.md">
                        Use Policy
                      </UiLink>
                      VilnaCRM Service
                    </Trans>
                  </UiTypography>
                }
              />
            )}
          />
          <Box sx={styles.buttonWrapper}>
            <UiButton
              sx={styles.button}
              variant="contained"
              size="medium"
              type="submit"
              fullWidth
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
