import { Stack } from '@mui/material';
import React from 'react';

import Info from './Info/Info';

function AboutUs() {
  return (
    <Stack sx={{ mt: '80px' }} component="section" alignItems="center">
      <Info />
    </Stack>
  );
}

export default AboutUs;
