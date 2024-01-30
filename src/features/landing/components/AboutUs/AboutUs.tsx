import { Container, Stack } from '@mui/material';
import React from 'react';

import { DeviceImage } from './DeviceImage';
import styles from './styles';
import { TextInfo } from './TextInfo';

function AboutUs(): React.ReactElement {
  return (
    <Stack component="section" alignItems="center" sx={styles.wrapper}>
      <Container maxWidth="xl" sx={styles.content}>
        <TextInfo />
        <DeviceImage />
      </Container>
    </Stack>
  );
}

export default AboutUs;
