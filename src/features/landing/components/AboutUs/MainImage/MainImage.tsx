import { Box } from '@mui/material';
import React from 'react';

import styles from './styles';

function MainImage() {
  return (
    <Box sx={styles.mainImageWrapper}>
      <Box sx={styles.mainImage} />
    </Box>
  );
}
export default MainImage;
