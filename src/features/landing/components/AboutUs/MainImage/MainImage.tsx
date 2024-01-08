import { Box } from '@mui/material';
import React from 'react';

import { mainImageStyles } from './styles';

function MainImage() {
  return (
    <Box sx={mainImageStyles.mainImageWrapper}>
      <Box sx={mainImageStyles.mainImage} />
    </Box>
  );
}
export default MainImage;
