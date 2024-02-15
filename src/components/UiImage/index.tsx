/* eslint-disable react/jsx-props-no-spreading */
import { Box } from '@mui/material';
import Image from 'next/image';

import defaultImage from './DefaultImage';
import styles from './styles';
import { UiImageProps } from './types';

function UiImage({ ...props }: UiImageProps): React.ReactElement {
  return (
    <Box sx={props.sx}>
      <Image {...props} style={styles.image} />
    </Box>
  );
}

export const DefaultImage: React.FC<UiImageProps> = defaultImage(UiImage);

export default UiImage;
