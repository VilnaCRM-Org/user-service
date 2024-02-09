/* eslint-disable react/jsx-props-no-spreading */
import { Box } from '@mui/material';
import Image from 'next/image';

import styles from './styles';
import { UiImageProps } from './types';

function UiImage({ ...rest }: UiImageProps): React.ReactElement {
  return (
    <Box sx={rest.sx}>
      <Image {...rest} style={styles.image} />
    </Box>
  );
}

export default UiImage;