import { Box, Container, Stack } from '@mui/material';
import React from 'react';

import { aboutUsStyles } from '../AboutUs/styles';

import { AuthForm } from './AuthForm';
import { SignUpText } from './SignUpText';

function AuthSection() {
  return (
    <Box sx={aboutUsStyles.wrapper}>
      <Container>
        <Stack direction="row" justifyContent="space-between">
          <SignUpText />
          <AuthForm />
        </Stack>
      </Container>
    </Box>
  );
}

export default AuthSection;
