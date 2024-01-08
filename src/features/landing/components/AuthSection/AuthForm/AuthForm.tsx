import { Box } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { useForm } from 'react-hook-form';

import {
  UiButton,
  UiCheckbox,
  UiInput,
  UiLabel,
  UiTypography,
} from '@/components/ui';

import Images from '../../../assets/svg/auth-section/image.svg';

import { authFormStyles } from './styles';

export interface RegisterItem {
  FullName: string;
  Email: string;
  Password: string;
}

function AuthForm() {
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

  return (
    <Box sx={authFormStyles.formWrapper}>
      <Image
        src={Images}
        alt="back"
        width={784}
        height={656}
        style={{
          position: 'absolute',
          left: '-40%',
          bottom: '0%',
          zIndex: '1',
        }}
      />
      <Box sx={authFormStyles.formContent}>
        <form onSubmit={handleSubmit(onSubmit)}>
          <UiTypography variant="h4" sx={{ mb: '32px' }}>
            Або зареєструйтеся на сайті:
          </UiTypography>
          <UiLabel
            sx={{ mt: '22px', paddingBottom: '9px' }}
            title="Ваше ім’я та прізвище"
            errorText={
              errors.FullName?.message || 'Виникла помилка. Перевірте ще раз'
            }
            hasError={errors.FullName}
          >
            <UiInput
              fullWidth
              error={!!errors.FullName}
              helperText={errors.FullName?.message}
              placeholder="Михайло Светський"
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
            sx={{ mt: '22px', paddingBottom: '9px' }}
            title="E-mail"
            errorText={
              errors.Email?.message || 'Виникла помилка. Перевірте ще раз'
            }
            hasError={errors.Email}
          >
            <UiInput
              fullWidth
              error={!!errors.Email?.message || false}
              helperText={errors.Email?.message}
              placeholder="vilnaCRM@gmail.com"
              // eslint-disable-next-line react/jsx-props-no-spreading
              {...register('Email', {
                required: true,
                validate: validateEmail,
              })}
            />
          </UiLabel>
          <UiLabel
            sx={{ mt: '22px', paddingBottom: '9px' }}
            title="Пароль"
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
              placeholder="Створіть пароль"
              // eslint-disable-next-line react/jsx-props-no-spreading
              {...register('Password', {
                required: true,
                validate: validatePassword,
              })}
            />
          </UiLabel>

          <UiCheckbox
            label={
              <UiTypography variant="medium14">
                Я прочитав та приймаю Політику Конфіденційності та Політику
                Використання сервісу VilnaCRM
              </UiTypography>
            }
          />
          <UiButton variant="contained" size="medium" type="submit">
            Реєєстрація
          </UiButton>
        </form>
      </Box>
    </Box>
  );
}

export default AuthForm;
