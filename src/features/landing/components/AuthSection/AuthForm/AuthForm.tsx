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
} from '@/components';
import UiTooltip from '@/components/UiTooltip';

import QuestionMark from '../../../assets/svg/auth-section/questionMark.svg';
import { RegisterItem } from '../../../types/authentication/form';
import { PasswordTip } from '../PasswordTip';
import {
  PasswordValidator,
  EmailValidator,
  FullNameValidator,
} from '../Validations';

import styles from './styles';

function AuthForm({
  onSubmit,
}: {
  onSubmit: (data: RegisterItem) => Promise<void>;
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
                  validate: (value: string): boolean =>
                    new FullNameValidator().validateFullName(value),
                }}
                errors={!!errors.FullName}
                placeholder={t('sign_up.form.name_input.placeholder')}
                type="text"
              />
            </Stack>
            <Stack sx={styles.inputWrapper}>
              <UiTypography variant="medium14" sx={styles.inputTitle}>
                {t('sign_up.form.email_input.label')}
              </UiTypography>
              <UiTextFieldForm
                control={control}
                name="Email"
                rules={{
                  validate: (value: string): boolean =>
                    new EmailValidator().isValidEmailFormat(value),
                }}
                errors={!!errors.Email}
                placeholder={t('sign_up.form.email_input.placeholder')}
                type="text"
              />
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
                  validate: (value: string): boolean =>
                    new PasswordValidator().validatePassword(value),
                }}
                errors={!!errors.Password}
                placeholder={t('sign_up.form.password_input.placeholder')}
                type="password"
              />
            </Stack>
          </Stack>
          <Controller
            control={control}
            name="Privacy"
            render={({ field }) => (
              <UiCheckbox
                onChange={e => field.onChange(e)}
                error={!errors.Privacy}
                sx={styles.labelText as React.CSSProperties}
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
