import { Box, Container, Stack } from '@mui/material';
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
              height: '647px',
              borderRadius: '32px 32px 0px 0px',
              border: '1px solid  #E1E7EA',
              background: '#FFF',
              boxShadow: '1px 1px 41px 0px rgba(59, 68, 80, 0.05)',
            }}
          >
            <form onSubmit={handleSubmit(onSubmit)}>
              <UILabel
                title="Ваше ім’я та прізвище"
                errorText="Виникла помилка. Перевірте ще раз"
                hasError={errors.FullName}
              >
                <UIInput
                  hasError={errors.FullName}
                  placeholder="Михайло Светський"
                  // eslint-disable-next-line react/jsx-props-no-spreading
                  {...register('FullName', {
                    required: true,
                  })}
                />
              </UILabel>
              <UILabel
                title="E-mail"
                errorText="Виникла помилка. Перевірте ще раз"
                hasError={errors.Email}
              >
                <UIInput
                  hasError={errors.Email}
                  placeholder="vilnaCRM@gmail.com"
                  // eslint-disable-next-line react/jsx-props-no-spreading
                  {...register('Email', {
                    required: true,
                  })}
                />
              </UILabel>
              <UILabel
                title="Пароль"
                errorText="Виникла помилка. Перевірте ще раз"
                hasError={errors.Password}
              >
                <UIInput
                  hasError={errors.Password}
                  placeholder="Створіть пароль"
                  // eslint-disable-next-line react/jsx-props-no-spreading
                  {...register('Password', {
                    required: true,
                  })}
                />
              </UILabel>
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
