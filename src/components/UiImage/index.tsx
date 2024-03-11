import { Box } from '@mui/material';
import Image from 'next/image';

import styles from './styles';
import { UiImageProps } from './types';

function UiImage({ sx, alt, src }: UiImageProps): React.ReactElement {
  return (
    <Box sx={{ ...sx, ...styles.wrapper }}>
      <Image alt={alt} src={src} />
    </Box>
  );
}

export default UiImage;