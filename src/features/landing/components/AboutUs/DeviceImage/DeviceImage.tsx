import { Box, Stack } from '@mui/material';
import React from 'react';

import { MainImage } from '../MainImage';
import { Notch } from '../Notch';

import { deviceImageStyles } from './styles';

function DeviceImage() {
  return (
    <Stack
      justifyContent="center"
      alignItems="center"
      sx={deviceImageStyles.wrapper}
    >
      <Box sx={deviceImageStyles.screenBorder}>
        <Box sx={deviceImageStyles.screenBackground}>
          <Notch />
          <MainImage />
        </Box>
      </Box>
      <Box sx={deviceImageStyles.backgroundImage} />
    </Stack>
  );
}

export default DeviceImage;
