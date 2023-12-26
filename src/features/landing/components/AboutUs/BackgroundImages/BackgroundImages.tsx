import { Box } from '@mui/material';
import React from 'react';

// bad solution, need to be refactored

function BackgroundImages() {
  return (
    <Box
      sx={{
        borderRadius: '48px',
        background:
          'linear-gradient(0deg, rgba(245,255,0,1) 0%, rgba(255,252,0,1) 29%, rgba(0,236,255,1) 59%, rgba(0,164,255,1) 100%)',
        width: '1190px',
        height: '493px',
        zIndex: '-1',
      }}
    />
  );
}

export default BackgroundImages;
