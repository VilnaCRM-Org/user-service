import { Stack } from '@mui/material';
import React from 'react';

// import BackgroundImages from './BackgroundImages/BackgroundImages';
import DeviceImage from './DeviceImage/DeviceImage';
import TextInfo from './TextInfo/TextInfo';

function AboutUs() {
  return (
    <Stack component="section" alignItems="center" sx={{ pt: '9rem' }}>
      <TextInfo />
      <DeviceImage />
      {/* <BackgroundImages /> */}
    </Stack>
  );
}

export default AboutUs;
