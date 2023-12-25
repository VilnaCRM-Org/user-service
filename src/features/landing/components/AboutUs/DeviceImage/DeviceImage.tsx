import { Box } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import DeskTop from '../../../assets/img/AboutVilna/desktop.png';

function DeviceImage() {
  return (
    <Box
      sx={{
        display: 'flex',
        justifyContent: 'center',
        backgroundImage: `url('/assets/backGroundDesktop.png')`,
        backgroundRepeat: 'no-repeat',
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        width: '1192px',
        height: '493px',
      }}
    >
      <Image
        src={DeskTop}
        alt="Header Image"
        width={960}
        height={545}
        style={{}}
      />
    </Box>
  );
}

export default DeviceImage;
