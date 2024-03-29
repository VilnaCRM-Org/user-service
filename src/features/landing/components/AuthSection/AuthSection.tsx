import { ApolloProvider } from '@apollo/client';
import { Box, Container, Stack } from '@mui/material';
import React from 'react';

import client from '../../api/graphql/apollo';

import { AuthForm } from './AuthForm';
import { socialLinks } from './constants';
import { SignUpText } from './SignUpText';
import styles from './styles';

function AuthSection(): React.ReactElement {
  return (
    <Box sx={styles.wrapper} component="section" data-testid="auth-section">
      <Container>
        <Stack justifyContent="space-between" sx={styles.content}>
          <SignUpText socialLinks={socialLinks} />
          <ApolloProvider client={client}>
            <AuthForm />
          </ApolloProvider>
        </Stack>
      </Container>
    </Box>
  );
}

export default AuthSection;
