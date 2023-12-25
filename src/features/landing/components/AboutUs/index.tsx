import { Stack } from '@mui/material';
import React from 'react';

import DeviceImage from './DeviceImage/DeviceImage';
import Info from './Info/Info';

function AboutUs() {
  return (
    <Stack component="section" alignItems="center" sx={{ pt: '9rem' }}>
      <Info />
      <DeviceImage />
    </Stack>
  );
}

export default AboutUs;
