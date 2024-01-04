import { Container, Stack } from '@mui/material';
import React from 'react';

import CopyrightInfo from './CopyrightInfo';
import Gmail from './Gmail';
import Navigation from './Navigation';
import PrivacyPolicy from './PrivacyPolicy';

function Mobile() {
  return (
    <Container maxWidth="sm">
      <Stack direction="column" justifyContent="center" alignItems="center">
        <Navigation />
        <Stack gap="4px" width="100%" alignItems="center">
          <Gmail />
          <PrivacyPolicy />
        </Stack>
        <CopyrightInfo />
      </Stack>
    </Container>
  );
}

export default Mobile;
