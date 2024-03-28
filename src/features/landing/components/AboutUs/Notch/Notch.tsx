import { Box } from '@mui/material';
import React from 'react';

import styles from './styles';

function Notch(): React.ReactElement {
  return (
    <Box sx={styles.wrapper}>
      <Box sx={styles.notch} data-testid="notch" />
    </Box>
  );
}

export default Notch;
