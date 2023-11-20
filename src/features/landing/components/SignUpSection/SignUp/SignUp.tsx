import { Box } from '@mui/material';
import { useForm, SubmitHandler, Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button/Button';
import CustomInput from '@/components/ui/CustomInput/CustomInput';
import {
  INPUT_ID_FOR_EMAIL,
  INPUT_ID_FOR_PASSWORD,
  INPUT_ID_FOR_USER_FIRST_AND_LAST_NAME,
} from '@/features/landing/types/sign-up/types';

import SignUpPrivacyPolicy from '../SignUpPrivacyPolicy/SignUpPrivacyPolicy';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

interface IFormData {
  username: string;
  email: string;
  password: string;
}

const styles = {
  mainBox: {
    height: '100%',
    minHeight: '548px',
    borderRadius: '32px 32px 0px 0px',
    border: '1px solid #E1E7EA',
    background: '#FFF',
    boxShadow: '1px 1px 41px 0px rgba(59, 68, 80, 0.05)',
    padding: '36px 40px 40px 40px',
    width: '100%',
  },
  form: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'stretch',
    width: '100%',
    gap: '22px',
  },
};

export default function SignUp() {
  const { t } = useTranslation();
  const { isSmallest, isMobile, isTablet } = useScreenSize();
  const { control, handleSubmit, formState } = useForm<IFormData>();
  const { errors } = formState;

  const onSubmit: SubmitHandler<IFormData> = (data) => {
    console.log(data);
    // Add your logic for submitting the form data
  };

  return (
    <Box sx={{ ...styles.mainBox }}>
      <form onSubmit={handleSubmit(onSubmit)} style={{
        ...styles.form,
        flexDirection: 'column',
      }}>
        <Controller
          name='username'
          control={control}
          defaultValue=''
          rules={{ required: 'Your firstname and lastname are required' }}
          render={({ field }) => (
            <CustomInput
              id={INPUT_ID_FOR_USER_FIRST_AND_LAST_NAME}
              label={t('Your firstname and lastname')}
              onChange={field.onChange}
              value={field.value}
              error={errors.username?.message}
              placeholder='Mykhailo Svetskyi'
              type='text'
            />
          )}
        />
        <Controller
          name='email'
          control={control}
          defaultValue=''
          rules={{ required: 'Email is required', pattern: /^\S+@\S+$/i }}
          render={({ field }) => (
            <CustomInput
              id={INPUT_ID_FOR_EMAIL}
              label={t('Email')}
              onChange={field.onChange}
              value={field.value}
              error={errors.email?.message}
              placeholder='vilnaCRM@gmail.com'
              style={{ marginTop: '22px' }}
              type='email'
            />
          )}
        />
        <Controller
          name='password'
          control={control}
          defaultValue=''
          rules={{
            required: 'Password is required',
            pattern: {
              value: /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^\w\d\s:])([^\s]){8,32}$/,
              message:
                'Invalid password. Must meet requirements: no whitespaces, at least one uppercase, one lowercase, one digit, one special symbol, and 8-32 characters long.',
            },
          }}
          render={({ field }) => (
            <CustomInput
              id={INPUT_ID_FOR_PASSWORD}
              label={t('Password')}
              onChange={field.onChange}
              value={field.value}
              error={errors.password?.message}
              placeholder='Create password'
              style={{ marginTop: '22px' }}
              type='password'
            />
          )}
        />

        <SignUpPrivacyPolicy />

        <Button buttonSize='big' customVariant='light-blue' type='submit'
                style={{ alignSelf: (isSmallest || isMobile || isTablet) ? 'stretch' : 'flex-start' }}>
          {t('Sign-Up')}
        </Button>
      </form>
    </Box>
  );
}
