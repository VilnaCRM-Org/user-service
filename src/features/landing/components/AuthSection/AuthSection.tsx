import { Box, Container, Stack } from '@mui/material';
import React from 'react';

import { AuthForm } from './AuthForm';
import { SignUpText } from './SignUpText';

function AuthSection() {
  return (
    <Box sx={{ background: '#FBFBFB', mb: '2px' }}>
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
