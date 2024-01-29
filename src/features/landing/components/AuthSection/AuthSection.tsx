import { Box, Container, Stack } from '@mui/material';
import React from 'react';

import { AuthForm } from './AuthForm';
import { socialLinks } from './dataArray';
import { SignUpText } from './SignUpText';
import styles from './styles';

function AuthSection() {
  return (
    <Box sx={styles.wrapper} component="section">
      <Container>
        <Stack justifyContent="space-between" sx={styles.content}>
          <SignUpText socialLinks={socialLinks} />
          <AuthForm />
        </Stack>
      </Container>
    </Box>
  );
}

export default AuthSection;
