import { Box, Container, Stack } from '@mui/material';
import React from 'react';

import { AuthForm } from './AuthForm';
import { socialLinks } from './dataArray';
import { SignUpText } from './SignUpText';
import { authSectionStyles } from './styles';

function AuthSection() {
  return (
    <Box sx={authSectionStyles.wrapper} id="signUp" component="section">
      <Container>
        <Stack justifyContent="space-between" sx={authSectionStyles.content}>
          <SignUpText socialLinks={socialLinks} />
          <AuthForm />
        </Stack>
      </Container>
    </Box>
  );
}

export default AuthSection;
