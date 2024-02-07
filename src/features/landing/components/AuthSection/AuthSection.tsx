import { Box, Container, Stack } from '@mui/material';
import React from 'react';

import { socialLinks } from './dataArray';
import ConnectedLoginForm from './Login/ConnectedLoginForm';
import { SignUpText } from './SignUpText';
import styles from './styles';

function AuthSection(): React.ReactElement {
  return (
    <Box sx={styles.wrapper} component="section">
      <Container>
        <Stack justifyContent="space-between" sx={styles.content}>
          <SignUpText socialLinks={socialLinks} />
          <ConnectedLoginForm />
        </Stack>
      </Container>
    </Box>
  );
}

export default AuthSection;
