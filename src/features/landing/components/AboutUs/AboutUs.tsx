import { Container, Stack } from '@mui/material';
import React from 'react';

import { BackgroundImages } from './BackgroundImages';
import { DeviceImage } from './DeviceImage';
import styles from './styles';
import { TextInfo } from './TextInfo';

function AboutUs() {
  return (
    <Stack component="section" alignItems="center" sx={styles.wrapper}>
      <Container maxWidth="xl" sx={styles.content}>
        <TextInfo />
        <DeviceImage />
      </Container>
      <BackgroundImages />
    </Stack>
  );
}

export default AboutUs;
