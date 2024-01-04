import { Box, Container, Stack } from '@mui/material';
import React from 'react';
import { useForm } from 'react-hook-form';

import UIButton from '@/components/ui/UIButton/UIButton';
import UICheckbox from '@/components/ui/UICheckbox/UICheckbox';
import UIInput from '@/components/ui/UIInput/UIInput';
import UILabel from '@/components/ui/UILabel/UILabel';
import UITypography from '@/components/ui/UITypography/UITypography';

import SignUpText from './SignUpText/SignUpText';

// in progress dont need review

export interface RegisterItem {
  FullName: string;
  Email: string;
  Password: string;
}

function AuthSection() {
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
    <Box sx={{ background: '#FBFBFB', mb: '2px' }}>
      <Container>
        <Stack direction="row" justifyContent="space-between">
          <SignUpText />
          <Box
            sx={{
              padding: '36px 40px 40px',
              mt: '65px',
              maxWidth: '502px',
              borderRadius: '32px 32px 0px 0px',
              border: '1px solid  #E1E7EA',
              background: '#FFF',
              boxShadow: '1px 1px 41px 0px rgba(59, 68, 80, 0.05)',
            }}
          >
            <form onSubmit={handleSubmit(onSubmit)}>
              <UITypography variant="h4" sx={{ mb: '32px' }}>
                Або зареєструйтеся на сайті:
              </UITypography>
              <UILabel
                sx={{ mt: '22px', paddingBottom: '9px' }}
                title="Ваше ім’я та прізвище"
                errorText={
                  errors.FullName?.message ||
                  'Виникла помилка. Перевірте ще раз'
                }
                hasError={errors.FullName}
              >
                <UIInput
                  hasError={errors.FullName}
                  placeholder="Михайло Светський"
                  // eslint-disable-next-line react/jsx-props-no-spreading
                  {...register('FullName', {
                    required: true,
                    validate: validateFullName,
                    minLength: 2,
                    maxLength: 50,
                  })}
                />
              </UILabel>
              <UILabel
                sx={{ mt: '22px', paddingBottom: '9px' }}
                title="E-mail"
                errorText={
                  errors.Email?.message || 'Виникла помилка. Перевірте ще раз'
                }
                hasError={errors.Email}
              >
                <UIInput
                  hasError={errors.Email}
                  placeholder="vilnaCRM@gmail.com"
                  // eslint-disable-next-line react/jsx-props-no-spreading
                  {...register('Email', {
                    required: true,
                    validate: validateEmail,
                  })}
                />
              </UILabel>
              <UILabel
                sx={{ mt: '22px', paddingBottom: '9px' }}
                title="Пароль"
                errorText={
                  errors.Password?.message ||
                  'Виникла помилка. Перевірте ще раз'
                }
                hasError={errors.Password}
              >
                <UIInput
                  hasError={errors.Password}
                  type="password"
                  placeholder="Створіть пароль"
                  // eslint-disable-next-line react/jsx-props-no-spreading
                  {...register('Password', {
                    required: true,
                    validate: validatePassword,
                  })}
                />
              </UILabel>

              <UICheckbox
                label={
                  <UITypography variant="medium14">
                    Я прочитав та приймаю Політику Конфіденційності та Політику
                    Використання сервісу VilnaCRM
                  </UITypography>
                }
              />
              <UIButton variant="contained" size="medium" type="submit">
                Реєєстрація
              </UIButton>
            </form>
          </Box>
        </Stack>
      </Container>
    </Box>
  );
}

export default AuthSection;
