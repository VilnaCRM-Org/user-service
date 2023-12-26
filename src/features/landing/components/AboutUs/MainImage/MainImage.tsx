import { Box } from '@mui/material';
import React from 'react';

function MainImage({ imageSrc }: { imageSrc: string }) {
  return (
    <Box
      sx={{
        overflow: 'hidden',
        borderRadius: '10px',
        marginTop: '-18px',
      }}
    >
      <Box
        sx={{
          width: '766px',
          height: '498px',
          background: 'grey',
        }}
      >
        <Box
          sx={{
            backgroundImage: `url(${imageSrc})`,
            width: '766px',
            height: '498px',
          }}
        />
      </Box>
    </Box>
  );
}
export default MainImage;
