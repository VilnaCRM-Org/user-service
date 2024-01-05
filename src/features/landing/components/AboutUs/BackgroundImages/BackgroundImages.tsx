import { Box } from '@mui/material';
import React from 'react';

import VectorIcon from '../../../assets/img/AboutVilna/Vector.svg';

function BackgroundImages() {
  return (
    <>
      <Box
        sx={{
          backgroundImage: `url(${VectorIcon.src})`,
          backgroundSize: 'contain',
          backgroundRepeat: 'no-repeat',
          backgroundPosition: 'center',
          width: '100dvw',
          height: '900px',
          zIndex: '-2',
          position: 'absolute',
          left: '50%',
          top: { md: '4%', xl: '11%' },
          transform: 'translateX(-50%)',
        }}
      />
      <Box
        sx={{
          position: 'absolute',
          background:
            'linear-gradient( to bottom, rgba(34, 181, 252, 1) 0%, rgba(252, 231, 104, 1) 100%)',
          width: '100%',
          maxwidth: '1192px',
          height: '493px',
          zIndex: '-1',
          top: '54%',
          left: '0',
          borderRadius: '48px',
        }}
      />
    </>
  );
}

export default BackgroundImages;
