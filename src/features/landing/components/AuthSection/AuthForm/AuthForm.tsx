import { useMutation } from '@apollo/client';
import { Box, Stack, CircularProgress } from '@mui/material';
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

import { SIGNUP_MUTATION } from '../../../api/service/userService';
import QuestionMark from '../../../assets/svg/auth-section/questionMark.svg';
import { RegisterItem } from '../../../types/authentication/form';
import { PasswordTip } from '../PasswordTip';
import { validateFullName, validatePassword, validateEmail } from '../Validations';

import styles from './styles';

interface ErrorData {
  message: string;
}

function AuthForm(): React.ReactElement {
  const [serverError, setServerError] = React.useState('');
  const [signupMutation, { loading }] = useMutation(SIGNUP_MUTATION);
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

  const onSubmit: (data: RegisterItem) => Promise<void> = async (data: RegisterItem) => {
    try {
      setServerError('');
      await signupMutation({
        variables: {
          input: {
            email: data.Email,
            initials: data.FullName,
            clientMutationId: '132',
            password: data.Password,
          },
        },
      });
    } catch (errorData) {
      setServerError((errorData as ErrorData)?.message);
    }
  };

  const handleFormSubmit: (data: RegisterItem) => void = (data: RegisterItem) => {
    onSubmit(data);
  };

  return (
    <Box sx={styles.formWrapper}>
      {loading && (
        <Box sx={styles.loader} role="status">
          <CircularProgress color="primary" size={70} />
        </Box>
      )}
      <Box sx={styles.backgroundImage} />
      <Box sx={styles.backgroundBlock} />
      <Box sx={styles.formContent}>
        <form onSubmit={handleSubmit(handleFormSubmit)}>
          <UiTypography variant="h4" component="h4" sx={styles.formTitle}>
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
                  required: t('sign_up.form.email_input.required'),
                  validate: validateEmail,
                }}
                placeholder={t('sign_up.form.email_input.placeholder')}
                type="text"
              />
              {serverError && (
                <UiTypography variant="medium14" sx={styles.errorText} role="alert">
                  {serverError}
                </UiTypography>
              )}
            </Stack>
            <Stack sx={styles.inputWrapper}>
              <Stack direction="row" alignItems="center" gap="0.25rem">
                <UiTypography variant="medium14" sx={styles.inputTitle}>
                  {t('sign_up.form.password_input.label')}
                </UiTypography>
                <UiTooltip placement="right" sx={styles.tip} arrow title={<PasswordTip />}>
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
                placeholder={t('sign_up.form.password_input.placeholder')}
                type="password"
              />
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
                      <UiLink href="https://github.com/VilnaCRM-Org" target="_blank">
                        Privacy Policy
                      </UiLink>
                      та
                      <UiLink href="https://github.com/VilnaCRM-Org" target="_blank">
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
            <UiButton sx={styles.button} variant="contained" size="medium" type="submit" fullWidth>
              {t('sign_up.form.button_text')}
            </UiButton>
          </Box>
        </form>
      </Box>
    </Box>
  );
}

export default AuthForm;
