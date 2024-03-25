import { Box } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import MainImageSrc from '../../../assets/img/about-vilna/Screen.webp';

import styles from './styles';

function MainImage(): React.ReactElement {
  return (
    <Box sx={styles.mainImageWrapper}>
      <Image src={MainImageSrc} alt="Main image" width={766} height={498} />
    </Box>
  );
}
export default MainImage;
