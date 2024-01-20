/* eslint-disable react/jsx-props-no-spreading */
import { Box } from '@mui/material';
import Image from 'next/image';

import { UiImageProps } from './types';

function UiImage({ src, alt, sx, ...rest }: UiImageProps) {
  return (
    <Box sx={sx}>
      <Image
        src={src}
        alt={alt}
        {...rest}
        style={{ width: '100%', height: '100%' }}
      />
    </Box>
  );
}

export default UiImage;
