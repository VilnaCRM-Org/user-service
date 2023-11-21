import { Grid, Typography } from '@mui/material';
import { useState } from 'react';
import { useForm, SubmitHandler, Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button/Button';
import CustomInput from '@/components/ui/CustomInput/CustomInput';
import { createUser } from '@/features/landing/api/service/userService';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import {
  INPUT_ID_FOR_EMAIL,
  INPUT_ID_FOR_PASSWORD,
  INPUT_ID_FOR_USER_FIRST_AND_LAST_NAME,
} from '@/features/landing/types/sign-up/types';

import SignUpPrivacyPolicy from '../SignUpPrivacyPolicy/SignUpPrivacyPolicy';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

interface IFormData {
  username: string;
  email: string;
  password: string;
}

const styles = {
  mainHeading: {
    color: '#484848',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '30px',
    fontStyle: 'normal',
    fontWeight: '600',
    lineHeight: 'normal',
    marginBottom: '32px',
    alignSelf: 'flex-start',
  },
  mainHeadingSmallest: {
    fontSize: '22px',
  },
  mainGrid: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    height: '100%',
    minHeight: '548px',
    width: '100$',
    maxWidth: '636px',
    borderRadius: '32px 32px 0px 0px',
    border: '1px solid #E1E7EA',
    background: '#FFF',
    padding: '36px 40px 40px 40px',
    boxShadow: '-25px 105px #E1E7EA, 1px 1px 41px 0px rgba(59, 68, 80, 0.05)',
  },
  form: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'stretch',
    width: '100%',
    gap: '22px',
    height: '100%',
  },
};

export default function SignUp() {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isSmallest, isMobile, isTablet } = useScreenSize();
  const { control, handleSubmit, formState } = useForm<IFormData>();
  const { errors } = formState;
  const [isPrivacyPolicyCheckboxChecked, setIsPrivacyPolicyCheckboxChecked] =
    useState<boolean>(false);

  const onSubmit: SubmitHandler<IFormData> = async ({ email, username, password }) => {
    if (!isPrivacyPolicyCheckboxChecked) {
      return;
    }

    try {
      const { id, email: userEmail, initials } = await createUser(email, username, password);
      alert(`Successfully registered with id: ${id}, email: ${userEmail}, initials: ${initials}`);
    } catch (error) {}
  };

  return (
    <Grid
      item
      lg={6} md={12}
      sx={{
        ...styles.mainGrid,
        boxShadow: !(isSmallest || isMobile || isTablet)
          ? '-25px 105px #E1E7EA, 1px 1px 41px 0px rgba(59, 68, 80, 0.05)'
          : '1px 1px 41px 0px rgba(59, 68, 80, 0.05)',
        padding: isSmallest || isMobile ? '24px 24px 32px 24px' : styles.mainGrid.padding,
      }}
    >
      <Typography
        style={{
          ...styles.mainHeading,
          ...(isSmallest || isMobile ? styles.mainHeadingSmallest : {}),
        }}
        component="h2"
        variant="h1"
      >
        {t('sign_up.form.heading_main')}
      </Typography>

      <form
        onSubmit={handleSubmit(onSubmit)}
        style={{
          ...styles.form,
          flexDirection: 'column',
        }}
      >
        <Controller
          name="username"
          control={control}
          defaultValue=""
          rules={{ required: t('sign_up.form.name_input.required') as string }}
          render={({ field }) => (
            <CustomInput
              id={INPUT_ID_FOR_USER_FIRST_AND_LAST_NAME}
              label={t('sign_up.form.name_input.label')}
              onChange={field.onChange}
              value={field.value}
              error={errors.username?.message}
              placeholder={t('sign_up.form.name_input.placeholder')}
              type="text"
            />
          )}
        />
        <Controller
          name="email"
          control={control}
          defaultValue=""
          rules={{ required: t('sign_up.form.email_input.required') as string, pattern: /^\S+@\S+$/i }}
          render={({ field }) => (
            <CustomInput
              id={INPUT_ID_FOR_EMAIL}
              label={t('sign_up.form.email_input.label')}
              onChange={field.onChange}
              value={field.value}
              error={errors.email?.message}
              placeholder={t('sign_up.form.email_input.placeholder')}
              style={{ marginTop: '22px' }}
              type="email"
            />
          )}
        />
        <Controller
          name="password"
          control={control}
          defaultValue=""
          rules={{
            required: t('sign_up.form.password_input.required') as string,
            pattern: {
              value: /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\w\d\s:])([^\s]){8,32}$/,
              message: t(
                'sign_up.form.password_input.message'
              ),
            },
          }}
          render={({ field }) => (
            <CustomInput
              id={INPUT_ID_FOR_PASSWORD}
              label={t('sign_up.form.password_input.label')}
              onChange={field.onChange}
              value={field.value}
              error={errors.password?.message}
              placeholder="Create password"
              style={{ marginTop: '22px' }}
              type="password"
            />
          )}
        />

        <SignUpPrivacyPolicy
          isCheckboxChecked={isPrivacyPolicyCheckboxChecked}
          onPrivacyPolicyCheckboxChange={(checked) => setIsPrivacyPolicyCheckboxChecked(checked)}
        />

        <Button
          buttonSize="big"
          customVariant="light-blue"
          type="submit"
          style={{ alignSelf: isSmallest || isMobile || isTablet ? 'stretch' : 'flex-start' }}
        >
          {t('sign_up.form.button-text')}
        </Button>
      </form>
    </Grid>
  );
}
