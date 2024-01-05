import { Stack } from '@mui/material';
import React from 'react';

import { BackgroundImages } from './BackgroundImages';
import { DeviceImage } from './DeviceImage';
import { TextInfo } from './TextInfo';

function AboutUs() {
  return (
    <Stack
      component="section"
      alignItems="center"
      sx={{
        pt: '9rem',
        position: 'relative',
        maxWidth: '100dvw',
      }}
    >
      <TextInfo />
      <DeviceImage />
      <BackgroundImages />
    </Stack>
  );
}

export default AboutUs;
