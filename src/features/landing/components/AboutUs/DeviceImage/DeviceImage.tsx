import { Box, Stack } from '@mui/material';
import React from 'react';

import { MainImage } from '../MainImage';
import { Notch } from '../Notch';

import styles from './styles';

function DeviceImage() {
  return (
    <Stack justifyContent="center" alignItems="center" sx={styles.wrapper}>
      <Box sx={styles.screenBorder}>
        <Box sx={styles.screenBackground}>
          <Notch />
          <MainImage />
        </Box>
      </Box>
      <Box sx={styles.backgroundImage} />
    </Stack>
  );
}

export default DeviceImage;
