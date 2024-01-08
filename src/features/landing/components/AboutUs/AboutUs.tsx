import { Stack } from '@mui/material';
import React from 'react';

import { BackgroundImages } from './BackgroundImages';
import { DeviceImage } from './DeviceImage';
import { aboutUsStyles } from './styles';
import { TextInfo } from './TextInfo';

function AboutUs() {
  return (
    <Stack component="section" alignItems="center" sx={aboutUsStyles.wrapper}>
      <TextInfo />
      <DeviceImage />
      <BackgroundImages />
    </Stack>
  );
}

export default AboutUs;
