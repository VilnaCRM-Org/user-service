import { Box } from '@mui/material';
import React from 'react';

function Notch({ imageSrc }: { imageSrc: string }) {
  return (
    <Box
      sx={{
        position: 'relative',
        margin: '0 auto',
        bottom: '4px',
        left: '0',
        backgroundImage: `url(${imageSrc})`,
        backgroundRepeat: 'no-repeat',
        width: '110.791px',
        height: '18.535px',
        zIndex: '11',
      }}
    />
  );
}

export default Notch;
