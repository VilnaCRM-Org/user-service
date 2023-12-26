import { Box } from '@mui/material';
import React from 'react';

import NotchCameraImage from '../../../assets/img/AboutVilna/Notch&Camera.svg';
import MainImageSrc from '../../../assets/img/AboutVilna/Screen.png';
import MainImage from '../MainImage/MainImage';
import Notch from '../Notch/Notch';

function DeviceImage() {
  return (
    <Box
      sx={{
        border: '3px solid #78797D',
        borderRadius: '30px',
        overflow: 'hidden',
      }}
    >
      <Box
        sx={{
          border: '4px solid #232122',
          borderRadius: '25px',
          backgroundColor: '#1A1C1E',
          padding: '12px',
          overflow: 'hidden',
        }}
      >
        <Notch imageSrc={NotchCameraImage.src} />
        <MainImage imageSrc={MainImageSrc.src} />
      </Box>
    </Box>
  );
}

export default DeviceImage;
