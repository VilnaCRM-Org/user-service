import {
  Box,
  Checkbox,
  Container,
  FormControlLabel,
  Stack,
  Typography,
} from '@mui/material';
import React from 'react';
import { useForm } from 'react-hook-form';

import UIButton from '@/components/ui/UIButton/UIButton';
import UIInput from '@/components/ui/UIInput/UIInput';
import UILabel from '@/components/ui/UILabel/UILabel';

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
  const onSubmit = (data: RegisterItem) => {
    console.log(data);
  };

  const isLengthValid = (value: string) =>
    value.length >= 8 && value.length <= 64;

  const hasNumber = (value: string) => /[0-9]/.test(value);

  const hasUppercase = (value: string) => /[A-Z]/.test(value);

  function validatePassword(value: string) {
    if (!isLengthValid(value))
      return 'Password must be between 8 and 64 characters long';
    if (!hasNumber(value)) return 'Password must contain at least one number';
    if (!hasUppercase(value))
      return 'Password must contain at least one uppercase letter';
    return true;
  }

  function isValidEmailFormat(email: string) {
    return /^.+@.+\..+$/.test(email);
  }

  function validateEmail(email: string) {
    if (!isValidEmailFormat(email)) return 'Invalid email format';
    return true;
  }

  function isValidFullNameFormat(fullName: string) {
    return /^[^\d\s]+\s[^\d\s]+$/.test(fullName);
  }

  function validateFullName(fullName: string) {
    if (!isValidFullNameFormat(fullName)) return 'Invalid full name format';
    if (fullName.split(' ').some(part => part.length === 0))
      return 'Name and surname should have at least 1 character';
    return true;
  }

  return (
    <Box sx={{ background: '#FBFBFB', mb: '2px' }}>
      <Container>
        <Stack
          alignItems="center"
          direction="row"
          gap="128px"
          sx={{ pt: '65px' }}
        >
          <SignUpText />
          <Box
            sx={{
              padding: '35px',
              width: '50%',
              maxWidth: '507px',
              borderRadius: '32px 32px 0px 0px',
              border: '1px solid  #E1E7EA',
              background: '#FFF',
              boxShadow: '1px 1px 41px 0px rgba(59, 68, 80, 0.05)',
            }}
          >
            <form onSubmit={handleSubmit(onSubmit)}>
              <Typography variant="h4" sx={{ mb: '32px' }}>
                Або зареєструйтеся на сайті:
              </Typography>
              <UILabel
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
              <FormControlLabel
                sx={{ pt: '20px', pb: '32px' }}
                control={<Checkbox defaultChecked />}
                label="Я прочитав та приймаю Політику Конфіденційності та Політику Використання сервісу VilnaCRM"
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
